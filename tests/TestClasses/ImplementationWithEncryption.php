<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests\TestClasses;

use PhilippR\Atk4\PluggableModel\BaseImplementation;

class ImplementationWithEncryption extends BaseImplementation
{
    public static string $name = 'ImplementationWithEncryption';

    public static function getFieldDefinitions(): array
    {
        return [
            'secret' => ['type' => 'string'],
            'note' => ['type' => 'string'],
        ];
    }

    public static function getEncryptedFields(): array
    {
        return ['secret'];
    }
}
