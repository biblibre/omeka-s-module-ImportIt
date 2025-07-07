<?php

namespace ImportIt\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use ImportIt\Controller\Admin\SourceController;

class SourceControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $jobDispatcher = $services->get('ImportIt\Job\Dispatcher');

        $controller = new SourceController($jobDispatcher);

        return $controller;
    }
}
