<?php

class Link_DB extends Link_DBLayer {
  
  public function __construct() {

  }
  
  public static function getInstance() {
    if(!parent::$_instance instanceof self){
      parent::$_instance = new self();
    }
    return parent::$_instance;
  }
  
}

?>
