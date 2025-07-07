<?php

namespace ImportIt\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use ImportIt\Form\SourceAddForm;

class SourceAddFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $logger = $services->get('Omeka\Logger');
        $sourceTypeManager = $services->get('ImportIt\SourceTypeManager');

        $form = new SourceAddForm(null, $options ?? []);

        $form->setLogger($logger);
        $form->setSourceTypeManager($sourceTypeManager);

        return $form;
    }
}
