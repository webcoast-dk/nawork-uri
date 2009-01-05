<?php
require_once (PATH_t3lib.'class.t3lib_page.php');

class tx_naworkuri_transformer {
	
	private $conf;
	private $domain;
	private static $instance = null;

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
        	$cachedparams = array_merge($cachedparams, $this->explode_parameters($row['params']));
        	return $cachedparams;
        }
        
        	// @TODO handle 404 here
        	
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
		if ( $cache_uri = $this->params2uri_read_cache($params) ) {
			return $cache_uri;
		}
		
			// create new uri because no exact match was found on cache
		$original_params = $params;
		$encoded_params  = array();
		$encoded_uri     = $this->params2uri_path(&$original_params, &$encoded_params);
		
  			// find a already cached uri with these params 
  		if ( $tmp_uri = $this->params2uri_read_cache($encoded_params) ) {
  			$uri = $tmp_uri;
  		} else {
  			$uri = $this->params2uri_write_cache($encoded_params, $this->sanitize_uri($encoded_uri) ); 
  		}
  		
  			// add not encoded parameters
  		$i =0; 
  		foreach ($original_params as $key => $value){
  			$uri.= ( ($i>0)?'&':'?' ).$key.'='.$value;
  			$i++;
  		}
  		
  		return($uri);
  				
	}	
	
	public function params2uri_path(&$original_params, &$encoded_params) {

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
	 * find the cached entry for the given parameters if any 
	 * 
	 * @param array $params
	 * @return string : uri wich matches to these params otherwise false 
	 */
	public function params2uri_read_cache($params){
		
		$search_uid   = (int)$params['id'];
		$search_lang  = (int)($params['L'])?$params['L']:0;
		
		unset($params['id']);
		unset($params['L']);
		  
		$search_params = $this->implode_parameters($params);
		$search_hash   = md5($search_params);
		$search_domain = $this->domain;
				  
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('path', 'tx_naworkuri_uri', 'deleted=0 AND pid='.$search_uid.' AND sys_language_uid='.$search_lang.' AND domain="'.$search_domain.'" AND hash_params LIKE "'.$search_hash.'"' );
		if ( $row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres) ){
			return $row['path'];
		}
		return false; 
	}
	
	/**
	 * write a new uri param combination to the cache
	 *
	 * @param unknown_type $params
	 * @param unknown_type $uri
	 */
	public function params2uri_write_cache($params, $uri){

			// make the uri unique
		$uri = $this->params2uri_uri_unique($uri);
			
			// save uri to db cache
		$save_uid    = $params['id'];
		$save_lang   = $params['L'];
		$save_domain = $this->domain;
		
		unset($params['id']);
		unset($params['L']);
		
		$save_params = $this->implode_parameters($params);
		
		$save_record = array(
			'pid' => $save_uid,
			'sys_language_uid' =>  $save_lang,
			'domain' => $this->domain,
			'path'   => $uri,
			'params' => $save_params,
			'hash_path' => md5($uri),
			'hash_params' => md5($save_params)
		);
		
		$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = 1;
		$GLOBALS['TYPO3_DB']->debugOutput = 1;
		
		$dbres = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_naworkuri_uri', $save_record );
		return ($uri);
	}

		/**
		 * Make shire this URI is unique for the current domain
		 *
		 * @param string $uri URI
		 * @return string unique URI 
		 */
	public function params2uri_uri_unique($uri){
		$tmp_uri       = $uri;
		$search_hash   = md5($tmp_uri);
		$search_domain = $this->domain;

		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_naworkuri_uri', 'deleted=0 AND domain="'.$search_domain.'" AND hash_path LIKE "'.$search_hash.'"' );
		
		if ( $GLOBALS['TYPO3_DB']->sql_num_rows($dbres) > 0 ){
				// make the uri unique
			$append = 0;
			do {
				$append ++;
				$tmp_uri      = $uri.$append.'/' ;
				$search_hash  = md5($tmp_uri);
				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_naworkuri_uri', 'deleted=0 AND domain="'.$search_domain.'" AND hash_path LIKE "'.$search_hash.'"' );
			} while ( $GLOBALS['TYPO3_DB']->sql_num_rows($dbres) > 0 && $append < 100);
		}
		return $tmp_uri;
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
	  					$parts[$param_name] = $key;
  					} else if (!$value) {
  						$parts[$param_name] = str_replace('###', $params[$param_name], $key);
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
						$key = (string)$value->attributes()->key;
						$remove = (string)$value->attributes()->remove;
						if (!$remove){
							if ($key) {
								$parts[$param_name] = $key;
							}
							$encoded_params[$param_name]=$params[$param_name];
							unset($params[$param_name]);  
						} else {
							unset($params[$param_name]);
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
	public function params2uri_uriparts (&$params, &$encoded_params ){
		$parts = array();
		foreach ($this->conf->uriparts->part as $uripart) {
			
			$param_name = (string)$uripart->parameter;
  			if ( $param_name && isset($params[$param_name]) ) {
  				$table  = (string)$uripart->table;
  				$field  = (string)$uripart->field;
  				$where  = str_replace('###',$params[$param_name], (string)$uripart->where);
  				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery($field, $table, $where, '', '' ,1 );
  				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres) ){
  					foreach ($row as $key=>$value){
  						if ($value){
  							$parts[$param_name] = $value;
							$encoded_params[$param_name]=$params[$param_name];
							unset($params[$param_name]); 
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
  				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery( implode(',',$fields).',uid,pid,hidden,tx_naworkuri_exclude' , 'pages', 'uid='.$id.' AND deleted=0', '', '' ,1 );
				$row   = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres);
				if (!$row) break; // no page found
					// translate
				if (TYPO3_MODE=='FE'){
					$row   = $GLOBALS['TSFE']->sys_page->getPageOverlay($row);
				}
					// extract part
				if ($row['tx_naworkuri_exclude'] == 0 ){ 
					foreach ($fields as $field){
						if ( $row['pid']==0 ) break;
						if ( $row[$field] ) {
							$segment = $row[$field];
							array_unshift($parts,$segment);
							break; // field found
						}
					}
				}
					// continue fetching the path
				$id = $row['pid'];
				$limit--;
			}
			$encoded_params['id']=$params['id'];
			unset($params['id']);  
		}
		
		return array('id'=>implode('/',$parts));
	}
	
	
	/*
	 * Helper functions
	 */
	
	public function explode_parameters($param_string){
		$res = array();
		parse_str($param_string, $res);
		return $res; 
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
	
	/**
	 * transliterate string to iso
	 *
	 * @TODO find a better transliteration solution
	 * @param string $string
	 * @return string
	 */
	
	public static function sanitize_uri($uri) {
		
			// no punctuation and space
		$uri = str_replace( '&', '-', $uri);
		$uri = str_replace( '.', '-', $uri);
			// no whitespace
	  	$uri = preg_replace( '/[\s-]+/u', '-', $uri);
 			// remove tags
		$uri = strip_tags($uri);

/*
	 if(@function_exists('iconv'))
    {
        return iconv($from, $to, $str);
    }
    else if(@function_exists('recode_string'))
    {
        return recode_string($from . '..'  . $to, $str);
    }

// recode 
*/
		
	  	$sonderzeichen = array( 
			'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
			'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
			'è' => 'e' , 'é' => 'e', 'ê' => 'e',
			'à' => 'a', 'â' => 'a',
			'ù' => 'u', 'û' => 'u',
			'î' => 'i',
			'ô' => 'o',
			'¥' => 'Y', 'µ' => 'u', 'À' => 'A', 'Á' => 'A',
			'Â' => 'A', 'Ã' => 'A', 'Å' => 'A',
			'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
			'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I',
			'Î' => 'I', 'Ï' => 'I', 'Ð' => 'D', 'Ñ' => 'N',
			'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
			'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U',
			'Û' => 'U', 'Ý' => 'Y',
			'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
			'å' => 'a', 'æ' => 'a', 'ç' => 'c',
			'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
			'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
			'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
			'ô' => 'o', 'õ' => 'o', 'ø' => 'o',
			'ù' => 'u', 'ú' => 'u', 'û' => 'u',
			'ý' => 'y', 'ÿ' => 'y'
		 );
	 
		$uri = strtr($uri, $sonderzeichen);
	    
	//	if ( !preg_match('/[\x80-\xff]/', $uri) )
	//        return $uri;
	  
        $chars = array(
	        // Decompositions for Latin-1 Supplement
	        chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
	        chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
	        chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
	        chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
	        chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
	        chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
	        chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
	        chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
	        chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
	        chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
	        chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
	        chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
	        chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
	        chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
	        chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
	        chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
	        chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
	        chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
	        chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
	        chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
	        chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
	        chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
	        chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
	        chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
	        chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
	        chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
	        chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
	        chr(195).chr(191) => 'y',
	        // Decompositions for Latin Extended-A
	        chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
	        chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
	        chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
	        chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
	        chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
	        chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
	        chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
	        chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
	        chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
	        chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
	        chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
	        chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
	        chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
	        chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
	        chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
	        chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
	        chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
	        chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
	        chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
	        chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
	        chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
	        chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
	        chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
	        chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
	        chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
	        chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
	        chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
	        chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
	        chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
	        chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
	        chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
	        chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
	        chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
	        chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
	        chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
	        chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
	        chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
	        chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
	        chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
	        chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
	        chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
	        chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
	        chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
	        chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
	        chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
	        chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
	        chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
	        chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
	        chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
	        chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
	        chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
	        chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
	        chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
	        chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
	        chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
	        chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
	        chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
	        chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
	        chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
	        chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
	        chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
	        chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
	        chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
	        chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
	        // Euro Sign
	        chr(226).chr(130).chr(172) => 'E',
	        // GBP (Pound) Sign
	        chr(194).chr(163) => ''
	    );

	    		// lowercase
		$uri = strtolower($uri); 
        $uri = strtr($uri, $chars);
	    return $uri;
	}  
	
}
?>