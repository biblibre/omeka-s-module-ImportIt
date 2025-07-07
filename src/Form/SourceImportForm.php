<?php

namespace ImportIt\Form;

use Laminas\Form\Form;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Text;

class SourceImportForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'delete_all_entities',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Delete all entities before import', // @translate
                'info' => 'Delete all entities that were imported from this source before starting the new import.' // @translate
            ],
        ]);
    }
}
