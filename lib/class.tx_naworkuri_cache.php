<?php

require_once (t3lib_extMgm::extPath('nawork_uri').'/lib/class.tx_naworkuri_helper.php');

class tx_naworkuri_cache {
	
	private $helper;
	
	/**
	 * Constructor
	 *
	 */
	public function __construct (){
		$this->helper = t3lib_div::makeInstance('tx_naworkuri_helper');
	}
	
	/**
	 * Read a previously created URI from cache 
	 *
	 * @param array $params Parameter Array
	 * @param string $domain current Domain
	 * @return string URI if found otherwise false
	 */
	public function read_params ($params, $domain){
		$uid   = (int)$params['id'];
		$lang  = (int)($params['L'])?$params['L']:0;
		
		unset($params['id']);
		unset($params['L']);
		 
		$imploded_params =$this->helper->implode_parameters($params);
		
		return $this->read($uid, $lang, $domain, $imploded_params);
	}
	
	/**
	 * Read Cache entry for the given URI
	 *
	 * @param string $path URI Path
	 * @param string $domain Current Domain
	 * @return array cache result
	 */
	public function read_path ($path, $domain){
		$hash_path = md5($path);
  		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
  			'pid, sys_language_uid, params',
  			'tx_naworkuri_uri', 
  			'deleted=0 AND hidden=0 AND hash_path="'.$hash_path.'" AND domain="'.$domain.'"' 
  		);
  		
        if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres) ){
        	return $row;
        }
		return false;
	}

	
	/**
	 * Find the cached URI for the given parameters 
	 * 
	 * @param int $id        : the if param
	 * @param int $lang      : the L param
	 * @param string $domain : the current domain '' for not multidomain setups
	 * @param array $params  : other  url parameters
	 * @return string        : uri wich matches to these params otherwise false 
	 */
	public function read ($id, $lang, $domain, $parameters){
			// lookup in db
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'path', 
			'tx_naworkuri_uri',
			'deleted=0 AND hidden=0 AND pid='.$id.' AND sys_language_uid='.$lang.' AND domain="'.$domain.'" AND hash_params LIKE "'.md5($parameters).'"' 
		);
		
		if ( $row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres) ){
			return $row['path'];
		} else {
			return false; 
		}
	}
	

	/**
	 * Write a new URI to cache
	 *
	 * @param array $params Parameter Array
	 * @param string $domain current Domain
	 * @param string $path preferred Path
	 * @param string $debug_info Debug Infos
	 * @return string URI wich was stored for the params
	 */
	public function write_params ($params, $domain, $path, $debug_info=''){
		$uid   = (int)$params['id'];
		$lang  = (int)($params['L'])?$params['L']:0;
		
		unset($params['id']);
		unset($params['L']);
		 
		$imploded_params = $this->helper->implode_parameters($params);
		
		return $this->write($uid, $lang, $domain, $imploded_params, $path, $debug_info);
	}
	
	/**
	 * Write a new URI-Parameter combination to the cache
	 *
	 * @param int $id id Parameter
	 * @param int $lang L Parameter
	 * @param string $domain current Domain
	 * @param string $parameters URI Paramters 
	 * @param string $path Preferred URI Path
	 * @param string $debug_info Debig Informations
	 * @return string URI wich was stored for the params
	 */
	public function write($id, $lang, $domain, $parameters, $path, $debug_info = ''){

			// make uri unique
		$path = $this->unique($path, $domain);
		
			// save in dm
		$save_record = array(
			'pid' => $id,
			'sys_language_uid' =>  $lang,
			'domain' => $domain,
			'path'   => $path,
			'params' => $parameters,
			'hash_path'   => md5($path),
			'hash_params' => md5($parameters),
			'debug_info' => $debug_info
		);
		
		$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = 1;
		$GLOBALS['TYPO3_DB']->debugOutput = 1;
		
		$dbres = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_naworkuri_uri', $save_record );
		return ($path);
		
	}

		/**
		 * Make shure this URI is unique for the current domain
		 *
		 * @param string $uri URI
		 * @return string unique URI 
		 */
	public function unique($uri, $domain){
		$tmp_uri       = $uri;
		$search_hash   = md5($tmp_uri);
		$search_domain = $domain;

		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_naworkuri_uri', 'deleted=0 AND hidden=0 AND domain="'.$search_domain.'" AND hash_path LIKE "'.$search_hash.'"' );
		
		if ( $GLOBALS['TYPO3_DB']->sql_num_rows($dbres) > 0 ){
				// make the uri unique
			$append = 0;
			do {
				$append ++;
				$tmp_uri      = $uri.$append.'/' ;
				$search_hash  = md5($tmp_uri);
				$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_naworkuri_uri', 'deleted=0 AND hidden=0 AND domain="'.$search_domain.'" AND hash_path LIKE "'.$search_hash.'"' );
			} while ( $GLOBALS['TYPO3_DB']->sql_num_rows($dbres) > 0 && $append < 100);
		}
		return $tmp_uri;
	}
	
}
?>