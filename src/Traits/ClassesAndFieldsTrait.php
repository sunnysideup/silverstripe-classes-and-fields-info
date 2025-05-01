<?php

declare(strict_types=1);

namespace Sunnysideup\ClassesAndFieldsInfo\Traits;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBField;

trait ClassesAndFieldsTrait
{

    private static $show_pages_first_in_list_of_models = true;

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

    protected static array $listOfClasses = [];



    protected function getListOfClasses(): array
    {
        if (empty(self::$listOfClasses)) {
            $otherList = [];
            $pageList = [];
            $classes = ClassInfo::subclassesFor(DataObject::class, false);
            $excludedModels = (array) $this->config()->get('excluded_models');
            $includedModels = (array) $this->config()->get('included_models');
            $onlyIncludeModelsWithCMSEditLink = (bool) $this->config()->get('only_included_models_with_cmseditlink');
            $showPagesFirst = (bool) $this->config()->get('show_pages_first_in_list_of_models');
            $hasIsValidClassNameMethod = $this->hasMethod('IsValidClassName');
            foreach ($classes as $class) {
                if (in_array($class, $excludedModels)) {
                    continue;
                }
                if (!empty($includedModels) && !in_array($class, $includedModels)) {
                    continue;
                }

                if ($hasIsValidClassNameMethod && ! $this->IsValidClassName($class)) {
                    continue;
                }
                // get the name
                $obj = Injector::inst()->get($class);
                $count = $class::get()->filter(['ClassName' => $class])->count();
                if ($count === 0) {
                    continue;
                }
                if ($obj->hasMethod('CMSEditLink') || !$onlyIncludeModelsWithCMSEditLink) {
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
                    if ($showPagesFirst) {
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
            self::$listOfClasses = $pageList + $otherList;
        }
        return self::$listOfClasses;
    }



    protected static array $listOfFieldNames = [];

    protected function getListOfFieldNames($record): array
    {
        if ($record) {
            if (empty(self::$listOfFieldNames[$record->ClassName])) {
                $list = [];
                $labels = $record->fieldLabels();
                $db = $record->config()->get('db');
                $className = get_class($record);

                // Get configuration variables using config system
                $excludedFields = $this->config()->get('excluded_fields');
                $includedFields = $this->config()->get('included_fields');
                $excludedFieldTypes = $this->config()->get('excluded_field_types');
                $includedFieldTypes = $this->config()->get('included_field_types');
                $excludedClassFieldCombos = $this->config()->get('excluded_class_field_combos');
                $includedClassFieldCombos = $this->config()->get('included_class_field_combos');
                $hasIsValidFieldTypeMethod = $this->hasMethod('IsValidFieldType');
                foreach ($db as $name => $typeName) {
                    if ($hasIsValidFieldTypeMethod && ! $this->IsValidFieldType((string) $typeName)) {
                        continue;
                    }
                    $type = $record->dbObject($typeName);
                    // Skip if field is in excluded_fields
                    if (in_array($name, $excludedFields)) {
                        continue;
                    }

                    // Skip if field is in excluded_class_field_combos for this class
                    if (
                        isset($excludedClassFieldCombos[$className]) &&
                        in_array($name, $excludedClassFieldCombos[$className])
                    ) {
                        continue;
                    }

                    // Skip if field type is in excluded_field_types
                    if ($this->isExcludedFieldType($type, $excludedFieldTypes)) {
                        continue;
                    }

                    // Skip if not explicitly included when included_fields is not empty
                    if (!empty($includedFields) && !in_array($name, $includedFields)) {
                        continue;
                    }

                    // Skip if not explicitly included when included_class_field_combos for this class is not empty
                    if (
                        isset($includedClassFieldCombos[$className]) &&
                        !empty($includedClassFieldCombos[$className]) &&
                        !in_array($name, $includedClassFieldCombos[$className])
                    ) {
                        continue;
                    }

                    // Skip if field type is not explicitly included when included_field_types is not empty
                    if (!empty($includedFieldTypes) && !$this->isIncludedFieldType($type, $includedFieldTypes)) {
                        continue;
                    }

                    // all good in the hood
                    $list[$name] = $labels[$name] ?? $name;
                }
                self::$listOfFieldNames[$record->ClassName] = $list;
            }
        }
        return self::$listOfFieldNames[$record?->ClassName] ?? [];
    }

    /**
     * Check if a field type is in the excluded_field_types list
     *
     * @param DBField $type The field type to check
     * @param array $excludedFieldTypes List of excluded field types
     * @return bool
     */
    protected function isExcludedFieldType($type, array $excludedFieldTypes): bool
    {
        foreach ($excludedFieldTypes as $excludedType) {
            if ($type instanceof $excludedType) {
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
    protected function isIncludedFieldType($type, array $includedFieldTypes): bool
    {
        if (empty($includedFieldTypes)) {
            return true; // If no included types specified, all non-excluded types are included
        }

        foreach ($includedFieldTypes as $includedType) {
            if ($type instanceof $includedType) {
                return true;
            }
        }
        return false;
    }
}
