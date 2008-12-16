<?php
require_once 'cooluri/link.Main.php';

class tx_naworkuri {

function extractArraysFromParams($params) {
  $params = (array)$params;
  foreach ($params as $k=>$v) {
    if (preg_match('~^(.+)\[([^\]]+)\]$~',$k,$matches)) {
      $params[$matches[1]][$matches[2]] = $v;
      unset($params[$k]);
    }
  }
  return $params;
}

function getTranslateInstance() {
		// read ExtConf
	$this->confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
		// read config file
	if (file_exists($this->confArray['XMLPATH'].'CoolUriConf.xml'))
		$lt = Link_Translate::getInstance($this->confArray['XMLPATH'].'CoolUriConf.xml');
	elseif (file_exists(PATH_typo3conf.'CoolUriConf.xml'))
		$lt = Link_Translate::getInstance(PATH_typo3conf.'CoolUriConf.xml');
	elseif (file_exists(dirname(__FILE__).'/cooluri/CoolUriConf.xml'))
		$lt = Link_Translate::getInstance(dirname(__FILE__).'/cooluri/CoolUriConf.xml');
	else return false;
	return $lt;
}

function cool2params($params, $ref) {
	// debug(array("cool2params", $params, $ref));
	
  if ($params['pObj']->siteScript && substr($params['pObj']->siteScript,0,9)!='index.php' && substr($params['pObj']->siteScript,0,1)!='?')	{
     
    /*  create link translator singleton */
    $lt = $this->getTranslateInstance();
    if (!$lt) return;
    
	// prefix the URIs with SERVER_NAME@ on multidomain Sites    
    if ($this->confArray['MULTIDOMAIN']) {
      if (empty(Link_Translate::$conf->cache->prefix)) {
        $this->simplexml_addChild(Link_Translate::$conf->cache,'prefix',$_SERVER['SERVER_NAME'].'@');
      } else {
        Link_Translate::$conf->cache->prefix = $_SERVER['SERVER_NAME'].'@';
      }
    }
    
    $pars = $lt->cool2params($params['pObj']->siteScript);
    
    	/* make shure the lang parameter is an int */
    $extconf =  unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cooluri']);
    if ($extconf['LANGID'] && $pars[$extconf['LANGID']] ){
    	 $pars[$extconf['LANGID']] = (int) $pars[$extconf['LANGID']];
    }

    $params['pObj']->id = $pars['id'];
    unset($pars['id']);
    $pars = $this->extractArraysFromParams($pars);
    $params['pObj']->mergingWithGetVars($pars);
  } else {
    //$link = $lt->params2cool($_GET);
    //var_dump($GLOBALS['TSFE']->config['config']['tx_cooluri_enable']);
  }
}

function getShorucutpage($page) {
    $limit = 5;
    while (!empty($page['shortcut_mode']) && $page['shortcut_mode']==1 && $page['doktype']==4 && $limit>0) {
      $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','pages','pid='.$page['uid'].$GLOBALS['TSFE']->cObj->enableFields('pages'),'','sorting','1');
      $tmp = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
      if ($tmp) $page = $tmp;
      --$limit;
    }
    return $page;
}

function simplexml_addChild($parent, $name, $value=''){
    $new_child = new SimpleXMLElement("<$name>$value</$name>");
    $node1 = dom_import_simplexml($parent);
    $dom_sxe = dom_import_simplexml($new_child);
    $node2 = $node1->ownerDocument->importNode($dom_sxe, true);
    $node1->appendChild($node2);
    return simplexml_import_dom($node2);
}

function params2cool(&$params, $ref) {

		// is cooluri enabled
	if (!$GLOBALS['TSFE']->config['config']['tx_cooluri_enable']) {
		return;
	}

		// handle shortcut pages
	if (!empty($params['args']['page']['shortcut']) && $params['args']['page']['doktype']==4) {
		$shortcut = $params['args']['page']['shortcut'];
		$limit = 5;
		while (!empty($shortcut) && $limit>0) {
			$page = $GLOBALS['TSFE']->sys_page->getPage($shortcut);
			if (!$page) break;
			$shortcut = $page['shortcut'];
			$params['args']['page'] = $page;
			--$limit;
		}
	} elseif (!empty($params['args']['page']['shortcut_mode']) && $params['args']['page']['shortcut_mode']==1 && $params['args']['page']['doktype']==4) {
		$page = $this->getShorucutpage($params['args']['page']);
		$params['args']['page'] = $page;
	}
  
		// handle external url pages
	if ($params['args']['page']['doktype']==3) {
		switch ($params['args']['page']['urltype']) {
			case 1: $url = 'http://'; break;
			case 4: $url = 'https://'; break;
			case 2: $url = 'ftp://'; break;
			case 3: $url = 'mailto:'; break;
		}
		$params['LD']['totalURL'] = $url.$params['args']['page']['url'];
		return;
	}
  		
	$tu = explode('?',$params['LD']['totalURL']);
  if (isset($tu[1])) {
    $anch = explode('#',$tu[1]);
    $pars = Link_Func::convertQuerystringToArray($tu[1]);
    
    $pars['id'] = $params['args']['page']['uid'];
    
    $lt = $this->getTranslateInstance();
    if (!$lt) {
    	debug("nolt");
    	return;
    }
    
    if ($this->confArray['MULTIDOMAIN']) {
      if (empty(Link_Translate::$conf->cache->prefix)) {
        $this->simplexml_addChild(Link_Translate::$conf->cache,'prefix',$this->getDomain($pars['id']).'@');
      } else {
        Link_Translate::$conf->cache->prefix = $this->getDomain($pars['id']).'@';
      }
    }
    
    $params['LD']['totalURL'] = $lt->params2cool($pars,'',false).(!empty($anch[1])?'#'.$anch[1]:'');

    if (isset($GLOBALS['TSFE']->config['config']['absRefPrefix'])) {
     	$params['LD']['totalURL'] = $GLOBALS['TSFE']->config['config']['absRefPrefix']. $params['LD']['totalURL'];
    }
    
    if ($this->confArray['MULTIDOMAIN']) {
      $params['LD']['totalURL'] = explode('@',$params['LD']['totalURL']);  
      if ($params['LD']['totalURL'][0]==$_SERVER['SERVER_NAME'])
        $params['LD']['totalURL'] = $params['LD']['totalURL'][1];
      else
        $params['LD']['totalURL'] = 'http://'.$params['LD']['totalURL'][0].'/'.$params['LD']['totalURL'][1];
    }
  }
}

function getDomain($id) {
  $enable = ' AND pages.deleted=0 AND pages.hidden=0';
  $enable2 = ' AND deleted=0 AND hidden=0';
  $db = &$GLOBALS['TYPO3_DB'];
  $max = 10;
  while ($max>0 && $id) {
    
    //$page = $GLOBALS['TSFE']->sys_page->getPage($id);
    $q = $db->exec_SELECTquery('pages.title, pages.pid, pages.is_siteroot, pages.uid AS id, sys_domain.domainName, sys_domain.redirectTo','pages LEFT JOIN sys_domain ON pages.uid=sys_domain.pid','pages.uid='.$id.$enable.' AND sys_domain.hidden=0','','sys_domain.sorting');
    $page = $db->sql_fetch_assoc($q);
        
    $temp  = $db->exec_SELECTquery('COUNT(*) as num','sys_template','pid='.$id.' AND root=1'.$enable2);
    $count = $db->sql_fetch_assoc($temp);
    
    if ($page['domainName'] && !$page['redirectTo']) {
      return ereg_replace('^.*://(.*)/?$','\\1',ereg_replace('/$','',$page['domainName']));
    }
    
    if ($count['num']>0 || $page['is_siteroot']==1) { return $_SERVER['SERVER_NAME']; }
    
    
    $id = $page['pid'];
    --$max;
  }
  return $_SERVER['SERVER_NAME'];
}

function goForRedirect($params, $ref) {
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
        /*if ($url[0]==$_SERVER['SERVER_NAME'])
          $url = $url[1];
        else*/
          $url = 'http://'.$url[0].'/'.$url[1];
      }
      
