<?php
namespace ImportIt\Log\Writer;

use Laminas\Log\Writer\AbstractWriter;
use Omeka\Api\Manager as ApiManager;

class ImportLogWriter extends AbstractWriter
{
    protected int $jobId;
    protected ApiManager $api;

    public function setApiManager(ApiManager $api)
    {
        $this->api = $api;
    }

    public function setJobId(int $jobId)
    {
        $this->jobId = $jobId;
    }

    protected function doWrite(array $event)
    {
        $data = array_merge($event, ['job_id' => $this->jobId]);
        $this->api->create('importit_logs', $data);
    }
}
