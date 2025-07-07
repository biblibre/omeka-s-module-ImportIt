#!/usr/bin/php
<?php

require dirname(__DIR__, 3) . '/bootstrap.php';

$application = Omeka\Mvc\Application::init(require OMEKA_PATH . '/application/config/application.config.php');
$serviceLocator = $application->getServiceManager();
$em = $serviceLocator->get('Omeka\EntityManager');
$api = $serviceLocator->get('Omeka\ApiManager');
$jobDispatcher = $serviceLocator->get('ImportIt\Job\Dispatcher');
$dispatchStrategy = $serviceLocator->get('Omeka\Job\DispatchStrategy\Synchronous');
$authentication = $serviceLocator->get('Omeka\AuthenticationService');

$options = getopt(null, ['user-id:', 'source-id:', 'job-args:', 'help']);
if (isset($options['help'])) {
    echo help();
    exit;
}

if (!isset($options['user-id'])) {
    fwrite(STDERR, "No user-id given; use --user-id\n");
    fwrite(STDERR, help());
    exit(1);
}

if (!isset($options['source-id'])) {
    fwrite("No source ID given; use --source-id\n");
    fwrite(STDERR, help());
    exit(1);
}

$jobArgs = [];
if (isset($options['job-args'])) {
    $jobArgs = json_decode($options['job-args'], true);
    if (null === $jobArgs) {
        fwrite(STDERR, sprintf("JSON cannot be decoded: %s\n", $options['job-args']));
        exit(1);
    }
}

$userId = $options['user-id'];
$user = $em->find('Omeka\Entity\User', $userId);
if (!$user) {
    fwrite(STDERR, sprintf("User %d does not exist\n", $userId));
    exit(1);
}
$authentication->getStorage()->write($user);

$logger = $serviceLocator->get('ImportIt\Logger');
$writer = new \Zend\Log\Writer\Stream('php://stderr');
$logger->addWriter($writer);

try {
    $source = $api->read('importit_sources', $options['source-id'])->getContent();
    $sourceType = $source->sourceType();
    $jobClass = $sourceType->getImportJobClass();
    $jobArgs['source_id'] = $source->id();

    $job = new \Omeka\Entity\Job;
    $job->setStatus(\Omeka\Entity\Job::STATUS_STARTING);
    $job->setClass($jobClass);
    $job->setArgs($jobArgs);
    $job->setOwner($user);
    $em->persist($job);
    $sourceEntity = $em->find('ImportIt\Entity\Source', $source->id());
    $sourceEntity->getJobs()->add($job);
    $em->flush();

    $job = $jobDispatcher->send($job, $dispatchStrategy);
} catch (\Exception $e) {
    $logger->err($e->getMessage());
}

function help()
{
    return <<<'HELP'
job-start --user-id <user-id> --source-id <source-id> [--job-args <job-args>]
job-start --help

Options:
    --user-id <user-id>
        Required. Authenticate with this user before starting the job

    --source-id <source-id>
        Required. ID of the source to import

    --job-args
        Optional. Job arguments in JSON

    --help
        Display this help

HELP;
}
