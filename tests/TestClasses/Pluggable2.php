<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests\TestClasses;

use PhilippR\Atk4\PluggableModel\BasePluggable;

class Pluggable2 extends BasePluggable
{

    public static function getFieldDefinitions(): array
    {
        return [
            'field3' => ['type' => 'string'],
            'field4' => ['type' => 'integer'],
        ];
    }

    public function executeSomething(): void
    {
        $_ENV['TEST_ENV_VAR'] = Pluggable2::class;
    }
}