# Silverstripe CMS 6 Upgrade Guide

This document outlines the necessary changes to upgrade your project to be compatible with Silverstripe CMS 6.

## ⚠️ BREAKING CHANGE

### Requirements

-   **PHP ^8.1**: The minimum required PHP version has been updated to 8.1.
-   **Silverstripe Framework ^6.0**: This module now requires Silverstripe Framework version 6.0 or higher.
-   **Silverstripe Admin ^3.0**: The required version for Silverstripe Admin has been updated to 3.0.

### API Changes

-   The method signature for `getClassesAndFields` in `Sunnysideup\ClassesAndFieldsInfo\Api\GetClassesAndFields` has been updated. The `$customFilter` parameter is now explicitly typed as `?Closure`. If you were passing a custom filter, ensure it is a `Closure` or `null`.

-   The `getCached` method in `Sunnysideup\ClassesAndFieldsInfo\Api\GetClassesAndFields` now has a return type of `?array`. You may need to update your code to handle the possibility of a `null` return.

-   The `formatKey` method in `Sunnysideup\ClassesAndFieldsInfo\Api\GetClassesAndFields` is now explicitly typed to return a `string`.

-   All private properties in `Sunnysideup\ClassesAndFieldsInfo\Api\GetClassesAndFields` now have explicit types.

-   The `checkForTable` method in `Sunnysideup\ClassesAndFieldsInfo\Api\GetClassesAndFields` has been removed. Any direct calls to this method must be removed.

### Other Changes

-   The `sunnysideup/sswebpack_engine_only` dependency is now fixed to `^5.0-dev`.

-   The `.php_cs.dist` and `.idea` folder have been added to `.gitignore`.
