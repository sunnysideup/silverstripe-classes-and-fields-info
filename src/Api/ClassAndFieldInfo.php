<?php

declare(strict_types=1);

namespace Sunnysideup\ClassesAndFieldsInfo\Api;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;

class ClassAndFieldInfo
{
    use Injectable;
    use Configurable;

    private static array $included_models = [];

    private static array $excluded_models = [
        'SilverStripe\\Versioned\\ChangeSetItem',
        'DNADesign\\Elemental\\Models\\BaseElement',
    ];

    private static array $included_fields = [];

    private static array $excluded_field_types = [];

    private static array $included_field_types = [];

    private static $excluded_class_field_combos = [
        // 'SilverStripe\\Versioned\\ChangeSetItem' => [
        //     'ClassName',
        // ],
    ];

    private static $included_class_field_combos = [
        // 'SilverStripe\\Versioned\\ChangeSetItem' => [
        //     'ClassName',
        // ],
    ];

    private static array $excluded_fields = [];

    private static $only_included_models_with_cmseditlink = false;

    private static $show_pages_first_in_list_of_models = true;


    protected $excludedModels = [];
    protected $includedModels = [];

    protected $includedFields = [];
    protected $excludedFields = [];
    protected $excludedFieldTypes = [];
    protected $includedFieldTypes = [];
    protected $excludedClassFieldCombos = [];
    protected $includedClassFieldCombos = [];
    protected $onlyIncludeModelsWithCMSEditLink = true;
    protected $showPagesFirst = false;


    public function setExcludedModels(array $excludedModels): static
    {
        $this->excludedModels = $excludedModels;
        return $this;
    }
    public function setIncludedModels(array $includedModels): static
    {
        $this->includedModels = $includedModels;
        return $this;
    }
    public function setIncludedFields(array $includedFields): static
    {
        $this->includedFields = $includedFields;
        return $this;
    }
    public function setExcludedFields(array $excludedFields): static
    {
        $this->excludedFields = $excludedFields;
        return $this;
    }
    public function setExcludedFieldTypes(array $excludedFieldTypes): static
    {
        $this->excludedFieldTypes = $excludedFieldTypes;
        return $this;
    }
    public function setIncludedFieldTypes(array $includedFieldTypes): static
    {
        $this->includedFieldTypes = $includedFieldTypes;
        return $this;
    }
    public function setExcludedClassFieldCombos(array $excludedClassFieldCombos): static
    {
        $this->excludedClassFieldCombos = $excludedClassFieldCombos;
        return $this;
    }
    public function setIncludedClassFieldCombos(array $includedClassFieldCombos): static
    {
        $this->includedClassFieldCombos = $includedClassFieldCombos;
        return $this;
    }
    public function setOnlyIncludeModelsWithCMSEditLink(bool $onlyIncludeModelsWithCMSEditLink): static
    {
        $this->onlyIncludeModelsWithCMSEditLink = $onlyIncludeModelsWithCMSEditLink;
        return $this;
    }
    public function setShowPagesFirst(bool $showPagesFirst): static
    {
        $this->showPagesFirst = $showPagesFirst;
        return $this;
    }

    public function addToExcludedModels(array $excludedModels): static
    {
        $this->excludedModels = array_merge($this->excludedModels, $excludedModels);
        return $this;
    }
    public function addToIncludedModels(array $includedModels): static
    {
        $this->includedModels = array_merge($this->includedModels, $includedModels);
        return $this;
    }
    public function addToIncludedFields(array $includedFields): static
    {
        $this->includedFields = array_merge($this->includedFields, $includedFields);
        return $this;
    }
    public function addToExcludedFields(array $excludedFields): static
    {
        $this->excludedFields = array_merge($this->excludedFields, $excludedFields);
        return $this;
    }
    public function addToExcludedFieldTypes(array $excludedFieldTypes): static
    {
        $this->excludedFieldTypes = array_merge($this->excludedFieldTypes, $excludedFieldTypes);
        return $this;
    }
    public function addToIncludedFieldTypes(array $includedFieldTypes): static
    {
        $this->includedFieldTypes = array_merge($this->includedFieldTypes, $includedFieldTypes);
        return $this;
    }
    public function addToExcludedClassFieldCombos(array $excludedClassFieldCombos): static
    {
        $this->excludedClassFieldCombos = array_merge($this->excludedClassFieldCombos, $excludedClassFieldCombos);
        return $this;
    }
    public function addToIncludedClassFieldCombos(array $includedClassFieldCombos): static
    {
        $this->includedClassFieldCombos = array_merge($this->includedClassFieldCombos, $includedClassFieldCombos);
        return $this;
    }


