<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests\TestClasses;

use Atk4\Data\ValidationException;
use PhilippR\Atk4\PluggableModel\BaseImplementation;

class ImplementationWithValidation extends BaseImplementation
{
    public static string $name = 'ImplementationWithValidation';

    public static function getFieldDefinitions(): array
    {
        return [
            'email' => ['type' => 'string'],
        ];
    }

    public static function getFieldValidations(): array
    {
        return [
            'email' => function (?string $value): void {
                if ($value !== null && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    throw new ValidationException(['email' => 'Invalid email address']);
                }
            },
        ];
    }
}
