<?php
namespace ImportIt\Service\SourceType;

use ImportIt\SourceType\ServerSideMets;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ServerSideMetsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $settings = $serviceLocator->get('Omeka\Settings');

        $sourceType = new ServerSideMets($settings);

        return $sourceType;
    }
}
