<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel\Tests;

use Atk4\Data\Exception;
use Atk4\Data\Persistence\Sql;
use Atk4\Data\Schema\TestCase;
use Atk4\Data\ValidationException;
use PhilippR\Atk4\PluggableModel\Tests\TestClasses\Implementation1;
use PhilippR\Atk4\PluggableModel\Tests\TestClasses\Implementation2;
use PhilippR\Atk4\PluggableModel\Tests\TestClasses\ImplementationWithContainsMany;
use PhilippR\Atk4\PluggableModel\Tests\TestClasses\ImplementationWithValidationAndEncryption;
use PhilippR\Atk4\PluggableModel\Tests\TestClasses\ModelWithExtras;
use PhilippR\Atk4\PluggableModel\Tests\TestClasses\ModelWithPluggableTrait;
use TypeError;

class PluggableModelTraitTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new Sql('sqlite::memory:');
        $this->createMigrator(new ModelWithPluggableTrait($this->db))->create();

        // the containsMany field is added dynamically at runtime, but its column must exist in the table
        $modelWithExtras = new ModelWithExtras($this->db);
        $modelWithExtras->addField('items', ['type' => 'json']);
        $this->createMigrator($modelWithExtras)->create();
    }

    public function testPluggableFieldsAreAdded(): void
    {
        $model = new ModelWithPluggableTrait($this->db);

        self::assertTrue($model->hasField('implementation_class'));
        self::assertTrue($model->hasField('implementation_class_name'));
        self::assertTrue($model->hasField('data'));

        self::assertTrue($model->getField('implementation_class')->system);
        self::assertTrue($model->getField('implementation_class_name')->system);
        self::assertTrue($model->getField('data')->system);
        self::assertSame('json', $model->getField('data')->type);
    }

    public function testImplementationClassFieldUsesAvailableImplementationsAsValues(): void
    {
        $model = new ModelWithPluggableTrait($this->db);

        self::assertSame(
            [
                Implementation1::class => Implementation1::$name,
                Implementation2::class => Implementation2::$name,
            ],
            $model->getField('implementation_class')->values
        );
    }

    public function testDataFieldDefaultsToEmptyArray(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity();

        self::assertSame([], $entity->get('data'));
    }

    public function testFieldsFromImplementationClassAreAdded(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation2::class)
            ->save();

        self::assertTrue($entity->hasField('field3'));
        self::assertTrue($entity->hasField('field4'));
    }

    public function testFieldsFromOtherImplementationClassAreNotAdded(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();

        self::assertTrue($entity->hasField('field1'));
        self::assertTrue($entity->hasField('field2'));
        self::assertFalse($entity->hasField('field3'));
        self::assertFalse($entity->hasField('field4'));
    }

    public function testFieldsFromImplementationClassAreNeverPersistedAndEditable(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();

        self::assertTrue($entity->getField('field1')->neverPersist);
        self::assertTrue($entity->getField('field2')->neverPersist);
        self::assertTrue($entity->getField('field1')->ui['editable']);
        self::assertSame('string', $entity->getField('field1')->type);
        self::assertSame('integer', $entity->getField('field2')->type);
    }

    public function testNoFieldsAreAddedWithoutImplementationClass(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('name', 'some name')
            ->save();

        self::assertNull($entity->get('implementation_class'));
        self::assertNull($entity->get('implementation_class_name'));
        self::assertSame([], $entity->get('data'));
        self::assertFalse($entity->hasField('field1'));
        self::assertFalse($entity->hasField('field3'));
    }

    public function testNoFieldsAreAddedIfAddDynamicFieldsIsDisabled(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db, ['addDynamicFields' => false]))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();

        self::assertFalse($entity->hasField('field1'));
        self::assertFalse($entity->hasField('field2'));
        // implementation_class_name is set independently of dynamic fields
        self::assertSame(Implementation1::$name, $entity->get('implementation_class_name'));
    }

    public function testImplementationClassNameIsSetOnSave(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation2::class)
            ->save();

        self::assertSame(Implementation2::$name, $entity->get('implementation_class_name'));

        $loaded = (new ModelWithPluggableTrait($this->db))->load($entity->getId());
        self::assertSame(Implementation2::$name, $loaded->get('implementation_class_name'));
    }

    public function testDataIsNotWrittenOnSaveThatSetsImplementationClass(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();

        self::assertSame([], $entity->get('data'));
    }

    public function testImplementationFieldValuesAreStoredInDataAndRestoredOnLoad(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();

        $entity->set('field1', 'some text')
            ->set('field2', 42)
            ->save();

        self::assertSame('some text', $entity->get('data')['field1']);
        self::assertEquals(42, $entity->get('data')['field2']);

        $loaded = (new ModelWithPluggableTrait($this->db))->load($entity->getId());
        self::assertSame('some text', $loaded->get('field1'));
        self::assertSame(42, $loaded->get('field2'));
    }

    public function testImplementationFieldsAreNotDirtyAfterLoad(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();
        $entity->set('field1', 'some text')
            ->set('field2', 42)
            ->save();

        $loaded = (new ModelWithPluggableTrait($this->db))->load($entity->getId());

        self::assertFalse($loaded->isDirty('field1'));
        self::assertFalse($loaded->isDirty('field2'));
        self::assertFalse($loaded->isDirty('data'));
    }

    public function testImplementationFieldValuesCanBeUpdated(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();
        $entity->set('field1', 'first')->save();
        $entity->set('field1', 'second')->save();

        $loaded = (new ModelWithPluggableTrait($this->db))->load($entity->getId());
        self::assertSame('second', $loaded->get('field1'));
        self::assertSame('second', $loaded->get('data')['field1']);
    }

    public function testUnsetImplementationFieldValuesAreStoredAsNull(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();
        $entity->set('field1', 'only field1')->save();

        $loaded = (new ModelWithPluggableTrait($this->db))->load($entity->getId());
        self::assertSame('only field1', $loaded->get('field1'));
        self::assertNull($loaded->get('field2'));
        self::assertArrayHasKey('field2', $loaded->get('data'));
        self::assertNull($loaded->get('data')['field2']);
    }

    public function testImplementationClassIsReadOnlyAfterLoad(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();

        self::assertTrue($entity->getField('implementation_class')->readOnly);
    }

    public function testImplementationClassCannotBeChangedAfterLoad(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();

        $this->expectException(Exception::class);
        $entity->set('implementation_class', Implementation2::class);
    }

    public function testImplementationClassIsNotReadOnlyIfNotSet(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('name', 'no implementation')
            ->save();

        self::assertFalse($entity->getField('implementation_class')->readOnly);
    }

    public function testGetImplementationReturnsInstanceOfImplementationClass(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation2::class)
            ->save();

        $implementation = $entity->getImplementation();

        self::assertInstanceOf(Implementation2::class, $implementation);
        self::assertSame($entity, $implementation->getEntity());
    }

    public function testGetImplementationReturnsSameInstanceOnSubsequentCalls(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();

        self::assertSame($entity->getImplementation(), $entity->getImplementation());
    }

    public function testGetImplementationWorksOnUnsavedEntity(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class);

        self::assertInstanceOf(Implementation1::class, $entity->getImplementation());
    }

    public function testGetImplementationThrowsExceptionWithoutImplementationClass(): void
    {
        $entity = (new ModelWithPluggableTrait($this->db))->createEntity();

        $this->expectException(Exception::class);
        $entity->getImplementation();
    }

    public function testGetImplementationThrowsExceptionOnModel(): void
    {
        $model = new ModelWithPluggableTrait($this->db);

        $this->expectException(TypeError::class);
        $model->getImplementation();
    }

    public function testImplementationSpecificCodeCanBeExecuted(): void
    {
        unset($_ENV['TEST_ENV_VAR']);

        $entity1 = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();
        $entity1->getImplementation()->executeSomething();
        self::assertSame(Implementation1::class, $_ENV['TEST_ENV_VAR']);

        $entity2 = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation2::class)
            ->save();
        $entity2->getImplementation()->executeSomething();
        self::assertSame(Implementation2::class, $_ENV['TEST_ENV_VAR']);

        unset($_ENV['TEST_ENV_VAR']);
    }

    public function testDifferentImplementationsCanBeUsedInSameTable(): void
    {
        $entity1 = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation1::class)
            ->save();
        $entity1->set('field1', 'value for implementation 1')->save();

        $entity2 = (new ModelWithPluggableTrait($this->db))->createEntity()
            ->set('implementation_class', Implementation2::class)
            ->save();
        $entity2->set('field3', 'value for implementation 2')->save();

        $loaded1 = (new ModelWithPluggableTrait($this->db))->load($entity1->getId());
        self::assertSame('value for implementation 1', $loaded1->get('field1'));
        self::assertSame(Implementation1::$name, $loaded1->get('implementation_class_name'));

        $loaded2 = (new ModelWithPluggableTrait($this->db))->load($entity2->getId());
        self::assertSame('value for implementation 2', $loaded2->get('field3'));
        self::assertSame(Implementation2::$name, $loaded2->get('implementation_class_name'));
    }

    public function testFieldValidationIsExecutedOnSave(): void
    {
        $entity = (new ModelWithExtras($this->db))->createEntity()
            ->set('implementation_class', ImplementationWithValidationAndEncryption::class)
            ->save();

        $entity->set('email', 'not-an-email');

        $this->expectException(ValidationException::class);
        $entity->save();
    }

    public function testValidValueForValidatedFieldIsSaved(): void
    {
        $entity = (new ModelWithExtras($this->db))->createEntity()
            ->set('implementation_class', ImplementationWithValidationAndEncryption::class)
            ->save();

        $entity->set('email', 'someone@example.com')->save();

        $loaded = (new ModelWithExtras($this->db))->load($entity->getId());
        self::assertSame('someone@example.com', $loaded->get('email'));
    }

    public function testEncryptedFieldIsStoredEncryptedAndDecryptedOnLoad(): void
    {
        $entity = (new ModelWithExtras($this->db))->createEntity()
            ->set('implementation_class', ImplementationWithValidationAndEncryption::class)
            ->save();

        $entity->set('secret', 'my secret value')->save();

        // in memory the value is decrypted again after reload
        self::assertSame('my secret value', $entity->get('secret'));
        // in the persisted data the value is encrypted
        self::assertSame(
            ModelWithExtras::ENCRYPTION_PREFIX . base64_encode('my secret value'),
            $entity->get('data')['secret']
        );

        $loaded = (new ModelWithExtras($this->db))->load($entity->getId());
        self::assertSame('my secret value', $loaded->get('secret'));
        self::assertSame(
            ModelWithExtras::ENCRYPTION_PREFIX . base64_encode('my secret value'),
            $loaded->get('data')['secret']
        );
    }

    public function testNonEncryptedFieldsAreNotEncrypted(): void
    {
        $entity = (new ModelWithExtras($this->db))->createEntity()
            ->set('implementation_class', ImplementationWithValidationAndEncryption::class)
            ->save();

        $entity->set('email', 'someone@example.com')->save();

        self::assertSame('someone@example.com', $entity->get('data')['email']);
    }

    public function testContainsManyIsAddedFromImplementationClass(): void
    {
        $entity = (new ModelWithExtras($this->db))->createEntity()
            ->set('implementation_class', ImplementationWithContainsMany::class)
            ->save();

        self::assertTrue($entity->hasField('items'));
        self::assertTrue($entity->hasReference('items'));
    }

    public function testContainsManyDataIsPersistedAndLoaded(): void
    {
        $entity = (new ModelWithExtras($this->db))->createEntity()
            ->set('implementation_class', ImplementationWithContainsMany::class)
            ->save();

        $entity->ref('items')->createEntity()->save(['name' => 'first item']);
        $entity->ref('items')->createEntity()->save(['name' => 'second item']);

        $loaded = (new ModelWithExtras($this->db))->load($entity->getId());

        self::assertSame(
            ['first item', 'second item'],
            array_column($loaded->ref('items')->export(['name']), 'name')
        );
    }
}