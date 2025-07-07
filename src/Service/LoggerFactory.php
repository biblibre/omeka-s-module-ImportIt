<?php
namespace ImportIt\Service;

use Interop\Container\ContainerInterface;
use Laminas\Log\Logger;
use Laminas\ServiceManager\Factory\FactoryInterface;

class LoggerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $logger = $serviceLocator->get('Omeka\Logger');

        $logger->setWriters(new \Laminas\Stdlib\SplPriorityQueue());

        return $logger;
    }
}
