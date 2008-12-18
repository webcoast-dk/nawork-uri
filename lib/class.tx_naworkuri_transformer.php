<?php
class tx_naworkuri_transformer {
	
	private $conf;
	private static $instance = null;

	/**
	 * constructor
	 *
	 * @param SimpleXMLElement $configXML
	 */
	public function __construct ($configXML=false){
		if ($configXML) {
			$this->conf = $configXML;
		} else {
			$this->conf = new SimpleXMLElement(file_get_contents( t3lib_extMgm::extPath('nawork_uri').'/lib/default_UriConf.xml'));
		}
	}
	
	/**
	 * Create a singleton instance of tx_naworkuri_transformer
	 *
	 * @param SimpleXMLElement $xml_config_file
	 * @return tx_naworkuri_transformer
	 */
	public static function getInstance( $xml_config_file ) {
		if (!self::$instance) {
				 
			$confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
			$config_xml = false;
			
			if (file_exists($confArray['XMLPATH'].'NaworkUriConf.xml')){
				$config_xml = new SimpleXMLElement(file_get_contents( $confArray['XMLPATH'].'NaworkUriConf.xml'));
			} elseif (file_exists(PATH_typo3conf.'NaworkUriConf.xml')){
				$config_xml = new SimpleXMLElement(file_get_contents( PATH_typo3conf.'NaworkUriConf.xml'));
			} elseif (file_exists(dirname(__FILE__).'/cooluri/NaworkUriConf.xml')) {
				$config_xml = new SimpleXMLElement(file_get_contents( dirname(__FILE__).'/cooluri/NaworkUriConf.xml'));
			}
			
			self::$instance = new tx_naworkuri_transformer($config_xml);
		}
		return self::$instance;
	}
  
