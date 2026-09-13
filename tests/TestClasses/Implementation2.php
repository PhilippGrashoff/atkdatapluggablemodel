<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests\TestClasses;

use PhilippR\Atk4\PluggableModel\BaseImplementation;

class Implementation2 extends BaseImplementation
{
    public static string $name = 'Implementation2';

    public static function getFieldDefinitions(): array
    {
        return [
            'field3' => ['type' => 'string'],
            'field4' => ['type' => 'integer'],
        ];
    }

    public function executeSomething(): void
    {
        $_ENV['TEST_ENV_VAR'] = Implementation2::class;
    }
}