    public function __construct()
    {
        $this->setExcludedModels($this->config()->get('excluded_models'));
        $this->setIncludedModels($this->config()->get('included_models'));
        $this->setIncludedFields($this->config()->get('included_fields'));
        $this->setExcludedFields($this->config()->get('excluded_fields'));
        $this->setExcludedFieldTypes($this->config()->get('excluded_field_types'));
        $this->setIncludedFieldTypes($this->config()->get('included_field_types'));
        $this->setExcludedClassFieldCombos($this->config()->get('excluded_class_field_combos'));
        $this->setIncludedClassFieldCombos($this->config()->get('included_class_field_combos'));
        $this->setOnlyIncludeModelsWithCMSEditLink($this->config()->get('only_included_models_with_cmseditlink'));
        $this->setShowPagesFirst($this->config()->get('show_pages_first_in_list_of_models'));
    }

    protected function addInclusionsAndExclusions($requestingObject): void
    {
        $this->addToExcludedModels($requestingObject->config()->get('excluded_models') ?: []);
        $this->addToIncludedModels($requestingObject->config()->get('included_models') ?: []);
        $this->addToIncludedFields($requestingObject->config()->get('included_fields') ?: []);
        $this->addToExcludedFields($requestingObject->config()->get('excluded_fields') ?: []);
        $this->addToExcludedFieldTypes($requestingObject->config()->get('excluded_field_types') ?: []);
        $this->addToIncludedFieldTypes($requestingObject->config()->get('included_field_types') ?: []);
        $this->addToExcludedClassFieldCombos($requestingObject->config()->get('excluded_class_field_combos') ?: []);
        $this->addToIncludedClassFieldCombos($requestingObject->config()->get('included_class_field_combos') ?: []);

        $onlyIncludeModelsWithCMSEditLink = $requestingObject->config()->get('only_included_models_with_cmseditlink');
        if ($onlyIncludeModelsWithCMSEditLink) {
            $this->setOnlyIncludeModelsWithCMSEditLink($onlyIncludeModelsWithCMSEditLink);
        }
        $showPagesFirst = $requestingObject->config()->get('show_pages_first_in_list_of_models');
        if ($showPagesFirst) {
            $this->setShowPagesFirst($showPagesFirst);
        }
    }

    protected static array $listOfClasses = [];

    public function getListOfClasses($requestingObject): array
    {
        $key = get_class($requestingObject);
        if (!isset(self::$listOfClasses[$key])) {
            $otherList = [];
            $pageList = [];
            $classes = ClassInfo::subclassesFor(DataObject::class, false);
            $hasIsValidClassNameMethod = $requestingObject->hasMethod('IsValidClassName');
            foreach ($classes as $class) {
                if (in_array($class, $this->excludedModels)) {
                    continue;
                }
                if (!empty($includedModels) && !in_array($class, $this->includedModels)) {
                    continue;
                }

                if ($hasIsValidClassNameMethod && ! $requestingObject->IsValidClassName($class)) {
                    continue;
                }
                // get the name
                $obj = Injector::inst()->get($class);
                $count = $class::get()->filter(['ClassName' => $class])->count();
                if ($count === 0) {
                    continue;
                }
                if ($obj->hasMethod('CMSEditLink') || !$this->onlyIncludeModelsWithCMSEditLink) {
                    $name = $obj->i18n_singular_name();
                    $desc = $obj->Config()->get('description');
                    if ($desc) {
                        $name .= ' - ' . $desc;
                    }
                    $name = trim($name);
                    // add name to list
                    foreach ([$otherList, $pageList] as $list) {
                        if (in_array($name, $list, true)) {
                            $name .= ' (disambiguation class name: ' . $class . ')';
                        }
                    }
                    $name .= ' (' . $count . ' records)';
                    if ($this->showPagesFirst) {
                        if ($obj instanceof SiteTree) {
                            $pageList[$class] = $name;
                        } else {
                            $otherList[$class] = $name;
                        }
                    } else {
                        $otherList[$class] = $name;
                    }
                }
            }
            asort($pageList);
            asort($otherList);
            self::$listOfClasses[$key] = $pageList + $otherList;
        }
        return self::$listOfClasses[$key];
    }



    protected static array $listOfFieldNames = [];

