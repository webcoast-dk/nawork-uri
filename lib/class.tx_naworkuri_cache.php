<?php

require_once (t3lib_extMgm::extPath('nawork_uri').'/lib/class.tx_naworkuri_helper.php');

class tx_naworkuri_cache {
				
	/**
	 * find the cached uri entry for the given parameters if any 
	 * 
	 * @param array $params
	 * @return string : uri wich matches to these params otherwise false 
	 */
	public function read ($id, $lang, $domain, $parameters){

			// sort parameters
		$exploded_params = explode('&',$parameters);
		sort($exploded_params);
		$parameters = implode('&',$exploded_params);
		
		
			// lookup in db
		$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'path', 
			'tx_naworkuri_uri',
			'deleted=0 AND pid='.$id.' AND sys_language_uid='.$lang.' AND domain="'.$domain.'" AND hash_params LIKE "'.md5($parameters).'"' 
		);
		
		if ( $row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres) ){
			return $row['path'];
			
		} else {
			debug(array(
				'cache_search_failure',
				$id, $lang, $domain, $parameters,
				md5($parameters)
			));
			return false; 
		}
	}
	
	/**
	 * write a new uri param combination to the cache
	 *
	 * @param unknown_type $params
	 * @param unknown_type $uri
	 */
	
	// public function write($params, $uri){
	public function write($id, $lang, $domain, $parameters, $path, $debug_info = ''){

			// sort parameters
		$exploded_params = explode('&',$parameters);
		sort($exploded_params);
		$parameters = implode('&',$exploded_params);
		
			// make uri unique
		$path = $this->unique($path, $domain);
		
		debug(array(
				'cache_write_failure',
				$id, $lang, $domain, $parameters,
				md5($parameters),
				$path
		));
			
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
}
?>