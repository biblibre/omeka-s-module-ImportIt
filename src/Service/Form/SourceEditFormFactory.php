<?php declare(strict_types=1);

namespace ImportIt\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use ImportIt\Form\SourceEditForm;

class SourceEditFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $sourceTypeManager = $services->get('ImportIt\SourceTypeManager');

        $form = new SourceEditForm(null, $options ?? []);
        $form->setSourceTypeManager($sourceTypeManager);

        return $form;
    }
}
