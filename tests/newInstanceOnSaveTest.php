<?php namespace STPH\newInstanceOnSave;

class newInstanceOnSaveTest extends BaseTest
{
   function testnewInstanceOnSave(){
      $expected = 'Hello from New Instance on Save';
      $actual = $this->module->helloFrom_newInstanceOnSave();

      $this->assertSame($expected, $actual);
      
   }

}