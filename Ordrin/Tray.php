<?php

/* Tray Class */
class Tray {

    function __construct($items = null) {
      $this->items = $items;
      
      $this->validate();
    }

    function add($item) {
      if(!$item instanceof TrayItem) {
        throw new OrdrinExceptionBadValue(array('Tray - Validation - Items (invalid, items must be a non-empty array of TrayItems or string tray representation)'));
      }
      array_push($this->items, $item);
    }
    
    function validate() {
      $_errors = array();
      if(is_array($this->items) && !empty($this->items) && $this->items[0] instanceof TrayItem) {
        foreach($this->items as $item) {
          try {
            $item->validate();
           } catch (OrdrinExceptionBadValue $ex) {
            $_errors[] = $ex.__toString();
          }
        }
      }
      else{
      	$validation = new Validation();
      	$validation -> validateTrayItems($this->items);
      	if(!empty($validation->errors)) $_errors[] = $validation->errors[0];
      }
      
      if(!empty($_errors)) {
        throw new OrdrinExceptionBadValue($_errors);
      }
    }

    function _convertForAPI() {
      $api_string = '';
      foreach($this->items as $item){
        if(strlen($api_string) !== 0){
          $api_string .= "+";
        }
        $api_string .= $item->_convertForAPI();
      }
      return $api_string;
    }

    function __set($name, $value) {
        $this->$name = $value;
    }

    function __get($name) {
        return $this->$name;
    }
}

?>