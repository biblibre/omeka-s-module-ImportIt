<?php

namespace ImportIt\Api\Resource;

use Omeka\Api\ResourceInterface;
use Omeka\Entity\Job;
use DateTime;
use DateTimeZone;

class Log implements ResourceInterface
{
    protected $id;
    protected $job;
    protected $timestamp;
    protected $priority;
    protected $priorityName;
    protected $message;
    protected $extra;

    public function __construct()
    {
        $this->id = '';
    }

    public function getId()
    {
        return $this->id;
    }

    public function getJob()
    {
        return $this->job;
    }

    public function setJob(Job $job)
    {
        $this->job = $job;
    }

    public function setTimestamp(DateTime $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setPriority(int $priority)
    {
        $this->priority = $priority;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriorityName(string $priorityName)
    {
        $this->priorityName = $priorityName;
    }

    public function getPriorityName()
    {
        return $this->priorityName;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setExtra(array $extra)
    {
        $this->extra = $extra;
    }

    public function getExtra()
    {
        return $this->extra;
    }
}
