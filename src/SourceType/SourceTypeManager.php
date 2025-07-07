<?php

namespace ImportIt\SourceType;

use Omeka\ServiceManager\AbstractPluginManager;

class SourceTypeManager extends AbstractPluginManager
{
    protected $instanceOf = SourceTypeInterface::class;
}
