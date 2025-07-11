<?php

namespace ImportIt\Job;

use CallbackFilterIterator;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Omeka\Entity\Item;
use Omeka\Entity\Job;
use Omeka\Entity\Media;

class ServerSideMets extends AbstractImport
{
    const NS_DC = 'http://purl.org/dc/elements/1.1/';
    const NS_METS = 'http://www.loc.gov/METS/';
    const NS_XLINK = 'http://www.w3.org/1999/xlink';

    protected function import(): void
    {
        $em = $this->getEntityManager();

        $source = $this->getSource();
        $sourceSettings = $source->getSettings();

        $path = $sourceSettings['path'];
        if (!$path) {
            throw new Exception('No path');
        }

        $recursiveDirectoryIterator = new RecursiveDirectoryIterator($path, FilesystemIterator::KEY_AS_FILENAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS);
        $recursiveIteratorIterator = new RecursiveIteratorIterator($recursiveDirectoryIterator);
        $callbackFilterIterator = new CallbackFilterIterator($recursiveIteratorIterator, fn ($file) => $file->getExtension() === 'xml');
        $files = iterator_to_array($callbackFilterIterator);
        uasort($files, fn ($a, $b) => strnatcmp($a->getPathname(), $b->getPathname()));

        // Load all properties before building $originalIdentityMap, so that
        // they are not detached by detachAllNewEntities
        $em->getRepository(\Omeka\Entity\Property::class)->findAll();

        $originalIdentityMap = $this->getIdentityMap();

        foreach ($files as $filename => $fileInfo) {
            $filepath = $fileInfo->getPathname();
            try {
                $this->importFile($filepath);
            } catch (Exception $e) {
                $this->logger()->err(sprintf('Failed to import %s: %s', $filepath, $e->getMessage()));
            }

            $this->detachAllNewEntities($originalIdentityMap);

            if ($this->shouldStop()) {
                return;
            }
        }
    }

    protected function importFile(string $filepath): void
    {
        $this->logger()->info(sprintf('Found %s', $filepath));

        $document = new DOMDocument();
        if (false === $document->load($filepath)) {
            throw new Exception('Failed to load %s', $filepath);
        }

        $xpath = $this->getDOMXPath($document);

        $nodeList = $xpath->query('/mets:mets/mets:structMap');
        if ($nodeList === false || $nodeList->count() === 0) {
            throw new Exception('No structMap found');
        }

        $structMap = $nodeList->item(0);
        foreach ($xpath->query('mets:div', $structMap) as $div) {
            $this->importDivAsItem($div);
        }
    }

    protected function importDivAsItem(DOMNode $div): void
    {
        $document = $div->ownerDocument;
        $xpath = $this->getDOMXPath($document);

        $objId = $xpath->evaluate('string(/mets:mets/@OBJID)');
        $externalId = !empty($objId) ? $objId : null;

        $entities = [];
        if ($externalId) {
            $entities = $this->getImportedEntities($externalId);
        }

        if ($entities) {
            $this->logger()->info(sprintf('Item already imported, skipping (%s)', $externalId));

            $item = reset($entities);
        } else {
            $dmdId = $div->getAttribute('DMDID');

            $dmdSecNodeList = $xpath->query(sprintf('/mets:mets/mets:dmdSec[@ID="%s"]', $dmdId));
            if ($dmdSecNodeList === false || $dmdSecNodeList->count() === 0) {
                throw new Exception(sprintf('Cannot find dmdSec with ID: %s', $dmdId));
            }
            $dmdSec = $dmdSecNodeList->item(0);

            $dcNodeList = $xpath->query('descendant::dc:dc', $dmdSec);
            if ($dcNodeList === false || $dcNodeList->count() === 0) {
                throw new Exception('No dc found');
            }

            $itemBuilder = $this->getItemBuilder();

            $dc = $dcNodeList->item(0);
            foreach ($xpath->query('dc:*', $dc) as $propertyNode) {
                $term = sprintf('dcterms:%s', $propertyNode->localName);
                $itemBuilder->addLiteralValue($term, $propertyNode->textContent);
            }

            $item = $itemBuilder->getItem();

            $em = $this->getEntityManager();
            $em->persist($item);
            $em->flush();

            $this->saveFulltext($item);

            $this->recordImportedEntity($item, $externalId);
        }

        $fptrNodeList = $xpath->query('descendant::mets:fptr', $div);
        foreach ($fptrNodeList as $fptrNode) {
            try {
                $this->importFptrAsMedia($fptrNode, $item);
            } catch (\Throwable $e) {
                $fileId = $fptrNode->getAttribute('FILEID');
                $this->logger()->err(sprintf('Failed to import file "%s": %s', $fileId, $e->getMessage()));
            }

            if ($this->shouldStop()) {
                return;
            }
        }
    }

