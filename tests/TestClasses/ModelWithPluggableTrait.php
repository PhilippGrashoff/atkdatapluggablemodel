<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests\TestClasses;

use Atk4\Data\Model;
use PhilippR\Atk4\PluggableModel\PluggableModelTrait;
use TestClasses\Pluggable1;
use TestClasses\Pluggable2;

class ModelWithPluggableTrait extends Model
{

    use PluggableModelTrait;

    public $table = 'model1';

    protected function init(): void
    {
        parent::init();
        $this->addField('name');
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