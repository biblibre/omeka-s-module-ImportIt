<?php

namespace ImportIt\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Form\ConfirmForm;

class JobController extends AbstractActionController
{
    public function browseAction()
    {
        $this->browse()->setDefaults('jobs');

        $source = $this->api()->read('importit_sources', $this->params('source-id'))->getContent();

        $query = $this->params()->fromQuery();
        $query['importit_source_id'] = $source->id();

        $response = $this->api()->search('jobs', $query);
        $this->paginator($response->getTotalResults());

        $jobs = $response->getContent();

        $view = new ViewModel();
        $view->setVariable('source', $source);
        $view->setVariable('jobs', $jobs);

        return $view;
    }

    public function showAction()
    {
        $source = $this->api()->read('importit_sources', $this->params('source-id'))->getContent();
        $job = $this->api()->read('jobs', $this->params('job-id'))->getContent();

        $confirmForm = $this->getForm(ConfirmForm::class);
        $confirmForm->setAttribute('action', $job->url('stop'));
        $confirmForm->setButtonLabel('Attempt Stop'); // @translate

        $view = new ViewModel();
        $view->setVariable('source', $source);
        $view->setVariable('job', $job);
        $view->setVariable('confirmForm', $confirmForm);

        return $view;
    }
}
