<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DomainUserFunc
 *
 * @author thorben
 */
class Tx_NaworkUri_UserFunc_DomainUserFunc {
	public function itemsProcFunc(&$parameters, $tceForms) {
		foreach($parameters['items'] as $index => $item) {
			$parameters['items'][$index][1] = $item[0];
		}
	}
}

?>
