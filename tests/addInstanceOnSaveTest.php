<?php namespace STPH\addInstanceOnSave;

class addInstanceOnSaveTest extends BaseTest
{
   function testaddInstanceOnSave(){
      $expected = 'Hello from New Instance on Save';
      $actual = $this->module->helloFrom_addInstanceOnSave();

      $this->assertSame($expected, $actual);
      
   }

}