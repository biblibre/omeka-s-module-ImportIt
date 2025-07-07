<?php
namespace ImportIt\Api\Adapter;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use ImportIt\Api\Representation\LogRepresentation;
use ImportIt\Api\Resource\Log;

class LogAdapter extends AbstractAdapter
{
    public function getResourceName()
    {
        return 'importit_logs';
    }

    public function getRepresentationClass()
    {
        return LogRepresentation::class;
    }

    public function search(Request $request)
    {
        $jobId = $request->getValue('job_id');

        if (!isset($jobId) || !is_numeric($jobId)) {
            throw new Exception\BadRequestException(
                $this->getTranslator()->translate('Missing required parameter "job_id"')
            );
        }

        $filepath = $this->getPath($jobId);
        if (!is_readable($filepath)) {
            $response = new Response([]);
            $response->setTotalResults(0);
            return $response;
        }

        // FIXME This may load the whole file in memory. This will be a problem
        // when the log file is big (close to or bigger than PHP memory limit)

        $fh = fopen($filepath, 'r');
        if ($fh === false) {
            throw new Exception\RuntimeException(sprintf(
                $this->getTranslator()->translate('Failed to open log file "%s"'),
                $filepath
            ));
        }

        $max_priority = $request->getValue('max_priority');
        $entries = [];
        while (false !== ($line = fgets($fh))) {
            $entry = json_decode($line, JSON_OBJECT_AS_ARRAY);
            if (!$entry) {
                continue;
            }

            [$timestamp, $priority, $priorityName, $message, $extra] = $entry;

            if ($max_priority && $max_priority < $priority) {
                continue;
            }

            $entries[] = $entry;
        }
        fclose($fh);

        $sort_by = $request->getValue('sort_by', '');
        $sort_order = $request->getValue('sort_order', 'asc');

        if ($sort_by === 'timestamp' && $sort_order === 'desc') {
            $entries = array_reverse($entries);
        }

        $totalResults = count($entries);

        $page = $request->getValue('page');
        if (is_numeric($page)) {
            $paginator = $this->getServiceLocator()->get('Omeka\Paginator');
            $paginator->setCurrentPage($page);
            $per_page = $request->getValue('per_page');
            if (is_numeric($per_page)) {
                $paginator->setPerPage($per_page);
            }
            $limit = $paginator->getPerPage();
            $offset = $paginator->getOffset();
        } else {
            $limit = $request->getValue('limit');
            $offset = $request->getValue('offset', 0);
        }

        if ($limit) {
            $entries = array_slice($entries, $offset, $limit);
        }

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $job = $em->find('Omeka\Entity\Job', $jobId);

        $logs = [];
        $i = 0;
        foreach ($entries as $entry) {
            [$timestamp, $priority, $priorityName, $message, $extra] = $entry;

            $log = new Log;
            $log->setJob($job);
            $log->setTimestamp(DateTime::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $timestamp));
            $log->setPriority($priority);
            $log->setPriorityName($priorityName);
            $log->setMessage($message);
            $log->setExtra($extra);
            $logs[] = $log;
        }

        $response = new Response($logs);
        $response->setTotalResults($totalResults);
        return $response;
    }

    public function create(Request $request)
    {
        $jobId = $request->getValue('job_id');

        if (!isset($jobId) || !is_numeric($jobId)) {
            throw new Exception\BadRequestException(
                $this->getTranslator()->translate('Missing required parameter "job_id"')
            );
        }

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $job = $em->find('Omeka\Entity\Job', $jobId);
        if (!$job) {
            throw new Exception\BadRequestException(sprintf(
                $this->getTranslator()->translate('Job #%d does not exist'),
                $jobId
            ));
        }

        $filepath = $this->getPath($jobId);
        $fh = fopen($filepath, 'a');
        if ($fh === false) {
            throw new Exception\RuntimeException(sprintf(
                $this->getTranslator()->translate('Failed to open log file "%s"'),
                $filepath
            ));
        }

        $timestamp = $request->getValue('timestamp');
        $priority = $request->getValue('priority');
        $priorityName = $request->getValue('priorityName');
        $message = $request->getValue('message');
        $extra = $request->getValue('extra');

        $data = [
            $timestamp->format(DateTimeInterface::RFC3339_EXTENDED),
            $priority,
            $priorityName,
            $message,
            $extra,
        ];

        fwrite($fh, sprintf("%s\n", json_encode($data)));
        fclose($fh);

        $log = new Log;
        $log->setJob($job);
        $log->setTimestamp($timestamp);
        $log->setPriority($priority);
        $log->setPriorityName($priorityName);
        $log->setMessage($message);
        $log->setExtra($extra);

        $response = new Response($log);

        return $response;
    }

    public function getPath(int $jobId)
    {
        $config = $this->getServiceLocator()->get('Config');
        $dir = $config['importit_logger']['dir'] ?? OMEKA_PATH . '/logs/importit';
        $dir = rtrim($dir, '/');
        if (!is_dir($dir)) {
            if (false === mkdir($dir, 0777, true)) {
                throw new Exception\RuntimeException(sprintf(
                    $this->getTranslator()->translate('Failed to create directory "%s"'),
                    $dir
                ));
            }
        }

        $filepath = sprintf('%s/importit-job-%d.jsonl', $dir, $jobId);

        return $filepath;
    }
}