      Link_Func::redirect($url);
    }
  }
}

function getPageTitleBE($conf,$value) {
  $enable = ' AND deleted=0 AND hidden=0';
  $db = &$GLOBALS['TYPO3_DB'];

  if (empty($conf->alias)) $sel = (string)$conf->title;
	else $sel = (string)$conf->alias;
  
  $id = $value[(string)$conf->saveto];
  
  $confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cooluri']);
  $langVar = $confArray['LANGID'];
  
  $langId = empty($value[$langVar])?false:$value[$langVar];
  
  $pagepath = Array();
  
  if (empty($conf->alias)) $sel = (string)$conf->title;
  else $sel = (string)$conf->alias;
  $sel = t3lib_div::trimExplode(',',$sel);
  
  $max = 15;
  
  while ($max>0 && $id) {
    
    //$page = $GLOBALS['TSFE']->sys_page->getPage($id);
    $q = $db->exec_SELECTquery('*','pages','uid='.$id.$enable);
    $page = $db->sql_fetch_assoc($q);
    
    $temp = $db->exec_SELECTquery('COUNT(*) as num','sys_template','pid='.$id.' AND root=1'.$enable);
    $count = $db->sql_fetch_assoc($temp);
    if ($count['num']>0 || $page['is_siteroot']==1) { return $pagepath; }
    
    if ($langId) {
      $q = $db->exec_SELECTquery('*','pages_language_overlay','pid='.$id.' AND sys_language_uid='.$langId.$enable);
      echo mysql_error();
      $lo = $db->sql_fetch_assoc($q);
      if ($lo) {
        unset($lo['uid']);
        unset($lo['pid']);
        $page = array_merge($page,$lo);
      }
    }
    if (!$page) break;
    
    if ($page['tx_cooluri_exclude']==1 && !empty($pagepath)) {
      ++$max;
      $id = $page['pid'];
      continue;
    }
    
    foreach ($sel as $s) {
      if (!empty($page[$s])) {
        $title = $page[$s];
        break;
      }
    }
    
    if (!empty($conf->sanitize) && $conf->sanitize==1) {
      $pagepath[] = Link_Func::sanitize_title_with_dashes($title);
    } elseif (!empty($conf->t3conv) && $conf->t3conv==1) {
      $pagepath[] = Link_Func::specCharsToASCII($val);
    } elseif (!isset($conf->urlize) || $conf->urlize!=0) {
      $pagepath[] = Link_Func::URLize($title);
    } else {
      $pagepath[] = urlencode($title);
    }
    $id = $page['pid'];
    
    --$max;
  }
  return $pagepath;
}

