<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Reader
 *
 * @author thorben
 */
class Tx_NaworkUri_Configuration_Reader implements t3lib_Singleton {
	/**
	 *
	 * @var Tx_NaworkUri_Configuration_Configuration
	 */
	protected $configuration;
	public function __construct($filename) {
		if (!empty($filename)) {
			$xml = new SimpleXMLElement(file_get_contents($filename)); 
		}
	}
}

?>
