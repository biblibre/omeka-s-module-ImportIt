<?php declare(strict_types=1);

namespace ImportIt;

use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Module\AbstractModule;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $services)
    {
        $connection = $services->get('Omeka\Connection');

        $connection->executeStatement(<<<SQL
            CREATE TABLE importit_source_record (
                id INT AUTO_INCREMENT NOT NULL,
                source_id INT NOT NULL,
                external_id VARCHAR(255) DEFAULT NULL,
                entity_class VARCHAR(255) NOT NULL,
                entity_id INT NOT NULL,
                INDEX IDX_2E0F557D953C1C61 (source_id),
                INDEX IDX_2E0F557D953C1C61772E836A (source_id, external_id),
                UNIQUE INDEX UNIQ_2E0F557D953C1C61772E836A41BF2C6681257D5D (source_id, external_id, entity_class, entity_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $connection->executeStatement(<<<SQL
            CREATE TABLE importit_source (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(255) NOT NULL,
                settings LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $connection->executeStatement(<<<SQL
            CREATE TABLE importit_source_job (
                source_id INT NOT NULL,
                job_id INT NOT NULL,
                INDEX IDX_CB850437953C1C61 (source_id),
                UNIQUE INDEX UNIQ_CB850437BE04EA9 (job_id),
                PRIMARY KEY(source_id, job_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $connection->executeStatement(<<<SQL
            ALTER TABLE importit_source_record
            ADD CONSTRAINT FK_2E0F557D953C1C61 FOREIGN KEY (source_id) REFERENCES importit_source (id) ON DELETE CASCADE
        SQL);

        $connection->executeStatement(<<<SQL
            ALTER TABLE importit_source_job
            ADD CONSTRAINT FK_CB850437953C1C61 FOREIGN KEY (source_id) REFERENCES importit_source (id) ON DELETE CASCADE
        SQL);

        $connection->executeStatement(<<<SQL
            ALTER TABLE importit_source_job
            ADD CONSTRAINT FK_CB850437BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id) ON DELETE CASCADE
        SQL);
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        $connection = $services->get('Omeka\Connection');

        $connection->executeStatement('DROP TABLE IF EXISTS importit_source_job');
        $connection->executeStatement('DROP TABLE IF EXISTS importit_source_record');
        $connection->executeStatement('DROP TABLE IF EXISTS importit_source');
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $services)
    {
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach('*', 'view.layout', [$this, 'onViewLayout']);

        $sharedEventManager->attach(
            '*',
            'view.advanced_search',
            [$this, 'onViewAdvancedSearch']
        );

        $sharedEventManager->attach(
            '*',
            'api.search.query',
            [$this, 'onApiSearchQuery']
        );

        $sharedEventManager->attach(
            '*',
            'view.search.filters',
            [$this, 'onViewSearchFilters']
        );
    }

    public function onViewLayout(Event $event)
    {
        $view = $event->getTarget();
        if ($view->status()->isAdminRequest()) {
            $view->headLink()->appendStylesheet($view->assetUrl('css/admin/importit.css', 'ImportIt'));
        }
    }

    public function onViewAdvancedSearch(Event $event)
    {
        $status = $this->getServiceLocator()->get('Omeka\Status');
        if (!$status->isAdminRequest()) {
            return;
        }

        $partials = $event->getParam('partials');

        $partials[] = 'import-it/common/advanced-search/source';

        $event->setParam('partials', $partials);
    }

    public function onApiSearchQuery(Event $event)
    {
        $status = $this->getServiceLocator()->get('Omeka\Status');
        if (!$status->isAdminRequest()) {
            return;
        }

        $adapter = $event->getTarget();
        $qb = $event->getParam('queryBuilder');
        $request = $event->getParam('request');

        $ids = $request->getValue('importit_source_id', []);
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $ids = array_filter($ids);
        if ($ids) {
            $entityClass = $adapter->getEntityClass();
            if ($entityClass === 'Omeka\Entity\Job') {
                $subQb = $adapter->getEntityManager()->createQueryBuilder();
                $subQb->select('j')
                      ->from('ImportIt\Entity\Source', 's')
                      ->innerJoin('s.jobs', 'j')
                      ->where($subQb->expr()->in('s.id', $ids))
                      ->andWhere('j = omeka_root');
            } else {
                $subQb = $adapter->getEntityManager()->createQueryBuilder();
                $subQb->select('sr')
                      ->from('ImportIt\Entity\SourceRecord', 'sr')
                      ->where($subQb->expr()->in('sr.source', $ids))
                      ->andWhere($subQb->expr()->eq('sr.entityClass', $subQb->expr()->literal($entityClass)))
                      ->andWhere('sr.entityId = omeka_root');
            }
            $qb->andWhere($qb->expr()->exists($subQb->getDQL()));
        }
    }

    public function onViewSearchFilters(Event $event)
    {
        $status = $this->getServiceLocator()->get('Omeka\Status');
        if (!$status->isAdminRequest()) {
            return;
        }

        $view = $event->getTarget();
        $query = $event->getParam('query');
        $filters = $event->getParam('filters');

        $ids = $query['importit_source_id'] ?? [];
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $ids = array_filter($ids);
        if ($ids) {
            $api = $this->getServiceLocator()->get('Omeka\ApiManager');
            $values = [];
            $sources = $api->search('importit_sources', ['id' => $ids])->getContent();
            $names = array_map(fn($source) => $source->name(), $sources);
            $filters[$view->translate('Source (Import It)')] = $names;
        }

        $event->setParam('filters', $filters);
    }
}
