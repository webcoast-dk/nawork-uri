<?php
require_once 'cooluri/link.Main.php';
require_once 'lib/class.tx_naworkuri_transformer.php';

class tx_naworkuri {

	/**
	 * decode 
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
				// set id
			$params['pObj']->id = $uri_params['id'];
		    unset($uri_params['id']);
		    	// set other params
		    $params['pObj']->mergingWithGetVars($uri_params);
		} 
	}
	
	/**
	 * convert typolink parameteres 2 uri
	 *
	 * @param array $params
	 * @param array $ref
	 */
	function params2uri(&$params, $ref) {
	
		if ( 
			$GLOBALS['TSFE']->config['config']['tx_naworkuri_enable']==1 
			&& $params['LD']['url']
		){
				// create translator
			$translator = tx_naworkuri_transformer::getInstance($translator);
			list($path,$parameters) = explode ('?',$params['LD']['url']);
			$parameter_array = $translator->explode_parameters($parameters);
				// translate
			$new_uri    = $translator->params2uri($parameter_array);
				// set result
			$params['LD']['totalURL'] = $new_uri;
		}
	}


	function redirect2uri($params, $ref) {
		if ( 
			$GLOBALS['TSFE']->config['config']['tx_naworkuri_enable']==1 
			&& empty($_GET['ADMCMD_prev']) 
			&& $GLOBALS['TSFE']->config['config']['tx_naworkuri_redirect']==1 
			&& $GLOBALS['TSFE']->siteScript 
			&& (substr($GLOBALS['TSFE']->siteScript,0,9)=='index.php' 
			|| substr($GLOBALS['TSFE']->siteScript,0,1)=='?')
		){
			
		}
		
		/*
	  if (empty($_GET['ADMCMD_prev']) && $GLOBALS['TSFE']->config['config']['tx_cooluri_enable']==1 && $GLOBALS['TSFE']->config['config']['redirectOldLinksToNew']==1 && $GLOBALS['TSFE']->siteScript && (substr($GLOBALS['TSFE']->siteScript,0,9)=='index.php' || substr($GLOBALS['TSFE']->siteScript,0,1)=='?')) {
	    
	    $ss = explode('?',$GLOBALS['TSFE']->siteScript);
	    if ($ss[1]) $pars = Link_Func::convertQuerystringToArray($ss[1]);
	    
	    $params = Array();
	    
	    if ($pars) {
	      $lt = $this->getTranslateInstance();
	      //var_dump($lt);
	      if (!$lt) return;
	
	      $url = $lt->params2cool($pars);
	      
	      if ($this->confArray['MULTIDOMAIN']) {
	        $url = explode('@',$url);  
	        $url = 'http://'.$url[0].'/'.$url[1];
	      }
	      
	      Link_Func::redirect($url);
	    }
	  }
	*/ 
		
		
		/*
	$path = preg_replace('~^/~','',$path);
    if (dirname($_SERVER['PHP_SELF'])!='' && dirname($_SERVER['PHP_SELF'])!='/' && dirname($_SERVER['PHP_SELF'])!='\\') {
      $path = dirname($_SERVER['PHP_SELF']).'/'.$path;
    }
    $path = preg_replace('~^/~','',$path);
    if (!preg_match('~^http://~',$path)) {
      $path = 'http://'.$_SERVER['HTTP_HOST'].'/'.$path;
    }
      // avoid &amp; entities in location url
    $path = html_entity_decode($path);
    
    if( !($_SERVER['REQUEST_METHOD']=='POST' && preg_match('/index.php/', $_SERVER['SCRIPT_NAME'])) ) {
      header('Location: '.$path,true,301);
      exit;
    } 
	 */
	  
	}
	
}


?>
