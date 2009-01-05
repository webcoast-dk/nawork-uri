<?php
require_once 'lib/class.tx_naworkuri_transformer.php';

class tx_naworkuri {

	/**
	 * decode uri and extract parameters
	 *
	 * @param unknown_type $params
	 * @param unknown_type $ref
	 */
	function uri2params($params, $ref) {

		if ( 
			$params['pObj']->siteScript 
			&& substr($params['pObj']->siteScript,0,9)!='index.php' 
			&& substr($params['pObj']->siteScript,0,1)!='?'
		){
				// trabnslate uri
			$translator = tx_naworkuri_transformer::getInstance($translator);
			$uri_params = $translator->uri2params($params['pObj']->siteScript);
			
				// set GET array
			$GLOBALS['_GET'] = array_merge(	$GLOBALS['_GET'],$uri_params);
			debug ($GLOBALS['_GET']);
				// set id & other params
			$params['pObj']->id = $uri_params['id'];
		    unset($uri_params['id']);
		    $params['pObj']->mergingWithGetVars($uri_params);
		} 
	}
	
	/**
	 * convert typolink parameters 2 uri
	 *
	 * @param array $params
	 * @param array $ref
	 */
	function params2uri(&$link, $ref) {
		if ( 
			$GLOBALS['TSFE']->config['config']['tx_naworkuri_enable']==1 
			&& $link['LD']['url']
		){
			list($path,$params) = explode ('?',$link['LD']['totalURL']);
			$translator = tx_naworkuri_transformer::getInstance($translator);
			$parameters = $translator->explode_parameters($params);
			$link['LD']['totalURL'] = $translator->params2uri($parameters);
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $params
	 * @param unknown_type $ref
	 */
	function redirect2uri($params, $ref) {
		if ( 
			$GLOBALS['TSFE']->config['config']['tx_naworkuri_enable']==1 
			&& empty($_GET['ADMCMD_prev']) 
			&& $GLOBALS['TSFE']->config['config']['tx_naworkuri_redirect']==1 
			&& $GLOBALS['TSFE']->siteScript 
			&& (substr($GLOBALS['TSFE']->siteScript,0,9)=='index.php' 
			|| substr($GLOBALS['TSFE']->siteScript,0,1)=='?')
		){
			list($path,$params) = explode('?',$GLOBALS['TSFE']->siteScript);
			$translator = tx_naworkuri_transformer::getInstance($translator);
			$parameters = $translator->explode_parameters($params);
			$uri = $translator->params2uri($parameters);
			if( !($_SERVER['REQUEST_METHOD']=='POST') && $path == 'index.php' ) {
      			header('Location: '.$GLOBALS['TSFE']->config['config']['baseURL'].$uri,true,301);
      			exit;
    		} 
		}
	}

		/**
		 * Update the md5 values automatically
		 *
		 * @param unknown_type $incomingFieldArray
		 * @param unknown_type $table
		 * @param unknown_type $id
		 * @param unknown_type $res
		 */
	public function processDatamap_preProcessFieldArray(&$incomingFieldArray, &$table, &$id, &$res){
		if ($table=="tx_naworkuri_uri"){
			if ($incomingFieldArray['path']   || $incomingFieldArray['path']   =='' ) $incomingFieldArray['hash_path']   = md5($incomingFieldArray['path']);
			if ($incomingFieldArray['params'] || $incomingFieldArray['params'] =='' ) $incomingFieldArray['hash_params'] = md5($incomingFieldArray['params']);
		}
	}
}


?>
