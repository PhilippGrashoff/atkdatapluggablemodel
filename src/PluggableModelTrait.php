<?php declare(strict_types=1);

namespace PhilippR\Atk4\PluggableModel;

use Atk4\Data\Exception;
use Atk4\Data\Model;

/**
 * @mixin Model
 */
trait PluggableModelTrait
{
    public bool $addDynamicFields = true;

    protected ?BaseImplementation $implementation = null;
    protected ?int $containsManyLoadedForEntityId = null;

    protected function addPluggableFieldsAndHooks(): void
    {
        $this->addField(
            'implementation_class',
            [
                'values' => $this->getAvailableImplementations(),
                'system' => true
            ]
        );

        /** This field is used to store the implementation class name without needing to pull the name from the actual implementation class. Handy for UI tables etc.*/
        $this->addField(
            'implementation_class_name',
            [
                'system' => true
            ]
        );

        /** In this field all data from additional implementation class fields are stored */
        $this->addField(
            'data',
            [
                'type' => 'json',
                'default' => [],
                'system' => true
            ]
        );

        $this->onHook(
            Model::HOOK_AFTER_LOAD,
            function (self $entity) {
                $entity->addFieldsFromImplementationClass();
                if ($entity->get('implementation_class')) {
                    $this->getField('implementation_class')->readOnly = true;
                }
            }
        );

        $this->onHook(
            Model::HOOK_BEFORE_SAVE,
            function (self $entity, bool $isUpdate) {
                $entity->setImplementationClassFieldsToData();
                $entity->setImplementationClassFieldValues();
            }
        );
    }

    /**
     * Override to provide the list of selectable implementation classes,
     * e.g. via a dedicated "available implementations" controller.
     * @return array<string, string>
     */
    protected function getAvailableImplementations(): array
    {
        return [];
    }

    public function getImplementation(): BaseImplementation
    {
        $this->assertIsEntity();

        if ($this->implementation !== null) {
            return $this->implementation;
        }

        $implementationClass = $this->get('implementation_class');
        if ($implementationClass === null) {
            throw new Exception(__FUNCTION__ . ' can only be used with implementation_class set');
        }

        $this->implementation = new $implementationClass($this);
        return $this->implementation;
    }

    protected function setImplementationClassFieldValues(): void
    {
        $implementationClass = $this->get('implementation_class');
        if (
            $implementationClass === null
            || !$this->isDirty('implementation_class')
        ) {
            return;
        }
        $this->set('implementation_class_name', $implementationClass::$name);
    }

    protected function addFieldsFromImplementationClass(): void
    {
        if (!$this->addDynamicFields) {
            return;
        }
        $implementationClass = $this->get('implementation_class');
        if ($implementationClass === null) {
            return;
        }

        foreach ($implementationClass::getFieldDefinitions() as $fieldName => $seed) {
            if (!$this->getModel()->hasField($fieldName)) {
                $field = $this->getModel()->addField($fieldName, $seed);
                $field->neverPersist = true;
                $field->ui['editable'] = $seed['ui']['editable'] ?? true;
            }
            if (!array_key_exists($fieldName, $this->get('data'))) {
                continue;
            }
            $this->set(
                $fieldName,
                $this->getModel()->getPersistence()->typecastLoadField(
                    $this->getField($fieldName),
                    $this->get('data')[$fieldName]
                )
            );

            if (
                method_exists($this, 'decryptFieldValue')
                && in_array($fieldName, $implementationClass::getEncryptedFields())
            ) {
                $this->decryptFieldValue($fieldName);
            }

            $dirtyRef = &$this->getDirtyRef();
            if (array_key_exists($fieldName, $dirtyRef)) {
                unset($dirtyRef[$fieldName]);
            }
        }

        $needsReload = false;
        foreach ($implementationClass::getContainsManyDefinitions() as $fieldName => $seed) {
            if (!$this->getModel()->hasField($fieldName)) {
                $this->getModel()->containsMany($fieldName, $seed);
            }
            if ($this->containsManyLoadedForEntityId !== $this->getId()) {
                $needsReload = true;
            }
        }
        // reload needed to get the containsMany data loaded
        if ($needsReload) {
            $this->containsManyLoadedForEntityId = $this->getId();
            $this->reload();
        }
    }

    protected function setImplementationClassFieldsToData(): void
    {
        if (!$this->addDynamicFields) {
            return;
        }
        $implementationClass = $this->get('implementation_class');
        if ($implementationClass === null) {
            return;
        }
        if ($this->isDirty('implementation_class')) {
            return;
        }

        $data = [];
        $fieldValidations = $implementationClass::getFieldValidations();
        foreach ($implementationClass::getFieldDefinitions() as $fieldName => $seed) {
            if (
                method_exists($this, 'encryptFieldValue')
                && in_array($fieldName, $implementationClass::getEncryptedFields())
            ) {
                $this->encryptFieldValue($fieldName);
            }
            $typecastedValue = $this->getModel()->getPersistence()->typecastSaveField(
                $this->getField($fieldName),
                $this->get($fieldName)
            );
            if (array_key_exists($fieldName, $fieldValidations)) {
                $fieldValidations[$fieldName]($typecastedValue);
            }

            $data[$fieldName] = $typecastedValue;
        }
        $this->set('data', $data);
    }
}