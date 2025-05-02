# tl;dr


Here is how you use it:

```php
class MyDataObject extends DataObject
{

    public function getListOfClasses()
    {
        $mySchema = [
            // see ClassAndFieldInfo for options!
            'Grouped' => true, // groups them by root model - not required!
        ];        
        return Injector::inst()->get(ClassAndFieldInfo::class)
            ->getListOfClasses(
                $mySchema
            );
    }

    public function getListOfFields()
    {
        $mySchema = [
            // see ClassAndFieldInfo for options!
        ];
        return Injector::inst()->get(ClassAndFieldInfo::class)
            ->getListOfFields(
                $this->ClassName,
                ['db', 'casting', 'has_one', 'belongs']
                $mySchema
            );
    }
}
