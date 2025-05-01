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

    private static array $class_and_field_inclusion_exclusion_schema = [
        'only_include_models_with_cmseditlink' => true,
        'only_include_models_with_records' => true,
        'excluded_models' => [],
        'included_models' => [],
        'excluded_fields' => [],
        'included_fields' => [],
        'excluded_field_types' => [],
        'included_field_types' => [],
        'excluded_class_field_combos' => [],
        'included_class_field_combos' => [],
        'grouped' => false,
    ];

    private static array $field_grouping_names = [
        'db' => 'Database Fields',
        'casting' => 'Calculated Fields',
        'belongs' => 'Has One',
        'has_one' => 'Has One',
        'has_many' => 'Has Many',
        'many_many' => 'Has Many',
        'belongs_many_many' => 'Has Many',
    ];

    protected $onlyIncludeModelsWithCMSEditLink = true;
    protected $onlyIncludeModelsWithRecords = true;
    protected $excludedModels = [];
    protected $includedModels = [];

    protected $includedFields = [];
    protected $excludedFields = [];
    protected $excludedFieldTypes = [];
    protected $includedFieldTypes = [];
    protected $excludedClassFieldCombos = [];
    protected $includedClassFieldCombos = [];
    protected $grouped = false;


    public function __construct()
    {
        $this->addInclusionsAndExclusions(
            $this->config()->get('class_and_field_inclusion_exclusion_schema')
        );
    }

    protected function addInclusionsAndExclusions(array $schema): void
    {
        if (empty($schema)) {
            return;
        }
        foreach ($schema as $key => $value) {
            if (
                !property_exists($this, $key)
            ) {
                user_error(
                    'Schema Error: ' . $key . ' does not exist in ' . __CLASS__,
                    E_USER_WARNING
                );
            }
            if (is_array($value)) {
                $this->$key = array_unique(array_merge($this->$key, $value));
            } else {
                $this->$key = $value;
            }
        }
    }

    protected static array $listOfClasses = [];

    public function getListOfClasses(?array $additionalSchema = []): array
    {
        $cacheKey = implode(
            '-',
            [
                $additionalSchema ? serialize($additionalSchema) : '',
            ]
        );

        if (!isset(self::$listOfClasses[$cacheKey])) {
            $this->addInclusionsAndExclusions($additionalSchema);
            $list = [];
            $classes = ClassInfo::subclassesFor(DataObject::class, false);
            foreach ($classes as $class) {
                if (in_array($class, $this->excludedModels)) {
                    continue;
                }
                if (!empty($includedModels) && !in_array($class, $this->includedModels)) {
                    continue;
                }

                // get the name
                $obj = Injector::inst()->get($class);
                if (!$obj->canView()) {
                    continue;
                }
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

                    $name .= ' (' . $count . ' records)';
                    if ($this->grouped) {
                        $rootParentName = $this->getDirectSubclassOfDataObjectName($class);
                        if (! isset($list[$rootParentName])) {
                            $list[$rootParentName] = [];
                        }
                        if (in_array($name, $list[$rootParentName], true)) {
                            $name .= ' (disambiguation class name: ' . $class . ')';
                        }
                        $list[$rootParentName][$class] = $name;
                    } else {
                        if (in_array($name, $list, true)) {
                            $name .= ' - disambiguation class name: ' . $class;
                        }
                        $list[$class] = $name;
                    }
                }
            }
            ksort($list, SORT_NATURAL | SORT_FLAG_CASE);
            if ($this->grouped) {
                foreach ($list as &$subArray) {
                    asort($subArray, SORT_NATURAL | SORT_FLAG_CASE);
                }
            }
            unset($subArray); // prevent reference issues
            self::$listOfClasses[$cacheKey] = $list;
        }
        return self::$listOfClasses[$cacheKey];
    }

    public function getDirectSubclassOfDataObjectName(string $className, ?string $notGroupedName = 'Not Grouped'): ?string
    {
        $rootParent = $this->getDirectSubclassOfDataObject($className);
        if ($rootParent && $rootParent !== $className) {
            $obj = Injector::inst()->get($rootParent);
            return $obj->i18n_plural_name();
        }
        return $notGroupedName;
    }

    public function getDirectSubclassOfDataObject(string $className): string
    {
        $ancestors = ClassInfo::ancestry($className);
        foreach ($ancestors as $ancestor) {
            if (is_subclass_of($ancestor, DataObject::class)) {
                return $ancestor;
            }
        }
        return $className;
    }

    protected static array $listOfFieldNames = [];

    public function getListOfFieldNames(
        string $recordClassName,
        ?array $incArray = ['db'],
        ?array $additionalSchema = []
    ): array {
        $cacheKey = implode(
            '-',
            [
                $recordClassName,
                implode('_', $incArray),
                $additionalSchema ? serialize($additionalSchema) : '',
            ]
        );
        if (!isset(self::$listOfFieldNames[$cacheKey])) {
            $groupNames = [];
            if ($this->grouped) {
                $groupNames = $this->config()->get('field_grouping_names');
            }
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
            $classList = $this->getListOfClasses();
            foreach ($fieldsOuterArray as $incType => $fields) {
                $isRelField = $incType === 'db' || $incType === 'casting' ? false : true;
                foreach ($fields as $name => $typeNameOrClassName) {
                    if ($isRelField === false) {
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
                    if ($isRelField === false) {
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
                    if ($this->grouped) {
                        $groupNameNice = $groupNames[$incType] ?? $incType;
                        if (!isset($list[$groupNameNice])) {
                            $list[$groupNameNice] = [];
                        }
                    }
                    $niceName = $labels[$name] ?? $name;
                    if ($isRelField === false) {
                        if ($this->grouped) {
                            $list[$groupNameNice][$name] = $niceName;
                        } else {
                            $list[$name] = $niceName;
                        }
                    } else {
                        //todo: consider further fields in secondary classes
                        $subFields = $this->getListOfFieldNames(

                            $typeNameOrClassName,
                            ['db'],
                            $additionalSchema
                        );
                        foreach ($subFields as $subFieldName => $subFieldLabel) {
                            $relKey =  $name . '.' . $subFieldName;
                            $relLabel =  $niceName . ' - ' . $subFieldLabel;
                            if ($this->grouped) {
                                $list[$groupNameNice][$relKey] = $relLabel;
                            } else {
                                $list[$relKey] = $relLabel;
                            }
                        }
                    }
                }
            }
            self::$listOfFieldNames[$cacheKey] = $list;
        }
        return self::$listOfFieldNames[$cacheKey];
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
