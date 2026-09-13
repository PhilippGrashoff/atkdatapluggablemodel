<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests\TestClasses;

use Atk4\Data\Model;
use PhilippR\Atk4\PluggableModel\PluggableModelTrait;

class ModelWithExtras extends Model
{
    use PluggableModelTrait;

    public const string ENCRYPTION_PREFIX = 'encrypted:';

    public $table = 'model_with_extras';

    protected function init(): void
    {
        parent::init();
        $this->addField('name');
        $this->addPluggableFieldsAndHooks();
    }

    protected function getAvailableImplementations(): array
    {
        return [
            ImplementationWithValidationAndEncryption::class => ImplementationWithValidationAndEncryption::$name,
            ImplementationWithContainsMany::class => ImplementationWithContainsMany::$name,
        ];
    }

    protected function encryptFieldValue(string $fieldName): void
    {
        $value = $this->get($fieldName);
        if ($value === null) {
            return;
        }
        $this->set($fieldName, self::ENCRYPTION_PREFIX . base64_encode($value));
    }

    protected function decryptFieldValue(string $fieldName): void
    {
        $value = $this->get($fieldName);
        if ($value === null || !str_starts_with($value, self::ENCRYPTION_PREFIX)) {
            return;
        }
        $this->set($fieldName, base64_decode(substr($value, strlen(self::ENCRYPTION_PREFIX))));
    }
}