function getPageTitle($conf,$value) {
  if (!$GLOBALS['TSFE'] || !$GLOBALS['TSFE']->cObj) return tx_cooluri::getPageTitleBE($conf,$value);
  $db = &$GLOBALS['TYPO3_DB'];
  
  if (empty($conf->alias)) $sel = (string)$conf->title;
	else $sel = (string)$conf->alias;
  
  $id = $value[(string)$conf->saveto];
    
  $confArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['cooluri']);
  $langVar = $confArray['LANGID'];
  
  $langId = empty($value[$langVar])?false:$value[$langVar];
  
  $pagepath = Array();
  
  if (empty($conf->alias)) $sel = (string)$conf->title;
  else $sel = (string)$conf->alias;
  $sel = t3lib_div::trimExplode(',',$sel);
  
  $max = 15;
  
  while ($max>0 && $id) {
  
    if (!is_numeric($id)){
    	$id =  $GLOBALS['TSFE']->sys_page->getPageIdFromAlias($id);
    }
    
    if ($value['L'] > 0) {
    	$page = $GLOBALS['TSFE']->sys_page->getPage($id);    	
    } else {
    	$page = tx_naworkuri::getPage($id);
    }
    
    $temp = $db->exec_SELECTquery('COUNT(*) as num','sys_template','pid='.$id.' AND root=1'.$GLOBALS['TSFE']->cObj->enableFields('sys_template'));
    $count = $db->sql_fetch_assoc($temp);
    if ($count['num']>0 || $page['is_siteroot']==1) return $pagepath;
    
    if ($langId) {
      $q = $db->exec_SELECTquery('*','pages_language_overlay','pid='.$id.' AND sys_language_uid='.$langId.$GLOBALS['TSFE']->cObj->enableFields('pages_language_overlay'));
      $lo = $db->sql_fetch_assoc($q);
      if ($lo) {
        unset($lo['uid']);
        unset($lo['pid']);
        $page = array_merge($page,$lo);
      }
    }
    if (!$page) break;
    
    if ($page['tx_cooluri_exclude']==1 && !empty($pagepath)) {
      ++$max;
      $id = $page['pid'];
      continue;
    }
    
    foreach ($sel as $s) {
      if (!empty($page[$s])) {
        $title = $page[$s];
        break;
      }
    }
    
    if (!empty($conf->sanitize) && $conf->sanitize==1) {
      $pagepath[] = Link_Func::sanitize_title_with_dashes($title);
    } elseif (!empty($conf->t3conv) && $conf->t3conv==1) {
      $pagepath[] = Link_Func::specCharsToASCII($title);
    } elseif (!isset($conf->urlize) || $conf->urlize!=0) {
      $pagepath[] = Link_Func::URLize($title);
    } else {
      $pagepath[] = urlencode($title);
    }
    $id = $page['pid'];
    
    --$max;
    
    if (!empty($conf->maxsegments) && count($pagepath)>=(int)$conf->maxsegments) $max = 0;
  }
  return $pagepath;
}

	function getPage($uid)  {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'uid='.intval($uid));
        if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
        	if (is_array($row)) {       		
                return $row;
            }
            
            return Array();
        }
	 }
}


?>
