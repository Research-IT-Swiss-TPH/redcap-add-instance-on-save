<?php

// Set the namespace defined in your config file
namespace STPH\newInstanceOnSave;


// Declare your module class, which must extend AbstractExternalModule 
class newInstanceOnSave extends \ExternalModules\AbstractExternalModule {

    private $moduleName = "New Instance on Save";  

   /**
    * Constructs the class
    *
    */
    public function __construct()
    {        
        parent::__construct();
       // Other code to run when object is instantiated
    }

   /**
    * Hooks New Instance on Save module to redcap_every_page_top
    *
    */
    public function redcap_every_page_top($project_id = null) {
        $this->renderModule();
    }

   /**
    * Renders the module
    *
    * @since 1.0.0
    */
    private function renderModule() {
        
        

        print '<p class="new-instance-on-save">'.$this->helloFrom_newInstanceOnSave().'<p>';

    }

    /**
    * Returns a test string including module name.
    *
    * @since 1.0.0
    */    
    public function helloFrom_newInstanceOnSave() {

                
        return 'Hello from '.$this->moduleName;
        

    }

    

    
}