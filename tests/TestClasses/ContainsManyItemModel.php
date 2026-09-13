<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests\TestClasses;

use Atk4\Data\Model;

class ContainsManyItemModel extends Model
{
    protected function init(): void
    {
        parent::init();
        $this->addField('name');
    }
}
