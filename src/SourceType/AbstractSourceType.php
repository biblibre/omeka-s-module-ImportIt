<?php

namespace ImportIt\SourceType;

abstract class AbstractSourceType implements SourceTypeInterface
{
    public function getSourceDescription(SourceRepresentation $source, PhpRenderer $renderer): string
    {
        return $source->name();
    }

    public function getSourceDetails(SourceRepresentation $source, PhpRenderer $renderer): string
    {
        return '';
    }
}
