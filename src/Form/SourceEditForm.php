<?php

namespace ImportIt\Form;

use ImportIt\SourceType\SourceTypeManager;
use Laminas\Form\Form;

class SourceEditForm extends Form
{
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
                'id' => 'name',
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:settings',
            'type' => 'Laminas\Form\Fieldset',
        ]);
        $settingsFieldset = $this->get('o:settings');

        $source = $this->getOption('source');
        if ($source) {
            $sourceType = $source->sourceType();
            $sourceType->settingsFieldsetAddElements($settingsFieldset);

            $inputFilter = $this->getInputFilter();
            $sourceType->settingsFieldsetAddInputFilters($inputFilter->get('o:settings'));
        }

    }

    public function setSourceTypeManager(SourceTypeManager $sourceTypeManager)
    {
        $this->sourceTypeManager = $sourceTypeManager;
    }

    public function getSourceTypeManager(): SourceTypeManager
    {
        return $this->sourceTypeManager;
    }
}
