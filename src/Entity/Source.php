<?php

namespace ImportIt\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Omeka\Entity\AbstractEntity;

/**
 * @Entity
 * @Table(
 *     name="importit_source",
 * )
 */
class Source extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column
     */
    protected string $name;

    /**
     * @Column
     */
    protected string $type;

    /**
     * @Column(type="json")
     */
    protected array $settings = [];

    /**
     * @OneToMany(targetEntity="SourceRecord", mappedBy="source")
     */
    protected Collection $records;

    /**
     * @ManyToMany(targetEntity="Omeka\Entity\Job")
     * @JoinTable(
     *     name="importit_source_job",
     *     joinColumns={@JoinColumn(name="source_id", referencedColumnName="id", onDelete="cascade")},
     *     inverseJoinColumns={@JoinColumn(name="job_id", referencedColumnName="id", unique=true, onDelete="cascade")}
     * )
     */
    protected Collection $jobs;

    public function __construct()
    {
        $this->records = new ArrayCollection();
        $this->jobs = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    public function getRecords(): Collection
    {
        return $this->records;
    }

    public function getJobs(): Collection
    {
        return $this->jobs;
    }
}
