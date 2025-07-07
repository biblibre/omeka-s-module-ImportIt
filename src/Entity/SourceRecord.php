<?php

namespace ImportIt\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Item;
use Omeka\Entity\Resource;

/**
 * @Entity
 * @Table(
 *     name="importit_source_record",
 *     indexes={@Index(fields={"source", "externalId"})},
 *     uniqueConstraints={@UniqueConstraint(fields={"source", "externalId", "entityClass", "entityId"})}
 * )
 */
class SourceRecord extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Source", inversedBy="records")
     * @JoinColumn(nullable=false, onDelete="cascade")
     */
    protected Source $source;

    /**
     * @Column(nullable=true)
     */
    protected ?string $externalId = null;

    /**
     * @Column
     */
    protected string $entityClass;

    /**
     * @Column(type="integer")
     */
    protected int $entityId;

    public function getId()
    {
        return $this->id;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function setSource(Source $source)
    {
        $this->source = $source;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId = null)
    {
        $this->externalId = $externalId;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass)
    {
        $this->entityClass = $entityClass;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId)
    {
        $this->entityId = $entityId;
    }
}
