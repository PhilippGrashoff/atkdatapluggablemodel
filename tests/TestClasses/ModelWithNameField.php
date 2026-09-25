<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests\TestClasses;

use Atk4\Data\Model;
use PhilippR\Atk4\PluggableModel\PluggableModelTrait;

/**
 * The `name` field is not added by the model itself but by the trait via $addNameField = true
 */
class ModelWithNameField extends Model
{
    use PluggableModelTrait;

    public $table = 'model_with_name';

    protected function init(): void
    {
        parent::init();
        $this->addPluggableFieldsAndHooks(addNameField: true);
    }

    protected function getAvailableImplementations(): array
    {
        return [
            Implementation1::class => Implementation1::$name,
            Implementation2::class => Implementation2::$name,
        ];
    }
}
