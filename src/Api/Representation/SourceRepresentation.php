<?php declare(strict_types=1);

namespace ImportIt\Api\Representation;

use Laminas\Uri\Uri;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Api\Representation\JobRepresentation;

class SourceRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return [
            'o:name' => $this->name(),
            'o:type' => $this->type(),
        ];
    }

    public function getJsonLdType()
    {
        return 'o:ImportItSource';
    }

    public function adminUrl($action = null, $canonical = null)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/importit/source-id',
            [
                'controller' => $this->getControllerName(),
                'action' => $action,
                'id' => $this->id(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function name(): string
    {
        return $this->resource->getName();
    }

    public function type(): string
    {
        return $this->resource->getType();
    }

    public function settings(): array
    {
        return $this->resource->getSettings();
    }

    public function setting($name, $default = null)
    {
        $settings = $this->settings();

        if (!array_key_exists($name, $settings)) {
            return $default;
        }

        return $settings[$name];
    }

    public function sourceType(): \ImportIt\SourceType\SourceTypeInterface
    {
        $sourceTypeManager = $this->getServiceLocator()->get('ImportIt\SourceTypeManager');

        return $sourceTypeManager->get($this->type());
    }

    public function latestJob(): ?JobRepresentation
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $response = $api->search('jobs', [
            'importit_source_id' => $this->id(),
            'limit' => 1,
            'sort_by' => 'started',
            'sort_order' => 'desc',
        ]);

        $jobs = $response->getContent();
        $job = $jobs ? reset($jobs) : null;

        return $job;
    }

    public function logfilePath(int $jobId): string
    {
        return $this->getAdapter('importit_logs')->getPath($jobId);
    }
}