    protected function importFptrAsMedia(DOMNode $fptrNode, Item $item)
    {
        $document = $fptrNode->ownerDocument;
        $xpath = $this->getDOMXPath($document);

        $fileId = $fptrNode->getAttribute('FILEID');
        if ($fileId === '') {
            $this->logger()->warn(sprintf('No FILEID attribute on fptr element %s', $fptr->getNodePath()));
            return;
        }

        $objId = $xpath->evaluate('string(/mets:mets/@OBJID)');
        $externalId = !empty($objId) ? $objId : null;
        if (isset($externalId)) {
            $externalId .= "/$fileId";
        }

        if ($externalId) {
            $entities = $this->getImportedEntities($externalId);
            if ($entities) {
                $this->logger()->info(sprintf('File already imported, skipping (%s)', $externalId));
                return;
            }
        }

        $fileNodeList = $xpath->query(sprintf('/mets:mets/mets:fileSec//mets:file[@ID="%s"]', $fileId));
        if ($fileNodeList === false || $fileNodeList->count() === 0) {
            $this->logger()->warn(sprintf('No file found for ID "%s"', $fileId));
            return;
        }

        $fileNode = $fileNodeList->item(0);
        $fLocatNodeList = $xpath->query('mets:FLocat', $fileNode);
        if ($fLocatNodeList === false || $fLocatNodeList->count() === 0) {
            $this->logger()->warn(sprintf('No FLocat found for ID "%s"', $fileId));
            return;
        }

        $fLocatNode = $fLocatNodeList->item(0);
        $href = $fLocatNode->getAttributeNS(self::NS_XLINK, 'href');
        if ($href === '') {
            $this->logger()->warn(sprintf('No href attribute found in FLocat for ID "%s"', $fileId));
            return;
        }

        $mediaBuilder = $this->getMediaBuilder();
        $mediaBuilder->setItem($item);

        $dmdId = $xpath->evaluate('string(parent::mets:div/@DMDID)', $fptrNode);
        if ($dmdId) {
            $dmdSecNodeList = $xpath->query(sprintf('/mets:mets/mets:dmdSec[@ID="%s"]', $dmdId));
            if ($dmdSecNodeList === false || $dmdSecNodeList->count() === 0) {
                throw new Exception(sprintf('Cannot find dmdSec with ID: %s', $dmdId));
            }
            $dmdSec = $dmdSecNodeList->item(0);

            $dcNodeList = $xpath->query('descendant::dc:dc', $dmdSec);
            if ($dcNodeList === false || $dcNodeList->count() === 0) {
                throw new Exception('No dc found');
            }

            $dc = $dcNodeList->item(0);
            foreach ($xpath->query('dc:*', $dc) as $propertyNode) {
                $term = sprintf('dcterms:%s', $propertyNode->localName);
                $mediaBuilder->addLiteralValue($term, $propertyNode->textContent);
            }
        } else {
            $this->logger()->warn('No dmdid for media');
        }

        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            $mediaBuilder->ingestUrl($href);
        } else {
            $filepath = sprintf('%s/%s', dirname($document->documentURI), $href);
            if (!is_file($filepath)) {
                $this->logger()->warn(sprintf('"%s" does not exist or is not a file', $filepath));
                return;
            }

            $mediaBuilder->ingestLocalFile($filepath);
        }

        $media = $mediaBuilder->getMedia();

        $em = $this->getEntityManager();
        $em->persist($media);
        $em->flush();

        $this->saveFulltext($media);

        $this->recordImportedEntity($media, $externalId);
    }

    protected function getDOMXPath(DOMDocument $document): DOMXPath
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('dc', self::NS_DC);
        $xpath->registerNamespace('mets', self::NS_METS);
        $xpath->registerNamespace('xlink', self::NS_XLINK);

        return $xpath;
    }
}
