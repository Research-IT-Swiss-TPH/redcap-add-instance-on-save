# Add Instance on Save
Add new instance to a record of an repeating instance by saving any field on any project. Additionaly, define piping fields to set the initial values of fields within the newly added instance.

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


## Developer Notice
Adjust constants to improve developer experience.
Run `$ composer install` for formatted dumps.

Credits to @lsgs and the [Copy Data on Save](https://github.com/lsgs/redcap-copy-data-on-save) module developed by him which served as a fundament for this module.

## Changelog

Version | Description
------- | --------------------
v1.0.0  | Initial release.
