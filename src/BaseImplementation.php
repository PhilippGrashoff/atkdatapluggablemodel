<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel;

use Atk4\Data\Model;

abstract class BaseImplementation
{
    public static string $name;

    protected Model $entity;

    public function __construct(Model $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): Model
    {
        return $this->entity;
    }

    /**
     * Field definitions to be dynamically added to the owning model.
     * @return array<string, array>
     */
    abstract public static function getFieldDefinitions(): array;

    /**
     * containsMany definitions to be dynamically added to the owning model.
     * @return array<string, array>
     */
    public static function getContainsManyDefinitions(): array
    {
        return [];
    }

    /**
     * Validation callbacks, keyed by field name, run before persisting.
     * @return array<string, callable>
     */
    public static function getFieldValidations(): array
    {
        return [];
    }

    /**
     * Field names whose values should be encrypted before being persisted.
     * Override in implementations that need encryption support.
     * @return string[]
     */
    public static function getEncryptedFields(): array
    {
        return [];
    }
}