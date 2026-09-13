<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests\TestClasses;

use PhilippR\Atk4\PluggableModel\BaseImplementation;

class ImplementationWithContainsMany extends BaseImplementation
{
    public static string $name = 'ImplementationWithContainsMany';

    public static function getFieldDefinitions(): array
    {
        return [
            'title' => ['type' => 'string'],
        ];
    }

    public static function getContainsManyDefinitions(): array
    {
        return [
            'items' => ['model' => [ContainsManyItemModel::class]],
        ];
    }
}
