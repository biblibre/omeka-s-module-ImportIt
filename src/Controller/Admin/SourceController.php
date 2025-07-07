<?php

namespace ImportIt\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use ImportIt\Form\SourceAddForm;
use ImportIt\Form\SourceEditForm;
use ImportIt\Form\SourceImportForm;
use Omeka\Form\ConfirmForm;
use Omeka\Stdlib\Message;
use ImportIt\Job\Dispatcher;

class SourceController extends AbstractActionController
{
    protected Dispatcher $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function browseAction()
    {
        $this->browse()->setDefaults('importit_sources');

        $response = $this->api()->search('importit_sources', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());

        $sources = $response->getContent();

        $view = new ViewModel();
        $view->setVariable('sources', $sources);

        return $view;
    }

    public function addAction()
    {
        $form = $this->getForm(SourceAddForm::class);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();

                unset($data['csrf']);

                $response = $this->api($form)->create('importit_sources', $data);
                if ($response) {
                    $source = $response->getContent();
                    $this->messenger()->addSuccess('Source successfully created.'); // @translate
                    return $this->redirect()->toRoute('admin/importit/source-id', ['id' => $source->id(), 'action' => 'edit']);
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function editAction()
    {
        $id = $this->params('id');

        $source = $this->api()->read('importit_sources', $id)->getContent();

        $form = $this->getForm(SourceEditForm::class, ['source' => $source]);
        $form->setData([
            'o:name' => $source->name(),
            'o:settings' => $source->settings(),
        ]);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                unset($data['csrf']);
                $response = $this->api($form)->update('importit_sources', $id, $data, [], ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('Source successfully updated.'); // @translate
                    return $this->redirect()->toRoute('admin/importit/source');
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function deleteConfirmAction()
    {
        $resource = $this->api()->read('importit_sources', $this->params('id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $resource);
        $view->setVariable('resourceLabel', 'source'); // @translate
        $view->setVariable('partialPath', 'import-it/admin/source/show-details');
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('importit_sources', $this->params('id'));
                if ($response) {
                    $this->messenger()->addSuccess('Source successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        return $this->redirect()->toRoute('admin/importit/source', ['action' => 'browse']);
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('importit_sources', $this->params('id'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resource', $response->getContent());
        return $view;
    }

    public function importAction()
    {
        $form = $this->getForm(SourceImportForm::class);

        $source = $this->api()->read('importit_sources', $this->params('id'))->getContent();

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();

                $jobClass = $source->sourceType()->getImportJobClass();
                $args = [
                    'source_id' => $source->id(),
                    'delete_all_entities' => $formData['delete_all_entities'] ?? false,
                ];
                $job = $this->dispatcher->dispatch($jobClass, $args);

                $em = $source->getServiceLocator()->get('Omeka\EntityManager');
                $sourceEntity = $em->find('ImportIt\Entity\Source', $source->id());
                $sourceEntity->getJobs()->add($job);
                $em->flush();

                $message = new Message(
                    'Import started in a background job. %s', // @translate
                    sprintf(
                        '<a href="%s">%s</a>',
                        htmlspecialchars($this->url()->fromRoute('admin/importit/job-id', ['source-id' => $source->id(), 'job-id' => $job->getId()])),
                        $this->translate('See job details')
                    )
                );
                $message->setEscapeHtml(false);
                $this->messenger()->addSuccess($message);

                return $this->redirect()->toRoute('admin/importit/source');
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('source', $source);

        return $view;
    }
}