	/**
	 * Convert the uri path to the request parameters
	 *
	 * @param string $uri
	 */
	public function uri2params ($uri = ''){
			// remove opening slash
		if (empty($uri)) return;
      
			// look into the db
			// @TODO check the path for possible sql injections
		list($path,$params) = explode('?',$uri);
  		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid, sys_language_uid, params','tx_naworkuri_uri', 'deleted=0 AND path="'.$path.'"' );
        if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres) ){
        	$cachedparams = Array('id'=>$row['pid'],'L'=>$row['sys_language_uid']);
        	$cachedparams = array_merge($cachedparams, $this->explode_parameters($row['params']));
        	return $cachedparams;
        }
		return;
	}
		
	/**
	 * Enter description here...
	 *
	 * @param array $params
	 * @return string $uri encoded uri
	 */
	public function params2uri ($params){
		
			// find already created uris
		$parameters = $params;
		$save_uid   = $parameters['id'];
		$save_lang  = ($parameters['L'])?$parameters['L']:0;
		unset($parameters['id']);
		unset($parameters['L']);
		  
		$save_params = $this->implode_parameters($parameters);
		  
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, path', 'tx_naworkuri_uri', 'deleted=0 AND pid='.$save_uid.' AND sys_language_uid='.$save_lang.' AND params LIKE "'.$save_params.'"' );
		if ( $row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres) ){
				// an exact match was found return the url directly
			return $row['path'];
		}
			
			// create new uri because no exact match was found on cache

		$original_params = $params;
		$encoded_params = array();

			// transform the parameters
		$predef_path    = $this->params2uri_predefinedparts(&$original_params, &$encoded_params );
 		$valuemaps_path = $this->params2uri_valuemaps(&$original_params, &$encoded_params );
		$uriparts_path  = $this->params2uri_uriparts(&$original_params, &$encoded_params );
 		$page_path      = $this->params2uri_pagepath(&$original_params, &$encoded_params );
 		
  		if (!empty($this->conf->partorder) && !empty($this->conf->partorder->part)) {
  			$partorder = Array();
  			foreach ($this->conf->partorder->part as $p) {
  				$partorder[] = (string)$p;
  			}  
  		} else {
  			$partorder = Array('pagepath','uriparts','valuemaps','predefinedparts');
  		}
  			
  			// order the parts
		$path = array();
  		foreach ($partorder as $part_key) {
  			switch ($part_key) {
  				case 'predefinedparts': $path = array_merge($path,$predef_path);     break;
  				case 'valuemaps':       $path = array_merge($path,$valuemaps_path);  break;
  				case 'uriparts':   	    $path = array_merge($path,$uriparts_path);   break;
  				case 'pagepath':        $path = array_merge($path,$page_path);       break;
  			}
  		}
  		
  			// implode the uri
  		$uri = implode('/',$path).'/';
  		
  			// make the uri unique
  			
  			// save uri to db
  			
  		
  			// add not encoded parameters
  		$i =0; 
  		foreach ($original_params as $key => $value){
  			$uri.= ( ($i>0)?'&':'?' ).$key.'='.$value;
  			$i++;
  		}
  		
  		return($uri);
  				
	}	

	/**
	 * encode the predifined parts
	 *
	 * @param array $params : already encoded Parameters
	 * @param array $encoded_params : encoded Parameters
	 * @return array : Path Elements for final URI 
	 */
	public function params2uri_predefinedparts(&$params, &$encoded_params ){
		
		$parts = array();
  		foreach ($this->conf->predefinedparts->part as $part) {

			$param_name = (string)$part->parameter;
  			if ( $param_name && isset($params[$param_name]) ) {
  				$value  = (string)$part->value;
  				$key    = (string)$part->attributes()->key;
			
  				if (!$key) {
  					if (!$value && $value!=='0' ) {
	  					$encoded_params[$param_name]=$params[$param_name];
	  					unset($params[$param_name]);
	  				} elseif ($params[$param_name] == $value) {
  						$encoded_params[$param_name]=$params[$param_name];
  						unset($params[$param_name]);
	  				}
  				} else {
  					if ($value && $params[$param_name] == $value ){
  						$encoded_params[$param_name]=$params[$param_name];
	  					unset($params[$param_name]);
	  					$parts[] = $key;
  					} else if (!$value) {
  						$parts[] = str_replace('###', $params[$param_name], $key);
  						$encoded_params[$param_name]=$params[$param_name];
						unset($params[$param_name]);  						
  					}
  				}
  			}
  		} 
  		return $parts;
	}
	
	/**
	 * Encode tha Valuemaps 
	 *
	 * @param unknown_type $params
	 * @param unknown_type $encoded_params
	 * @return unknown
	 */
	public function params2uri_valuemaps (&$params, &$encoded_params ){
		$parts = array();
		foreach ($this->conf->valuemaps->valuemap as $valuemap) {
			$param_name = (string)$valuemap->parameter;
  			if ( $param_name && isset($params[$param_name]) ) {
				foreach($valuemap->value as $value){
					if ( (string)$value == $params[$param_name]){
						$part = (string)$value->attributes()->key;
						if ($part)  $parts[] = $part;
						$encoded_params[$param_name]=$params[$param_name];
						unset($params[$param_name]);  	
					}  	
				}			
  			}
		}
		return $parts;
	}
	
	public function params2uri_uriparts (&$params, &$encoded_params ){
		$parts = array();
		foreach ($this->conf->uriparts->part as $uripart) {
			
			$param_name = (string)$uripart->parameter;
  			if ( $param_name && isset($params[$param_name]) ) {
  				$table  = (string)$uripart->table;
  				$field  = (string)$uripart->field;
  				$where  = str_replace('###',$params[$param_name], (string)$uripart->where);
  				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery($field, $table, $where, '', '' ,1 );
  				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($dbres) ){
  					foreach ($row as $value){
  						if ($value){
  							$parts[] = $value;
							$encoded_params[$param_name]=$params[$param_name];
							unset($params[$param_name]);  		
  						}
  					}
  				}
  			}
		}
		return $parts;
	}
	
	public function params2uri_pagepath (&$params, &$encoded_params ) {
		$parts = array();
		if ($this->conf->pagepath && $params['id']){
			 
				// read alias and cast to int
			if (is_numeric($params['id']) ){
				$id = (int)$params['id'];
			} else {
				$id = (int)$GLOBALS['TSFE']->sys_page->getPageIdFromAlias($params['id']);
			}
			
				// get setup
			$limit  = (int)(string)$this->conf->pagepath->limit;
			if (!$limit) $limit=10;
			$fields  = explode(',', (string)$this->conf->pagepath->field );
			
				// walk the pagepath
			while ($limit && $id > 0){
  				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery( implode(',',$fields).',uid,pid,hidden' , 'pages', 'uid='.$id.' AND deleted=0', '', '' ,1 );
				$row   = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
				if (!$row) break; // no page found
				foreach ($fields as $field){
					if ( $row['pid']==0 ) break;
					if ( $row[$field] ) {
						array_unshift($parts,$row[$field]);
						break; // field found
					}
					
				}
				$id = $row['pid'];
				$limit--;
			}
			$encoded_params['id']=$params['id'];
			unset($params['id']);  
		}
		return $parts;
	}
	
	/*
	 * Helper functions
	 */
	
	public function explode_parameters($param_string){
		$result = array();
		$tmp = explode('&',$param_string);
		foreach ($tmp as $part){
			list($key,$value) = explode('=',$part);
			$result[$key] = $value;
		}
		return $result;
	}
  
	public function implode_parameters($params_array){
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