    public function getListOfFieldNames($requestingObject, string $recordClassName, ?array $incArray = ['db']): array
    {
        $key = get_class($requestingObject) . '-' . $recordClassName . '-' . implode('-', $incArray);
        if (!isset(self::$listOfFieldNames[$key])) {
            $record = Injector::inst()->get($recordClassName);
            if (!$record || !$record instanceof DataObject) {
                return [];
            }
            $list = [];
            $labels = $record->fieldLabels();
            if (empty($incArray)) {
                $incArray = ['db'];
            }
            $fieldsOuterArray = [];
            foreach ($incArray as $incType) {
                $fieldsOuterArray[$incType] = $record->config()->get($incType);
            }

            // Get configuration variables using config system
            $hasIsValidFieldTypeMethod = $requestingObject->hasMethod('IsValidFieldType');
            $classList = $this->getListOfClasses($requestingObject);
            foreach ($fieldsOuterArray as $incType => $fields) {
                $nonRelField = $incType === 'db' || $incType === 'casting';
                foreach ($fields as $name => $typeNameOrClassName) {
                    if ($nonRelField) {
                        if ($hasIsValidFieldTypeMethod && ! $requestingObject->IsValidFieldType((string) $typeNameOrClassName)) {
                            continue;
                        }
                    } else {
                        if (!isset($classList[$typeNameOrClassName])) {
                            continue;
                        }
                    }
                    // Skip if field is in excluded_fields
                    if (in_array($name, $this->excludedFields)) {
                        continue;
                    }

                    // Skip if field is in excluded_class_field_combos for this class
                    if (
                        isset($this->excludedClassFieldCombos[$recordClassName]) &&
                        in_array($name, $this->excludedClassFieldCombos[$recordClassName])
                    ) {
                        continue;
                    }
                    if ($nonRelField) {
                        $typeObject = $record->dbObject($typeNameOrClassName);

                        // Skip if field type is in excluded_field_types
                        if ($this->isExcludedFieldType($typeObject)) {
                            continue;
                        }
                        // Skip if field type is not explicitly included when included_field_types is not empty
                        if (!empty($this->includedFieldTypes) && !$this->isIncludedFieldType($typeObject)) {
                            continue;
                        }
                    }

                    // Skip if not explicitly included when included_fields is not empty
                    if (!empty($this->includedFields) && !in_array($name, $this->includedFields)) {
                        continue;
                    }

                    // Skip if not explicitly included when included_class_field_combos for this class is not empty
                    if (
                        isset($this->includedClassFieldCombos[$recordClassName]) &&
                        !empty($this->includedClassFieldCombos[$recordClassName]) &&
                        !in_array($name, $this->includedClassFieldCombos[$recordClassName])
                    ) {
                        continue;
                    }
                    $niceName = $labels[$name] ?? $name;
                    if ($nonRelField) {
                        $list[$name] = $niceName;
                    } else {
                        //todo: consider further fields in secondary classes
                        $subFields = $this->getListOfFieldNames(
                            $requestingObject,
                            $typeNameOrClassName,
                            ['db']
                        );
                        foreach ($subFields as $subFieldName => $subFieldLabel) {
                            $list[$name . '.' . $subFieldName] =  $niceName . ' - ' . $subFieldLabel;
                        }
                    }
                }
            }
            self::$listOfFieldNames[$key] = $list;
        }
        return self::$listOfFieldNames[$key];
    }

    /**
     * Check if a field type is in the excluded_field_types list
     *
     * @param DBField $type The field type to check
     * @param array $excludedFieldTypes List of excluded field types
     * @return bool
     */
    protected function isExcludedFieldType($typeObject): bool
    {
        foreach ($this->excludedFieldTypes as $excludedType) {
            if ($typeObject instanceof $excludedType) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a field type is in the included_field_types list
     *
     * @param DBField $type The field type to check
     * @param array $includedFieldTypes List of included field types
     * @return bool
     */
    protected function isIncludedFieldType($typeObject): bool
    {
        if (empty($this->includedFieldTypes)) {
            return true; // If no included types specified, all non-excluded types are included
        }

        foreach ($this->includedFieldTypes as $includedType) {
            if ($typeObject instanceof $includedType) {
                return true;
            }
        }
        return false;
    }

    public function FindFieldTypeObject($obj, string $dotNotationField)
    {
        list($class, $field) = $this->ResolveFieldFromChain($obj, $dotNotationField);
        $obj = Injector::inst()->get($class);
        if ($obj && $obj->hasMethod('dbObject')) {
            return $obj->dbObject($field);
        }
    }

    public function ResolveFieldFromChain($obj, string $dotNotationField): array
    {
        $parts = explode('.', $dotNotationField);
        $lastObj = $obj;
        foreach ($parts as $i => $part) {

            if ($i === count($parts) - 1) {
                return ['class' => get_class($lastObj), 'field' => $part];
            }
            $lastObj = $lastObj->$part();
        }
        return ['class' => get_class($lastObj), 'field' => end($parts)];
    }
}
