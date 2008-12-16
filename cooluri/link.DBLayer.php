<?php

class Link_DBLayer {

private $conn;
protected static $_instance = null;

protected function __construct() {

}

public static function getInstance() {
  if(!self::$_instance instanceof self){
    self::$_instance = new self();
  }
  return self::$_instance;
}

public function query($stmt) {
  return $GLOBALS['TYPO3_DB']->sql_query($stmt);
}

public function fetch($res) {
  return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
}

public function fetch_row($res) {
  return $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
}

public static function escape($string,$tp = 'link_') {
  return $GLOBALS['TYPO3_DB']->quoteStr($string,$tp.'cache');
}

public function error() {
  return $GLOBALS['TYPO3_DB']->sql_error();
}

public function num_rows($res) {
  return $GLOBALS['TYPO3_DB']->sql_num_rows($res);
}

public function affected_rows() {
  return $GLOBALS['TYPO3_DB']->sql_affected_rows();
}


}

?>
