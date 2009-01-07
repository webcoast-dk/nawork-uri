<?php
require_once (PATH_t3lib.'class.t3lib_page.php');

require_once (t3lib_extMgm::extPath('nawork_uri').'/lib/class.tx_naworkuri_cache.php');
require_once (t3lib_extMgm::extPath('nawork_uri').'/lib/class.tx_naworkuri_helper.php');

class tx_naworkuri_transformer {
	
	private $conf;
	private $domain;
	private static $instance = null;
	
	/*
	 * @var tx_naworkuri_cache
	 */
	private $cache;

	/**
	 * constructor
	 *
	 * @param SimpleXMLElement $configXML
	 */
	public function __construct ($configXML=false){
			// read configuration
		if ($configXML) {
			$this->conf = $configXML;
		} else {
			$this->conf = new SimpleXMLElement(file_get_contents( t3lib_extMgm::extPath('nawork_uri').'/lib/default_UriConf.xml'));
		}
			// check multidomain mode
		if ( (string)$this->conf->multidomain == '1' ) {
			$this->domain = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		} else {
			$this->domain = '';
		}
		
		$this->cache  = t3lib_div::makeInstance('tx_naworkuri_cache');
		$this->helper = t3lib_div::makeInstance('tx_naworkuri_helper');
		
	}
	
