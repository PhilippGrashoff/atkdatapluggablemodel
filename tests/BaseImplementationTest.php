<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests;

use PHPUnit\Framework\TestCase;
use PhilippR\Atk4\PluggableModel\Tests\TestClasses\Implementation1;
use PhilippR\Atk4\PluggableModel\Tests\TestClasses\ModelWithPluggableTrait;

class BaseImplementationTest extends TestCase
{

    public function testGetEntityReturnsEntityPassedToConstructor(): void
    {
        $entity = (new ModelWithPluggableTrait())->createEntity();
        $implementation = new Implementation1($entity);

        self::assertSame($entity, $implementation->getEntity());
    }

    public function testDefaultContainsManyDefinitionsAreEmpty(): void
    {
        self::assertSame([], Implementation1::getContainsManyDefinitions());
    }

    public function testDefaultFieldValidationsAreEmpty(): void
    {
        self::assertSame([], Implementation1::getFieldValidations());
    }

    public function testDefaultEncryptedFieldsAreEmpty(): void
    {
        self::assertSame([], Implementation1::getEncryptedFields());
    }
}
