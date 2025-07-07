<?php

namespace ImportIt\SourceType;

use ImportIt\Api\Representation\SourceRepresentation;
use Laminas\View\Renderer\PhpRenderer;

class ServerSideMets implements SourceTypeInterface
{
    public function getLabel(): string
    {
        return 'Server-side METS'; // @translate
    }

    public function getImportJobClass(): string
    {
        return 'ImportIt\Job\ServerSideMets';
    }

    public function settingsFieldsetAddElements(\Laminas\Form\FieldsetInterface $fieldset): void
    {
        $fieldset->add([
            'name' => 'path',
            'type' => 'Laminas\Form\Element\Text',
            'options' => [
                'label' => 'Path', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
    }

    public function settingsFieldsetAddInputFilters(\Laminas\InputFilter\InputFilterInterface $inputFilter): void
    {
        $inputFilter->add([
            'name' => 'path',
            'validators' => [
                [
                    'name' => 'ImportIt\Validator\DirectoryExists',
                ],
            ],
        ]);
    }

    public function getSourceDescription(SourceRepresentation $source, PhpRenderer $renderer): string
    {
        return $renderer->partial('import-it/common/source-type/server-side-mets/source-description', ['source' => $source]);
    }

    public function getSourceDetails(SourceRepresentation $source, PhpRenderer $renderer): string
    {
        return $renderer->partial('import-it/common/source-type/server-side-mets/source-details', ['source' => $source]);
    }
}
