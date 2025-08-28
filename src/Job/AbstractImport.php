<?php

namespace ImportIt\Job;

use Doctrine\ORM\EntityManager;
use ImportIt\Api\Representation\SourceRepresentation;
use ImportIt\Entity\Source;
use ImportIt\Entity\SourceRecord;
use ImportIt\Helper\ItemBuilder;
use ImportIt\Helper\MediaBuilder;
use ImportIt\Log\Writer\ImportLogWriter;
use Laminas\Log\Logger;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Item;
use Omeka\Entity\Media;
use Omeka\Entity\Resource;
use Omeka\Job\AbstractJob;

abstract class AbstractImport extends AbstractJob
{
    protected Source $source;
    protected Logger $logger;

    public function perform()
    {
        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $logger = $this->logger();

        $logger->info(sprintf('Job #%d started', $this->job->getId()));

        if ($this->getArg('delete_all_entities', false)) {
            $this->deleteAllEntities();
        }

        if ($this->shouldStop()) {
            $this->handleStop();
            return;
        }

        try {
            $this->import();
        } catch (\Throwable $e) {
            $logger->err($e->getMessage());
            throw $e;
        }

        if ($this->shouldStop()) {
            $this->handleStop();
            return;
        }

        $logger->info('Job ended normally');
    }

    abstract protected function import(): void;

    protected function handleStop(): void
    {
        $this->logger()->info('Job stopped');
    }

    protected function deleteAllEntities()
    {
        $em = $this->getEntityManager();
        $logger = $this->logger();

        $logger->info('Deletion of all entities started');

        $source = $this->getSource();
        $records = $source->getRecords()->toArray();
        foreach ($records as $record) {
            try {
                $entity = $em->find($record->getEntityClass(), $record->getEntityId());
                if ($entity) {
                    $em->remove($entity);
                    $em->flush();
                }

                $em->remove($record);
                $em->flush();

                $logger->info(sprintf('Deleted entity %s #%d', $record->getEntityClass(), $record->getEntityId()));
            } catch (\Exception $e) {
                $logger->err(sprintf('Failed to remove entity %s #%d: %s', $record->getEntityClass(), $record->getEntityId(), $e->getMessage()));
            }
        }

        $logger->info('Deletion of all entities finished');
    }

    protected function getSource(): Source
    {
        if (!isset($this->source)) {
            $em = $this->getEntityManager();

            $this->source = $em->find(Source::class, $this->getArg('source_id'));
        }

        return $this->source;
    }

    protected function getEntityManager(): \Doctrine\ORM\EntityManager
    {
        return $this->getServiceLocator()->get('Omeka\EntityManager');
    }

    protected function logger(): Logger
    {
        if (!isset($this->logger)) {
            $logger = $this->getServiceLocator()->get('ImportIt\Logger');

            $writer = new ImportLogWriter;
            $writer->setApiManager($this->getServiceLocator()->get('Omeka\ApiManager'));
            $writer->setJobId($this->job->getId());
            $logger->addWriter($writer);

            $this->logger = $logger;
        }

        return $this->logger;
    }

    protected function getItemBuilder(): ItemBuilder
    {
        return new ItemBuilder(new Item, $this->getServiceLocator());
    }

    protected function getMediaBuilder(): MediaBuilder
    {
        return new MediaBuilder(new Media, $this->getServiceLocator());
    }

    protected function recordImportedEntity(EntityInterface $entity, string $externalId = null)
    {
        $services = $this->getServiceLocator();
        $em = $this->getEntityManager();

        $sourceRecord = new SourceRecord;
        $sourceRecord->setSource($this->getSource());
        $sourceRecord->setExternalId($externalId);
        $sourceRecord->setEntityClass($entity->getResourceId());
        $sourceRecord->setEntityId($entity->getId());

        $em->persist($sourceRecord);
        $em->flush();

        $this->logger()->info(sprintf('Imported record %s #%d%s', $entity->getResourceId(), $entity->getId(), $externalId ? " ($externalId)" : ''));
    }

    protected function getImportedEntities(string $externalId): array
    {
        $em = $this->getEntityManager();

        $sourceRecords = $em->getRepository(SourceRecord::class)->findBy(['source' => $this->getSource(), 'externalId' => $externalId]);
        $entities = [];
        foreach ($sourceRecords as $sourceRecord) {
            $entity = $em->find($sourceRecord->getEntityClass(), $sourceRecord->getEntityId());
            if ($entity) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    protected function saveFulltext(Resource $resource)
    {
        $adapterName = match($resource->getResourceId()) {
            Item::class => 'items',
            Media::class => 'media',
            ItemSet::class => 'item_sets',
        };

        $fulltextSearch = $this->getServiceLocator()->get('Omeka\FulltextSearch');
        $apiAdapterManager = $this->getServiceLocator()->get('Omeka\ApiAdapterManager');
        $fulltextSearch->save($resource, $apiAdapterManager->get($adapterName));
    }

    protected function getIdentityMap(): array
    {
        return $this->getEntityManager()->getUnitOfWork()->getIdentityMap();
    }

    protected function detachAllNewEntities(array $oldIdentityMap)
    {
        $entityManager = $this->getEntityManager();
        $identityMap = $entityManager->getUnitOfWork()->getIdentityMap();
        foreach ($identityMap as $entityClass => $entities) {
            foreach ($entities as $idHash => $entity) {
                if (!isset($oldIdentityMap[$entityClass][$idHash])) {
                    $entityManager->detach($entity);
                }
            }
        }
    }

    protected function isModuleActive(string $id): bool
    {
        $moduleManager = $this->getServiceLocator()->get('Omeka\ModuleManager');

        $module = $moduleManager->getModule($id);
        if ($module && $module->getState() === \Omeka\Module\Manager::STATE_ACTIVE) {
            return true;
        }

        return false;
    }
}
