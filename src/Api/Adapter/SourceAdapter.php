<?php declare(strict_types=1);

namespace ImportIt\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class SourceAdapter extends AbstractEntityAdapter
{
    protected $sortFields = [
        'id' => 'id',
        'name' => 'name',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'name' => 'name',
    ];

    public function getEntityClass()
    {
        return \ImportIt\Entity\Source::class;
    }

    public function getResourceName()
    {
        return 'importit_sources';
    }

    public function getRepresentationClass()
    {
        return \ImportIt\Api\Representation\SourceRepresentation::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query): void
    {
        if (isset($query['name'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.name',
                $this->createNamedParameter($qb, $query['name']))
            );
        }

        if (isset($query['type'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.type',
                $this->createNamedParameter($qb, $query['type']))
            );
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore): void
    {
        /** @var \ImportIt\Entity\Source $entity */

        if ($this->shouldHydrate($request, 'o:name')) {
            $entity->setName($request->getValue('o:name'));
        }

        if ($this->shouldHydrate($request, 'o:type')) {
            $entity->setType($request->getValue('o:type'));
        }

        if ($this->shouldHydrate($request, 'o:settings')) {
            $entity->setSettings($request->getValue('o:settings', []));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        /** @var \ImportIt\Entity\Source $entity */

        $name = $entity->getName();
        if (!is_string($name) || $name === '') {
            $errorStore->addError('o:name', 'A source must have a name.');
        }

        $type = $entity->getType();
        if (!is_string($type) || $type === '') {
            $errorStore->addError('o:type', 'A source must have a type.');
        }
    }
}
