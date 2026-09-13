<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests\TestClasses;

use PhilippR\Atk4\PluggableModel\BaseImplementation;

class Implementation1 extends BaseImplementation
{

    public static string $name = 'Implementation1';

    public static function getFieldDefinitions(): array
    {
        return [
            'field1' => ['type' => 'string'],
            'field2' => ['type' => 'integer'],
        ];
    }

    public function executeSomething(): void
    {
        $_ENV['TEST_ENV_VAR'] = Implementation1::class;
    }
}