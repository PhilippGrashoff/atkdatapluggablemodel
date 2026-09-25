<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests\TestClasses;

use Atk4\Data\Model;
use PhilippR\Atk4\PluggableModel\PluggableModelTrait;

/**
 * Neither the model nor the trait adds a `name` field
 */
class ModelWithoutNameField extends Model
{
    use PluggableModelTrait;

    public $table = 'model_without_name';

    protected function init(): void
    {
        parent::init();
        $this->addPluggableFieldsAndHooks();
    }

    protected function getAvailableImplementations(): array
    {
        return [
            Implementation1::class => Implementation1::$name,
            Implementation2::class => Implementation2::$name,
        ];
    }
}
