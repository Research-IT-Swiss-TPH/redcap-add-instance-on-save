<?php

/**
 * Class for module "Add Instance on Save"
 * Author: Ekin Tertemiz
 * 
 * Developer Instructions:
 * Adjust constants to improve developer experience.
 * Run `$ composer install` for formatted dumps
 * 
 */

// Set the namespace defined in your config file
namespace STPH\addInstanceOnSave;

use \REDCap;

if( file_exists("vendor/autoload.php") ){
    require 'vendor/autoload.php';
}

// Declare your module class, which must extend AbstractExternalModule 
class addInstanceOnSave extends \ExternalModules\AbstractExternalModule {

    //  Constants
    const IS_HOOK_SIMU_ENABLED = false;
    const IS_DUMP_ENABLED = true;
    const IS_ADDING_ENABLED = true;

    /**
     * Module's main hook
     * 
     * @return void
     * @since 1.0.0
     */
    function redcap_save_record($project_id, $record=null, $instrument, $event_id, $group_id=null, $survey_hash=null, $response_id=null, $repeat_instance=1) {
        
        //  Check if is Data Entry Page (later on we can add support for Survey Pages if we need)
        if($this->isPage("DataEntry/index.php")) {           
            $this->run_instructions($record, $instrument, $event_id);
        }

    }

   /**
    * Only used during development
    * @return void
    * @since 1.0.0
    */
    public function redcap_every_page_top($project_id = null) {

        if(self::IS_HOOK_SIMU_ENABLED) {
            //  Simulate Save (has to be triggered within record context, otherwise no ID)
            $this->simulate_save_hook();
        }

    }

    /**
     * Simulate redcap_save_record Hook
     * Used for improved development experience.
     * 
     * @return void
     * @since 1.0.0
     */
    private function simulate_save_hook() {

        try {
            \Hooks::call(
                'redcap_save_record', 
                array(PROJECT_ID, 
                $_GET['id'], 
                $_GET['page'], 
                $_GET['event_id'], 
                null, 
                null, 
                null, 
                $_GET['instance'])
            );

        } catch (\Throwable $th) {
            $this->dump($th);
        }

    }

    /**
     * Main method: Runs each instruction that has been configured for the module per project
     * 
     * @param string $record
     * @param string $instrument
     * @param string $event_id
     * 
     * @return void
     * @since 1.0.0
     */
    private function run_instructions($record, $instrument, $event_id) {

        global $Proj;
        $instructions = $this->getSubSettings('instructions');

        foreach($instructions as $instruction) {

            //  Check if instruction is enabled
            if (!$instruction['add-enabled']) continue;
            $this->dump($instruction);

            $destProjectId = $instruction['destination-project'];

            //  Retrieve destination project data
            $destProject = new \Project( $destProjectId );
            $this->dump($destProject);

            //  Get destination event id (as first event id)
            $destEventId = $destProject->firstEventId;

            //  Check if destination form is repeating
            if(!($destProject-> isRepeatingForm($destEventId, $instruction['destination-form']))) continue;

            //  Get Source Project Meta Data
            $sourceProjectMeta = $Proj->metadata;
            $this->dump($sourceProjectMeta);

            //  Check if trigger field exists in Source Project (redundant)
            if( !array_key_exists($instruction['trigger-field'] ,$sourceProjectMeta) ) continue;

            //  Check if trigger field is on current instrument page
            if( $sourceProjectMeta[$instruction['trigger-field']]['form_name'] != $instrument ) continue;

            //  Get Source Project Trigger Field Value
            $sourceProjectFields = REDCap::getData(array(
                'return_format' => 'array', 
                'records' => $record, 
                'fields' => [],
                'exportDataAccessGroups' => true
            ))[$record][$event_id];
            $this->dump($sourceProjectFields);

            //  Check if Trigger Field value is empty (We will only add instance if trigger value is not empty)
            if((empty($sourceProjectFields[$instruction['trigger-field']]))) continue;

            //  Define destination record id
            $destRecordId =  $instruction['matching-field'] == null ? $record :$sourceProjectFields[$instruction['matching-field']];
            $this->dump($destRecordId);

            $destProjectFields = REDCap::getData(array(
                'return_format' => 'array', 
                'project_id' => $destProjectId,
                'records' => $destRecordId, 
                'fields' => [],
                'exportDataAccessGroups' => true
            ));
            
            //  Check if destination record exists
            if( is_null($destProjectFields) ) continue;
            $this->dump($destProjectFields);
            
            //  Calculate destination instance id from current count + 1
            $destInstanceId = count($destProjectFields[$destRecordId]['repeat_instances'][$destEventId][$instruction['destination-form']]) + 1;
            $this->dump($destInstanceId);

            $invalid_pipings = [];
            $destFieldValues = [];
            //  Construct destination field values from piping instructions
            foreach ($instruction['field-pipings'] as $key => $fieldPiping) {
                $fieldToCheck = is_null($fieldPiping['destination-field']) ? $fieldPiping['source-field'] :$fieldPiping['destination-field'];

                //  Check if field pipings are valid
                if(array_key_exists($fieldToCheck, $destProjectFields[$destRecordId][$destEventId])) {

                   $destFieldValues[$fieldToCheck] = $sourceProjectFields[$fieldPiping['source-field']];

                } else {
                    $invalid_pipings[] = $fieldToCheck;
                }
            }

            //  Break if invalid piping
            if(count($invalid_pipings) > 0) continue;
            $this->dump( $destFieldValues);

            //  Add instance
            if(self::IS_ADDING_ENABLED) {
            $added_instance = $this->add_instance($destProjectId, $destRecordId, $destEventId, $destInstanceId, $destFieldValues, $instruction);
            //REDCap::logEvent("Instance added", json_encode($added_instance));
            $this->dump($added_instance);
            }

        }

    }

    /**
     * Adds instance to existing record of destination project
     * 
     * @param string $destProjectId
     * @param string $destRecordId
     * @param string $destEventId
     * @param string $destInstanceId
     * @param array $destFieldValues
     * 
     * @return array
     * @since 1.0.0
     */
    private function add_instance($destProjectId, $destRecordId, $destEventId, $destInstanceId, $destFieldValues, $instruction) {

        //  Construct array to save data
        $dataToSave = [
            $destRecordId => [
                "repeat_instances" => [
                    $destEventId => [
                        $instruction['destination-form'] => [
                            $destInstanceId => $destFieldValues
                        ]
                    ]
                ]
            ]
        ];


        try {
            //  Do not skip calculated fields 
            $skipCalc = !$instruction['calc-enabled'];
            $saved = REDCap::saveData($destProjectId, 'array', $dataToSave, 'overwrite', 'YMD', 'flat', null, true, true, true, false, $skipCalc);
            return $saved;
        } catch(\Exception $e) {
            $this->dump($e);
        }

    }

    /**
     * Dumping helper
     * 
     * @return string
     * @since 1.0.0
     */
    private function dump($content) {
        if(function_exists('dump') && self::IS_DUMP_ENABLED ){
            dump($content);
        }
    }

}