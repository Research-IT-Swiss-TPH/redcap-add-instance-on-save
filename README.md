# Add Instance on Save
Add new instance to a record of an repeating instance by saving any field on any project. Additionaly, define piping fields to set the initial values of fields within the newly added instance.

**Limitation**: This module does not support longitudinal projects with multiple events. It will fail without an error message, since the module always uses the first event id in the project.

## Setup

Install the module from REDCap module repository and enable over Control Center.

## Configuration

Instrutions are sets of settings that will realize the instance addition.
- Enabled: Check to enable the instruction.
- Trigger Field: Field that triggers the instance addition. Only if trigger field value is not empty.
- Matching Field: The field that defines the destination record id. Leave empty if record IDs of source and destination projects are equal.
- Destination Project: Project ID of Destination Project. 
- Destination Form: Name of Destination Form.
Field Piping: Use piping if you want to add instances with initial values piped from the triggering project. In case a Destination Field cannot be resolved within destination project, the whole instruction will become invalid and no instance will be added.
Multiple Field Pipings per Instruction can be added:
- Source Field: The field where the value should be piped from.
- Destination Field: The target field where to value should be piped to. You can leave this empty if the destination field has the same name as the source field.
- Instance ID Target: The field where to save information of currently added instance count/id.

## Szenarios

**Szenario 1: Add instance within same project**

When "Destination Project" is same as the project wherein AIOS is triggered.
Then a new instance of "Destination Form" will be created, if triggered from "Trigger Field" with a non-empty value   - EACH TIME the form containing trigger_field is saved.

**Szenario 2: Add instance to another project**

When "Destination Project" is NOT same as the project wherein AIOS is triggered.
Then a new instance of "Destination Form" will be created, if triggered from "Trigger Field"  AND the matching record EXISTS - EACH TIME the form containing trigger_field is saved.

## Developer Notice
Adjust constants to improve developer experience.

```php
    //  Default
    const IS_HOOK_SIMU_ENABLED = false; # simulates a "save_record" hook on every page load
    const IS_DUMP_ENABLED = false;  # dump method that requires "symfony/var-dumper"
    const IS_ADDING_ENABLED = true; # save instances
```

Run `$ composer install` for formatted dumps.

Credits to [Copy Data on Save](https://github.com/lsgs/redcap-copy-data-on-save) module.

## Changelog

Version | Description
------- | --------------------
v1.0.0  | Initial release.
v1.1.0  | Several improvements. New Setting: Instance ID Target to save current instance count in Source Project.
v1.2.0  | Indicate trigger fields in Data Entry Page and show details Dialog.
v2.0.0  | Upgrade to Module Framework version 9.
