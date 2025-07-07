<?php

namespace ImportIt\Validator;

use Laminas\Validator\AbstractValidator;

class DirectoryExists extends AbstractValidator
{
    public const ERR_NOT_EXISTS = 'not_exists';

    protected array $messageTemplates = [
        self::ERR_NOT_EXISTS => "'%value%' is not a directory",
    ];

    public function isValid(mixed $value): bool
    {
        $this->setValue($value);

        if (! is_dir($value)) {
            $this->error(self::ERR_NOT_EXISTS);
            return false;
        }

        // TODO Should also validate that $value is inside a list of "allowed directories" to avoid giving access to /

        return true;
    }
}