	/**
	 * Create a singleton instance of tx_naworkuri_transformer
	 *
	 * @param SimpleXMLElement $xml_config_file
	 * @return tx_naworkuri_transformer
	 */
	public static function getInstance( $xml_config_file ) {
		if (!self::$instance) {
				 
			$confArray = unserialize( $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
			$config_xml = false;
			
			if (file_exists( PATH_site.$confArray['XMLPATH'] )){
				$config_xml = new SimpleXMLElement(file_get_contents( PATH_site.$confArray['XMLPATH']));
			} elseif (file_exists(PATH_typo3conf.'NaworkUriConf.xml')){
				$config_xml = new SimpleXMLElement(file_get_contents( PATH_typo3conf.'NaworkUriConf.xml'));
			}
			
			self::$instance = new tx_naworkuri_transformer($config_xml);
		}
		return self::$instance;
	}
	
	public function getConfiguration(){
		return $this->conf;
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
		list($path,$params) = explode('?',$uri);
		$hash_path = md5($path);
  		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid, sys_language_uid, params','tx_naworkuri_uri', 'deleted=0 AND hash_path="'.$hash_path.'" AND domain="'.$this->domain.'"' );
        if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres) ){
        	$cachedparams = Array('id'=>$row['pid'],'L'=>$row['sys_language_uid']);
        	$cachedparams = array_merge($cachedparams, $this->helper->explode_parameters($row['params']));
        	return $cachedparams;
        }
		return false;
	}
		
	/**
	 * Enter description here...
	 *
	 * @param array $params
	 * @return string $uri encoded uri
	 */
	public function params2uri ($params){
		
			// find already created uri with exactly these parameters
		$search = $params;
		$search_uid   = (int)$search['id'];
		$search_lang  = (int)($search['L'])?$search['L']:0;
		
		unset($search['id']);
		unset($search['L']);
		  
		$search_params = $this->helper->implode_parameters($search);
		$search_hash   = md5($search_params);
		$search_domain = $this->domain;
			
		if ( $cache_uri = $this->cache->read($search_uid, $search_lang, $search_domain, $search_params) ) {
			return $cache_uri;
		}
		
			// create new uri because no exact match was found in cache
		$original_params  = $params;
		$encoded_params   = array();
		$unencoded_params = $original_params;
		$encoded_uri      = $this->params2uri_process_parameters(&$original_params, &$unencoded_params, &$encoded_params);
		 
  			// check for cache entry with these uri an create cache entry if needed 
  		$cache_data  = $encoded_params;
		$cache_uid   = (int)$cache_data['id'];
		$cache_lang  = (int)($cache_data['L'])?$cache_data['L']:0;
		
		unset($cache_data['id']);
		unset($cache_data['L']);
		  
		$cache_params = $this->helper->implode_parameters($cache_data);
		$cache_path   = $this->helper->sanitize_uri($encoded_uri);
		$cache_domain = $this->domain;

		$cache_uri = $this->cache->read($cache_uid, $cache_lang, $cache_domain, $cache_params);
  		if ( $cache_uri !== false ) {
  			$uri = $cache_uri;
  		} else {
  			$debug_info = '';
  			$debug_info .= "original_params  : ".$this->helper->implode_parameters($original_params).chr(10);
  			$debug_info .= "encoded_params   : ".$this->helper->implode_parameters($encoded_params).chr(10);
  			$debug_info .= "unencoded_params : ".$this->helper->implode_parameters($unencoded_params).chr(10);
  			  			
  			$uri = $this->cache->write($cache_uid, $cache_lang, $cache_domain, $cache_params, $cache_path, $debug_info); 
  		}
  		
  			// read not encoded parameters
  		$i =0; 
  		foreach ($unencoded_params as $key => $value){
  			$uri.= ( ($i>0)?'&':'?' ).$key.'='.$value;
  			$i++;
  		}
  		
  		return($uri);
  				
	}	
	
	public function params2uri_process_parameters(&$original_params, &$unencoded_params, &$encoded_params) {

			// transform the parameters
		$predef_path    = $this->params2uri_predefinedparts(&$original_params, &$unencoded_params, &$encoded_params);
 		$valuemaps_path = $this->params2uri_valuemaps(&$original_params, &$unencoded_params, &$encoded_params);
		$uriparts_path  = $this->params2uri_uriparts(&$original_params, &$unencoded_params, &$encoded_params);
 		$page_path      = $this->params2uri_pagepath(&$original_params, &$unencoded_params, &$encoded_params);
 		
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
  		
  		
  			// order the params 
  		$res = array();
  		foreach ($this->conf->paramorder->param as $param) {
  			$param_name = (string)$param;
  			if (isset($path[$param_name]) && $segment = $path[$param_name]){
  				if ($segment) $res[]=$segment;
  				unset($path[$param_name]);
  			}
  		}
  			// add params with not specified order
  		foreach ($path as $param=>$path_segment) {
  			if ($path_segment) $res[]=$path_segment;
  		}
  			// return 
  		if (count($res)){
  			return (implode('/',$res).'/');
  		} else {
  			return '';
  		}
	}
	

	/**
	 * encode the predifined parts
	 *
	 * @param array $params : already encoded Parameters
	 * @param array $encoded_params : encoded Parameters
	 * @return array : Path Elements for final URI 
	 */
	public function params2uri_predefinedparts(&$original_params, &$unencoded_params, &$encoded_params ){
		
		$parts = array();
  		foreach ($this->conf->predefinedparts->part as $part) {

			$param_name = (string)$part->parameter;
  			if ( $param_name && isset($unencoded_params[$param_name]) ) {
  				$value  = (string)$part->value;
  				$key    = (string)$part->attributes()->key;
			
  				if (!$key) {
  					if (!$value && $value!=='0' ) {
	  					$encoded_params[$param_name]=$unencoded_params[$param_name];
	  					unset($unencoded_params[$param_name]);
	  				} elseif ($unencoded_params[$param_name] == $value) {
  						$encoded_params[$param_name]=$unencoded_params[$param_name];
  						unset($unencoded_params[$param_name]);
	  				}
  				} else {
  					if ($value && $unencoded_params[$param_name] == $value ){
  						$encoded_params[$param_name]=$unencoded_params[$param_name];
	  					unset($unencoded_params[$param_name]);
	  					$parts[$param_name] = $key;
  					} else if (!$value) {
  						$parts[$param_name] = str_replace('###', $unencoded_params[$param_name], $key);
  						$encoded_params[$param_name]=$unencoded_params[$param_name];
						unset($unencoded_params[$param_name]);  						
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
	public function params2uri_valuemaps (&$original_params, &$unencoded_params, &$encoded_params ){
		$parts = array();
		foreach ($this->conf->valuemaps->valuemap as $valuemap) {
			$param_name = (string)$valuemap->parameter;
  			if ( $param_name && isset($unencoded_params[$param_name]) ) {
				foreach($valuemap->value as $value){
					if ( (string)$value == $unencoded_params[$param_name]){
						$key = (string)$value->attributes()->key;
						$remove = (string)$value->attributes()->remove;
						if (!$remove){
							if ($key) {
								$parts[$param_name] = $key;
							}
							$encoded_params[$param_name]=$unencoded_params[$param_name];
							unset($unencoded_params[$param_name]);  
						} else {
							unset($unencoded_params[$param_name]);
						}	
					}  	
				}			
  			}
		}
		return $parts;
	}
	
	/**
	 * Enter description here...
	 *
	 * @TODO add record translation handling
	 * 
	 * @param array $params
	 * @param array $encoded_params
	 * @return unknown
	 */
	public function params2uri_uriparts (&$original_params, &$unencoded_params, &$encoded_params){
		$parts = array();
		foreach ($this->conf->uriparts->part as $uripart) {
			
			$param_name = (string)$uripart->parameter;
  			if ( $param_name && isset($unencoded_params[$param_name]) ) {
  				$table  = (string)$uripart->table;
  				$field  = (string)$uripart->field;
  				$where  = str_replace('###',$unencoded_params[$param_name], (string)$uripart->where);
  				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery($field, $table, $where, '', '' ,1 );
  				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres) ){
  					foreach ($row as $key=>$value){
  						if ($value){
  							$parts[$param_name] = $value;
							$encoded_params[$param_name]=$unencoded_params[$param_name];
							unset($unencoded_params[$param_name]); 
							break;	
  						}
  					}
  				}
  			}
		}

		return $parts;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $params
	 * @param unknown_type $encoded_params
	 * @return unknown
	 */
	public function params2uri_pagepath (&$original_params, &$unencoded_params, &$encoded_params) {
		$parts = array();
		if ($this->conf->pagepath && $unencoded_params['id']){
			 
				// read alias and cast to int
			if (is_numeric($unencoded_params['id']) ){
				$id = (int)$unencoded_params['id'];
			} else {
				$id = (int)$GLOBALS['TSFE']->sys_page->getPageIdFromAlias($unencoded_params['id']);
			}
			
				// get setup
			$limit  = (int)(string)$this->conf->pagepath->limit;
			if (!$limit) $limit=10;
			$fields  = explode(',', 'tx_naworkuri_pathsegment,'.(string)$this->conf->pagepath->field );
			
				// walk the pagepath
			while ($limit && $id > 0){
  				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery( implode(',',$fields).',uid,pid,hidden,tx_naworkuri_exclude' , 'pages', 'uid='.$id.' AND deleted=0', '', '' ,1 );
				$row   = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
				if (!$row) break; // no page found
				
					// translate pagepath if needed
					// @TODO some languages have to be excluded here
				$lang = 0;
				if ( $GLOBALS['TSFE'] && $GLOBALS['TSFE']->config['config']['sys_language_uid']){
					$lang = (int)$GLOBALS['TSFE']->config['config']['sys_language_uid'];
				}
				if (isset($original_params['L'])) {
					$lang = (int)$original_params['L'];
				}
				
				if ( $lang > 0 ){
					$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery( '*' , 'pages_language_overlay', 'pid='.$id.' AND deleted=0 AND sys_language_uid='.$lang, '', '' ,1 );
					$translated_row   = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
					foreach ($fields as $field){
						if ($translated_row[$field] ) {
							$row[$field] = $translated_row[$field];
						}
					}
				}
					// extract part
				if ($row['tx_naworkuri_exclude'] == 0 ){
					if ($row['pid']>0){
						foreach ($fields as $field){
							if ( $row[$field] ) {
								$segment = trim($row[$field]);
								array_unshift($parts,$segment);
								break; // field found
							}
						}
					} elseif ( $row['pid']==0 && $row['tx_naworkuri_pathsegment'] ){
						$segment = trim($row['tx_naworkuri_pathsegment']);
						array_unshift($parts,$segment);
					}  
				}
					// continue fetching the path
				$id = $row['pid'];
				$limit--;
			} 
			
			
			$encoded_params['id']=$unencoded_params['id'];
			unset($unencoded_params['id']);  
		}
		
		return array('id'=>implode('/',$parts));
	}
	
	
	
}
?>