<?php
class tx_naworkuri_transformer {
	
	var $conf;
	var $rootline;
	
	function loadConfiguration($configXML){
		$this->conf = $configXML;
	}
	
	function loadRootline($rootline){
		$this->rootline = $rootline;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param array $params
	 * @return string $uri
	 */
	function params2uri ($params){
		return '';
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $uri
	 */
	function uri2params ($uri){
		return array();
	}
	
	public function explode_params($param_string){
		$result = array();
		$tmp = explode('&',$param_string);
		foreach ($tmp as $part){
			list($key,$value) = explode('=',$part);
			$result[$key] = $value;
		}
		return $result;
	}
  
	public function implode_params($params_array){
		$result = '';
		$i = 0;
		foreach ($params_array as $key => $value){
			if ($i>0)  $result .= '&';
			$result .= $key.'='.$value;
			$i++;
		}
		return $result;
	}
	
}
?>