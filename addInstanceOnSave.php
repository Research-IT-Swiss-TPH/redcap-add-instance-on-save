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
    const IS_DUMP_ENABLED = false;
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
    * Hook needed to include parameters
    * simulate_save_hook(): Only used during development
    * @return void
    * @since 1.0.0
    */
    public function redcap_every_page_top($project_id = null) {

        if($this->isPage('DataEntry/index.php')) {
            global $Proj;
            $instructions = $this->getSubSettings('instructions');

            $js_instructions = [];

            //  push all relevant instructions to array for passing into JavaScript
            foreach ($instructions as $key => $instruction) {                
                $isRelevantFormPage = $Proj->metadata[$instruction['trigger-field']]['form_name'] === $_GET['page'];
                
                if($isRelevantFormPage) {
                    $js_instructions[] = $instruction;
                }                
            }
        }

        $this->includePageJavascript($js_instructions);

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
            //$this->dump($instruction);

            $destProjectId = $instruction['destination-project'];
            $destForm = $instruction['destination-form'];

            //  Retrieve destination project data
            $destProject = new \Project( $destProjectId );

            //  Skip if destination project is longitudinal
            if($destProject->longitudinal) continue;

            $destPrimaryKey = $destProject->table_pk;
            //$this->dump($destProject);

            //  Get destination event id (as first event id)
            $destEventId = $destProject->firstEventId;

            //  Skip if destination form is NOT repeating
            if(!($destProject-> isRepeatingForm($destEventId, $destForm))) continue;

            //  Get Source Project Meta Data
            $sourceProjectMeta = $Proj->metadata;
            //$this->dump($sourceProjectMeta);

            //  Skip if trigger field exists in Source Project (redundant)
            if( !array_key_exists($instruction['trigger-field'] ,$sourceProjectMeta) ) continue;

            //  Skip if trigger field is on current instrument page
            if( $sourceProjectMeta[$instruction['trigger-field']]['form_name'] != $instrument ) continue;

            //  Get Source Project Trigger Field Value
            $sourceProjectFields = REDCap::getData(array(
                'return_format' => 'array', 
                'records' => $record, 
                'fields' => [],
                'exportDataAccessGroups' => true
            ))[$record][$event_id];
            //$this->dump($sourceProjectFields);

            //  Skip if Trigger Field value is empty (We will only add instance if trigger value is not empty)
            if((empty($sourceProjectFields[$instruction['trigger-field']]))) continue;

            //  Define destination record id
            $destRecordId =  $instruction['matching-field'] == null ? $record :$sourceProjectFields[$instruction['matching-field']];
            //$this->dump($destRecordId);

            $destProjectFields = REDCap::getData(array(
                'return_format' => 'array', 
                'project_id' => $destProjectId,
                'records' => $destRecordId, 
                'fields' => [],
                'exportDataAccessGroups' => true
            ));
            
            //  Skip if destination record exists
            if( is_null($destProjectFields) ) continue;
            //$this->dump($destProjectFields);
            
            //  Calculate destination instance id from current count + 1
            $destInstanceId = count((array)$destProjectFields[$destRecordId]['repeat_instances'][$destEventId][$destForm]) + 1;
            //$this->dump($destInstanceId);

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

            //  Skip if invalid piping
            if(count($invalid_pipings) > 0) continue;
            //$this->dump( $destFieldValues);


            if(self::IS_ADDING_ENABLED) {
                
                //  Add instance
                $dataToAdd = [$destRecordId => ["repeat_instances" => [$destEventId => [$destForm => [$destInstanceId => $destFieldValues]]]]];
                $added_instance = $this->add_instance($destProjectId, $dataToAdd);
                //$this->dump($added_instance);

                //  Save current instance id to field if enabled
                if(!empty($instruction['instance-id-target'])) {

                    $json_data = '[{"'.$Proj->table_pk.'":"'.$record.'","'.$instruction["instance-id-target"].'":"'.$destInstanceId.'"}]';
                    $params = array(
                        'dataFormat'=>'json', 
                        'type'=>'flat', 
                        'data'=>$json_data
                    );

                    $repsonse = REDCap::saveData($params);
                    $this->dump($repsonse);

                }

            }

            /**
             * Ugly Fix for an issue with REDCap::saveData() where adding first instance to an already existing record will duplicate an entry
             * for its primary key. The below correction ensures that there are no duplicates and the database does not get corrupted.
             * 
             * @since 1.1.0
             */            
            if($destInstanceId == 1) {
                do {                    
                    $this->delete_duplicate_row(
                        $destProjectId, 
                        $destRecordId, 
                        $destPrimaryKey
                    );                    
                } while ($this->get_row_count($destProjectId, $destRecordId, $destPrimaryKey) > 1);

            }

        }

    }

    /**
     * Includes Page Javascript
     * 
     * @param array $params
     * 
     * @return void
     * @since 1.2.0
     * 
     */
    private function includePageJavascript($instructions) {

        ?>
        <script src="<?php print $this->getUrl('js/aios.js'); ?>"></script>
        <script>
            STPH_aios.enable_debug = <?= json_encode((bool) $this->getProjectSetting("javascript-debug")) ?>;
            STPH_aios.instructions = <?= json_encode($instructions) ?>;
            $(function() {
                $(document).ready(function(){
                    STPH_aios.init();
                })
            });            
        </script>
        <?php
    }    

    /**
     * Adds instance to existing record of destination project
     * 
     * @param string $project_id
     * @param string $data
     * @param string $skipCalcFields
     * 
     * @return array
     * @since 1.0.0
     */
    private function add_instance($project_id, $data) {

        try {

            $args = [
                'project_id' => $project_id,
                'data' => $data
            ];

            $saved = REDCap::saveData($args);
            return $saved;
        } catch(\Exception $e) {
            $this->dump($e);
        }

    }

    private function get_row_count($pid, $record, $pk) {
        $sql_select = 'SELECT record FROM redcap_data WHERE project_id=? AND record=? AND field_name=? AND instance IS NULL';
        $result = $this->query($sql_select, [$pid, $record, $pk]);
        $rows = [];
        while($row = $result->fetch_object()) {
           $rows[] = $row;
        }
        return count($rows);
    }

    private function delete_duplicate_row($pid, $record, $pk) {
        $sql_delete = 'DELETE FROM redcap_data WHERE project_id=? AND record=? AND field_name=? AND instance IS NULL LIMIT 1';
        $this->query($sql_delete, [$pid, $record, $pk]);
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
