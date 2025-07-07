<?php

namespace ImportIt\Helper;

use DateTime;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Entity\Resource;
use Omeka\Entity\Property;
use Omeka\Entity\Value;

abstract class ResourceBuilder
{
    protected ServiceLocatorInterface $serviceLocator;
    protected Resource $resource;
    protected array $propertyMap = [];

    public function __construct(Resource $resource, ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        $this->resource = $resource;
        if (!$this->resource->getCreated()) {
            $this->resource->setCreated(new DateTime());
        }
    }

    public function addLiteralValue(string $term, string $value, string $lang = null, bool $isPublic = true)
    {
        $value = trim($value);

        $property = $this->getPropertyReference($term);

        $v = new Value();
        $v->setResource($this->resource);
        $v->setProperty($property);
        $v->setType('literal');
        $v->setValue($value);
        $v->setIsPublic($isPublic);

        $this->resource->getValues()->add($v);

        if ($term === 'dcterms:title' && !$this->resource->getTitle()) {
            $this->resource->setTitle($value);
        }
    }

    public function getPropertyReference(string $term): Property
    {
        $em = $this->getEntityManager();

        $this->buildPropertyMap();

        if (!isset($this->propertyMap[$term])) {
            throw new \Exception(sprintf('Unknown property "%s"', $term));
        }

        return $em->getReference(Property::class, $this->propertyMap[$term]);
    }

    protected function buildPropertyMap()
    {
        if (!$this->propertyMap) {
            $em = $this->getEntityManager();
            $properties = $em->getRepository(Property::class)->findAll();
            foreach ($properties as $property) {
                $term = sprintf('%s:%s', $property->getVocabulary()->getPrefix(), $property->getLocalName());
                $this->propertyMap[$term] = $property->getId();
            }
        }
    }

    public function getResource(): Resource
    {
        return $this->resource;
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->getServiceLocator()->get('Omeka\EntityManager');
    }

    protected function getServiceLocator(): ServiceLocatorInterface
    {
        return $this->serviceLocator;
    }
}
