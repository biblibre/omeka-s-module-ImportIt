<?php

namespace ImportIt\SourceType;

use ImportIt\Api\Representation\SourceRepresentation;
use Laminas\Form\FieldsetInterface;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\View\Renderer\PhpRenderer;

interface SourceTypeInterface
{
    public function getLabel(): string;

    public function getImportJobClass(): string;

    public function settingsFieldsetAddElements(FieldsetInterface $fieldset): void;
    public function settingsFieldsetAddInputFilters(InputFilterInterface $inputFilter): void;

    public function getSourceDescription(SourceRepresentation $source, PhpRenderer $renderer): string;
    public function getSourceDetails(SourceRepresentation $source, PhpRenderer $renderer): string;
}
