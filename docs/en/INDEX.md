# tl;dr


Here is how you use it:

```php
class MyDataObject extends DataObject
{

    private static array $class_and_field_inclusion_exclusion_schema = [
        'only_include_models_with_cmseditlink' => true,
        'only_include_models_with_can_create_true' => false,
        'only_include_models_with_can_edit_true' => false,
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

    public function getListOfClasses()
    {
     
        return Injector::inst()->get(ClassAndFieldInfo::class)
            ->getListOfClasses(
                $this->Config()->get('class_and_field_inclusion_exclusion_schema'),
            );
    }

    public function getListOfFields()
    {
        return Injector::inst()->get(ClassAndFieldInfo::class)
            ->getListOfFields(
                $this->ClassName,
                ['db', 'casting', 'has_one', 'belongs']
               $this->Config()->get('class_and_field_inclusion_exclusion_schema'),
            );
    }
}
