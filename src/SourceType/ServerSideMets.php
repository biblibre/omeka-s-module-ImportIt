<?php

namespace ImportIt\SourceType;

use ImportIt\Api\Representation\SourceRepresentation;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Settings\Settings;

class ServerSideMets implements SourceTypeInterface
{
    public function __construct(protected Settings $settings)
    {
    }

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

        $fieldset->add([
            'name' => 'resource_visibility',
            'type' => 'Laminas\Form\Element\Select',
            'options' => [
                'label' => 'Visibility of created resources', // @translate
                'value_options' => [
                    'public' => 'Public', // @translate
                    'private' => 'Private', // @translate
                ],
                'empty_option' => $this->settings->get('default_to_private', false) ?
                    'Use global setting (private)' : // @translate
                    'Use global setting (public)', // @translate
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

        $inputFilter->add([
            'name' => 'resource_visibility',
            'allow_empty' => true,
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
