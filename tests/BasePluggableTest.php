<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests;

use Atk4\Data\Persistence\Sql;
use Atk4\Data\Schema\TestCase;
use PhilippR\Atk4\PluggableModel\Tests\TestClasses\Implementation2;
use PhilippR\Atk4\PluggableModel\Tests\TestClasses\ModelWithPluggableTrait;

class BasePluggableTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new Sql('sqlite::memory:');
        $this->createMigrator(new ModelWithPluggableTrait($this->db))->create();
    }

    public function testFieldsFromImplementationClassAreAdded(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation2::class)
            ->save();

        self::assertTrue($entity->hasField('field3'));
        self::assertTrue($entity->hasField('field4'));
    }
}