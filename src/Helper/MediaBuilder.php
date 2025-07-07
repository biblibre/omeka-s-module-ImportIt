<?php

namespace ImportIt\Helper;

use DateTime;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Request;
use Omeka\Entity\Item;
use Omeka\Entity\Media;
use Omeka\Entity\Property;
use Omeka\Entity\Value;
use Omeka\Stdlib\ErrorStore;

class MediaBuilder extends ResourceBuilder
{
    public function __construct(Media $media, ServiceLocatorInterface $serviceLocator)
    {
        parent::__construct($media, $serviceLocator);
    }

    public function setItem(Item $item)
    {
        $media = $this->getMedia();
        $media->setItem($item);
        $item->getMedia()->add($media);

        if (null === $media->getPosition()) {
            $em = $this->getEntityManager();
            $mediaCount = $em->getRepository(Media::class)->count(['item' => $item]);
            $media->setPosition($mediaCount + 1);
        }
    }

    public function getMedia(): Media
    {
        return $this->resource;
    }

    public function ingestLocalFile(string $filepath): void
    {
        $em = $this->getEntityManager();

        $media = $this->getMedia();

        $ingester = $this->getServiceLocator()->get('Omeka\Media\Ingester\Manager')->get('local');
        $media->setIngester('local');
        $media->setRenderer($ingester->getRenderer());
        $media->setSource($filepath);

        $request = new Request(Request::CREATE, 'media');
        $request->setContent([
            'o:source' => $filepath,
            'ingest_filename' => $filepath,
        ]);
        $errorStore = new ErrorStore;
        $ingester->ingest($media, $request, $errorStore);
    }

    public function ingestUrl(string $url): void
    {
        $em = $this->getEntityManager();

        $media = $this->getMedia();

        $ingester = $this->getServiceLocator()->get('Omeka\Media\Ingester\Manager')->get('url');
        $media->setIngester('url');
        $media->setRenderer($ingester->getRenderer());
        $media->setSource($url);

        $request = new Request(Request::CREATE, 'media');
        $request->setContent([
            'o:source' => $url,
            'ingest_url' => $url,
        ]);
        $errorStore = new ErrorStore;
        $ingester->ingest($media, $request, $errorStore);
    }
}
