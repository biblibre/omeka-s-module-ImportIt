<?php declare(strict_types=1);

namespace ImportIt\Form;

use Laminas\Form\Form;
use Laminas\Log\Logger;
use ImportIt\SourceType\SourceTypeManager;

class SourceAddForm extends Form
{
    protected Logger $logger;
    protected SourceTypeManager $sourceTypeManager;

    public function init()
    {
        $this->add([
            'name' => 'o:name',
            'type' => 'Laminas\Form\Element\Text',
            'options' => [
                'label' => 'Name', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:type',
            'type' => 'Laminas\Form\Element\Select',
            'options' => [
                'label' => 'Type', // @translate
                'value_options' => $this->getTypeValueOptions(),
                'empty_option' => '',
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public function setSourceTypeManager(SourceTypeManager $sourceTypeManager)
    {
        $this->sourceTypeManager = $sourceTypeManager;
    }

    public function getSourceTypeManager(): SourceTypeManager
    {
        return $this->sourceTypeManager;
    }

    protected function getTypeValueOptions(): array
    {
        $sourceTypeManager = $this->getSourceTypeManager();

        $sourceTypeNames = $sourceTypeManager->getRegisteredNames($sortAlpha = true);
        foreach ($sourceTypeNames as $sourceTypeName) {
            try {
                $sourceType = $sourceTypeManager->get($sourceTypeName);
                $sourceTypeValueOptions[$sourceTypeName] = $sourceType->getLabel();
            } catch (\Exception $e) {
                $this->getLogger()->err(sprintf('ImportIt: Failed to get source type "%s": %s', $sourceTypeName, $e->getMessage()));

                $sourceTypeValueOptions[$sourceTypeName] = [
                    'value' => $sourceTypeName,
                    'label' => sprintf('%s (disabled because of errors)', $sourceTypeName),
                    'disabled' => true,
                ];
            }
        }

        return $sourceTypeValueOptions;
    }
}
