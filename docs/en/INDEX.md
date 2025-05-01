# tl;dr


Here is how you use it:

```php
class MyDataObject extends DataObject
{

    public function getListOfClasses()
    {
        return Injector::inst()->get(ClassAndFieldInfo::class)
            ->getListOfClasses(
                $this->ClassName,
                ['db', 'casting', 'has_one', 'belongs']
            );
    }
}
