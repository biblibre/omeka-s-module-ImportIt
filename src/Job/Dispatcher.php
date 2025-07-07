<?php
namespace ImportIt\Job;

use DateTime;
use Omeka\Job\DispatchStrategy\StrategyInterface;
use Omeka\Entity\Job;

class Dispatcher extends \Omeka\Job\Dispatcher
{
    /**
     * Send a job via a strategy.
     *
     * @param Job $job
     * @param StrategyInterface $strategy
     *
     * Identical to Omeka default job dispatcher except for one thing:
     * it does not add the JobWriter to the logger
     */
    public function send(Job $job, StrategyInterface $strategy)
    {
        try {
            $strategy->send($job);
        } catch (\Exception $e) {
            $this->logger->err((string) $e);
            $job->setStatus(Job::STATUS_ERROR);
            $job->setEnded(new DateTime('now'));

            // Account for "inside Doctrine" errors that close the EM
            if ($this->entityManager->isOpen()) {
                $entityManager = $this->entityManager;
            } else {
                $entityManager = $this->getNewEntityManager($this->entityManager);
            }

            $entityManager->clear();
            $entityManager->merge($job);
            $entityManager->flush();
        }
    }
}
