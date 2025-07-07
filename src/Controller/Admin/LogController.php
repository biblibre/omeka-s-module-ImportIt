<?php

namespace ImportIt\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Form\ConfirmForm;

class LogController extends AbstractActionController
{
    public function browseAction()
    {
        $this->browse()->setDefaults('importit_logs');

        $source = $this->api()->read('importit_sources', $this->params('source-id'))->getContent();
        $job = $this->api()->read('jobs', $this->params('job-id'))->getContent();

        $query = $this->params()->fromQuery();
        $query['job_id'] = $job->id();

        $response = $this->api()->search('importit_logs', $query);
        $this->paginator($response->getTotalResults());

        $logs = $response->getContent();

        $view = new ViewModel();
        $view->setVariable('source', $source);
        $view->setVariable('job', $job);
        $view->setVariable('logs', $logs);

        return $view;
    }

    public function downloadAction()
    {
        $format = $this->params('format', '');

        if (!in_array($format, ['jsonl', 'txt'])) {
            return $this->redirect()->toRoute('admin/importit/log', [], [], true);
        }

        $source = $this->api()->read('importit_sources', $this->params('source-id'))->getContent();
        $job = $this->api()->read('jobs', $this->params('job-id'))->getContent();

        $response = $this->getResponse();

        if ($format === 'jsonl') {
            $logfilePath = $source->logfilePath($job->id());
            if (!is_readable($logfilePath)) {
                throw new \Omeka\Mvc\Exception\NotFoundException();
            }

            $response->getHeaders()->addHeaderLine('Content-Length', filesize($logfilePath));
            $response->getHeaders()->addHeaderLine('Content-Type', 'application/jsonl');
            $response->getHeaders()->addHeaderLine('Content-Disposition', sprintf('attachment; filename="%s"', basename($logfilePath)));

            $response->setContent(file_get_contents($logfilePath));
        } elseif ($format === 'txt') {
            $logs = $this->api()->search('importit_logs', ['job_id' => $job->id()])->getContent();
            $content = implode("\n", $logs);

            $response->getHeaders()->addHeaderLine('Content-Length', strlen($content));
            $response->getHeaders()->addHeaderLine('Content-Type', 'text/plain');
            $response->getHeaders()->addHeaderLine('Content-Disposition', sprintf('attachment; filename="importit-job-%d.txt"', $job->id()));

            $response->setContent($content);
        }

        return $response;
    }
}
