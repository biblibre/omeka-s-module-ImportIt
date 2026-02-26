<?php

namespace ImportIt\Helper;

use DateTime;
use Doctrine\ORM\EntityManager;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Entity\Item;
use Omeka\Entity\Property;
use Omeka\Entity\Value;

class ItemBuilder extends ResourceBuilder
{
    public function __construct(Item $item, ServiceLocatorInterface $serviceLocator)
    {
        parent::__construct($item, $serviceLocator);
    }

    public function getItem(): Item
    {
        return $this->resource;
    }

    public function addDefaultSites(): void
    {
        $sites = $this->getEntityManager()->getRepository('Omeka\Entity\Site')->findBy(['assignNewItems' => true]);
        foreach ($sites as $site) {
            $this->getItem()->getSites()->set($site->getId(), $site);
        }
    }
}
