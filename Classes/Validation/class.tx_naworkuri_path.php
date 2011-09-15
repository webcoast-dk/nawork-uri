<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author thorben
 */
class tx_naworkuri_path {

	public function returnFieldJS() {
		$typo3Path = '/' . substr(PATH_typo3, strlen(PATH_site));
		return '
			var path = value;
			if(path.substr(-1) != "/") {
				path = path + "/"
			}
			var request = new XMLHttpRequest();
			var url = "' . $typo3Path . 'ajax.php?ajaxID=tx_naworkuri::checkpathunique&path=" + path;
			if(request) {
				request.open("GET", url, false);
				request.onreadystatechange = function() {
					if(request.readyState == 4) {
						var result = Ext.decode(request.responseText);
						path = result.path;
					}
				}
				request.send();
			}
			return path;
			';
	}

	public function evaluateFieldValue($value, $is_in, &$set) {
		return $value;
	}

	public function checkPathUnique($path = NULL, $json = TRUE) {
		require_once t3lib_extMgm::extPath('nawork_uri').'lib/class.tx_naworkuri_cache.php';
		if ($path === NULL || empty($path)) {
			$path = t3lib_div::_GP('path');
		}
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
		/* @var $configReader tx_naworkuri_configReader */
		$configReader = t3lib_div::makeInstance('tx_naworkuri_configReader', $extConf['XMLPATH']);
		/* @var $cache tx_naworkuri_cache */
		$cache = t3lib_div::makeInstance('tx_naworkuri_cache', $configReader);
		$path = $cache->unique($path);
		if($json) {
			echo json_encode(array('path' => $path));
		} else {
			return $path;
		}
	}

}

?>
