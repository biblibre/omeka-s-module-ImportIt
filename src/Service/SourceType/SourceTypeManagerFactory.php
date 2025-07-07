<?php
namespace ImportIt\Service\SourceType;

use ImportIt\SourceType\SourceTypeManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SourceTypeManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');

        return new SourceTypeManager($serviceLocator, $config['importit_source_types'] ?? []);
    }
}
