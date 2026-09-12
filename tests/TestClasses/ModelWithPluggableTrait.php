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
        $this->addPluggableFields();
        $this->addPluggableHooks();
    }

    protected function getAvailableImplementations(): array
    {
        return [
            Pluggable1::class,
            Pluggable2::class,
        ];
    }
}