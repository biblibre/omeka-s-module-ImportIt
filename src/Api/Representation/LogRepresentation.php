<?php
namespace ImportIt\Api\Representation;

use DateTimeInterface;
use Omeka\Api\Representation\AbstractResourceRepresentation;

class LogRepresentation extends AbstractResourceRepresentation
{
    public function getJsonLd()
    {
        return [
            'job' => $this->job()->getReference(),
            'timestamp' => [
                '@value' => $this->getDateTime($this->timestamp()),
                '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ],
            'priority' => $this->priority(),
            'priorityName' => $this->priorityName(),
            'message' => $this->message(),
            'extra' => $this->extra(),
        ];
    }

    public function getJsonLdType()
    {
        return 'o:ImportItLog';
    }

    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation($this->resource->getJob());
    }

    public function timestamp()
    {
        return $this->resource->getTimestamp();
    }

    public function priority()
    {
        return $this->resource->getPriority();
    }

    public function priorityName()
    {
        return $this->resource->getPriorityName();
    }

    public function message()
    {
        return $this->resource->getMessage();
    }

    public function extra()
    {
        return $this->resource->getExtra();
    }

    public function __toString()
    {
        return sprintf(
            '%s [% 6s] %s%s',
            $this->timestamp()->format(DateTimeInterface::RFC3339_EXTENDED),
            $this->priorityName(),
            $this->message(),
            $this->extra() ? sprintf(' %s', json_encode($this->extra())) : ''
        );
    }
}
