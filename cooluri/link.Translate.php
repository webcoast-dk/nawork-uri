<?php

class Link_Translate {
  
  public static $conf = null;
  
  private static $coolParamsKeys = false;
  
  private static $instance = null;
  
  public static $uri = Array();
  
  public function __construct($xmlconffile = 'CoolUriConf.xml') {
    $conf = new SimpleXMLElement(file_get_contents($xmlconffile));
    self::$conf = $conf;
  }
  
  public static function getInstance($xmlconffile = 'CoolUriConf.xml') {
    if (!self::$instance) {
      self::$instance = new Link_Translate($xmlconffile);
    }
    return self::$instance;
  }
  
  public function cool2params($uri = '') {
    $lConf = self::$conf;
    // check if coolUris are active, if so, proceed with translation
    if (!empty($lConf->cooluris) && $lConf->cooluris==1) {
      
      // composer URI from a variable (usually compose from $_SERVER array)
      if (!empty($lConf->uri)) {
        $var = empty($lConf->uri->var)?'_SERVER':(string)$lConf->uri->var;
        if (!empty($lConf->uri->part)) {
          $uri = '';
          $var = $GLOBALS[$var];
          foreach ($lConf->uri->part as $p) {
            $uri .= $var[(string)$p];
          }
        } else {
          $uri = $GLOBALS[$var];
        }
      }
      // now we have in $uri our URI to parse

      // let's remove uninteresting parts (those are not even cached)
      if (!empty($lConf->removeparts) && !empty($lConf->removeparts->part)) {
      	$originaluri = $uri;
        foreach ($lConf->removeparts->part as $p) {
          if (!empty($p['regexp']) && $p['regexp']==1) { // there's a regexp
            $uri = preg_replace('~'.(string)$p.'~','',$uri);
          } else {
            $uri = str_replace((string)$p,'',$uri);
          }
        }
        // if something was stripped (and something is still left, redirect is needed)
        if (!empty($lConf->removeparts['redirect']) && $lConf->removeparts['redirect']==1)
          if (!empty($uri) && $uri!=$originaluri) Link_Func::redirect(Link_Func::prepareforOutput($uri,$lConf));
      }
      $temp = explode('?',$uri);
      if (!empty($lConf->urlsuffix)) {
        $temp[0] = preg_replace('~'.Link_Func::addregexpslashes((string)$lConf->urlsuffix).'$~','',$temp[0]);
      }
      if (!empty($lConf->urlprefix)) {
        $temp[0] = preg_replace('~^'.Link_Func::addregexpslashes((string)$lConf->urlprefix).'~','',$temp[0]);
      }
      $uri = implode('?',$temp);
      // now we remove opening slash
      $uri = preg_replace('~^/*~','',$uri);
      
      if (empty($uri)) return;
      
      // first let's look into the caches
      if (!empty($lConf->cache) && !empty($lConf->cache->usecache) && (string)$lConf->cache->usecache==1) {
      			// let's have a look into the cache, we'll look for all possibiltes (meaning trainling slash)
  			$tempuri = explode('?',$uri);
 			$tempuri[0] = Link_Func::prepareLinkForCache($tempuri[0],$lConf);
 			$xuri = $tempuri[0];
 			/*
  			$tempuri[0] = preg_match('~/$~',$tempuri[0])?substr($tempuri[0],0,strlen($tempuri[0])-1):$tempuri[0].'/'; // add or remove trailing slash
  			//$xuri = $uri;
  			$temp = '';
  			
  			if (!empty($lConf->cache->cacheparams) && $lConf->cache->cacheparams==1) {
  				$tempurix = implode('?',$tempuri);
  				$xuri = $uri;
  			} else {
  				$tempurix = $tempuri[0];
  			}
  			
        	$q = $db->query('SELECT * FROM '.$tp.'cache WHERE url=\''.$xuri.'\' OR url=\''.$tempurix.'\'');
			*/
 			
        	$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid, sys_language_uid, params','tx_naworkuri_uri', 'deleted=0 AND path="'.$xuri.'"' );
        	if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres) ){
        		$cachedparams = Array('id'=>$row['pid'],'L'=>$row['sys_language_uid']);
        		$cachedparams = array_merge($cachedparams,unserialize($row['params']));
        	} else {
        		Link_Func::pageNotFound($lConf);
        	}
        	
      		/*
  			$tp = Link_Func::getTablesPrefix($lConf);
  			$db = Link_DB::getInstance();
  			
  			// let's have a look into the cache, we'll look for all possibiltes (meaning trainling slash)
  			$tempuri = explode('?',$uri);
  			
  			//echo $tempuri[0];
			$tempuri[0] = Link_Func::prepareLinkForCache($tempuri[0],$lConf);
			//die($tempuri[0]);
        
			$xuri = $tempuri[0];
  			$tempuri[0] = preg_match('~/$~',$tempuri[0])?substr($tempuri[0],0,strlen($tempuri[0])-1):$tempuri[0].'/'; // add or remove trailing slash
  			//$xuri = $uri;
  			$temp = '';
  			
  			if (!empty($lConf->cache->cacheparams) && $lConf->cache->cacheparams==1) {
  				$tempurix = implode('?',$tempuri);
  				$xuri = $uri;
  			} else {
  				$tempurix = $tempuri[0];
  			}
  			
        	$q = $db->query('SELECT * FROM '.$tp.'cache WHERE url=\''.$xuri.'\' OR url=\''.$tempurix.'\'');
  			
  			$row = $db->fetch($q);
  			if ($row) {
  				if ($row['url']!=$xuri) { // we've got our $tempuri, not $url -> let's redirect
					Link_Func::redirect(Link_Func::prepareforOutput($row['url'].(empty($tempuri[1])?'':'?'.$tempuri[1]),$lConf));
  				} else {
  					$cachedparams = Link_Func::cache2params($row['params']);
  				}
  			} else {
  				$vf = '';
  				if (isset($lConf->cache->cool2params->oldlinksvalidfor))
  					$vf = ' AND DATEDIFF(NOW(),'.$tp.'oldlinks.tstamp)<'.(string)$lConf->cache->cool2params->oldlinksvalidfor;
					$q = $db->query('SELECT '.$tp.'cache.url AS oldlink FROM '.$tp.'oldlinks  LEFT JOIN '.$tp.'cache ON '.$tp.'oldlinks.link_id='.$tp.'cache.id WHERE ('.$tp.'oldlinks.url=\''.$xuri.'\' OR '.$tp.'oldlinks.url=\''.$tempurix.'\')'.$vf);
					$row = $db->fetch($q);
  				if ($row) {
  					Link_Func::redirect(Link_Func::prepareforOutput($row['oldlink'].(empty($tempuri[1])?'':'?'.$tempuri[1]),$lConf));
  				}			
  				elseif (empty($lConf->cache->cool2params->translateifnotfound) || $lConf->cache->cool2params->translateifnotfound!=1) {
  					Link_Func::pageNotFound($lConf);
  				}
  			}
			*/
  	  } // end cache
      
      //now we have a uri which will be parsed (without unwanted stuff)
      $finaluriparts = Array();
      if (!empty($uri) && empty($cachedparams)) {
      	// now we remove trailing slash
        $uri = preg_replace('~/*$~','',$uri);
        
        $coolpart = null;
        $dirtypart = null;
        $temp = explode('?',$uri);
        if (isset($temp[0])) $coolpart = $temp[0];
        if (isset($temp[1])) $dirtypart = $temp[1];
        
        if (!empty($coolpart)) {
          $pathsep = '';
          if (!empty($lConf->pathseparators) && !empty($lConf->pathseparators->separator)) {
            foreach ($lConf->pathseparators->separator as $sep) {
              $pathsep .= (string)$sep;
            }
          } else {
            $pathsep = '/';
          }
          $coolparts = preg_split('~['.$pathsep.']~',$coolpart);
          
          $coolparts = Link_Func::clearGETArray($coolparts);
          
          // at first we go through the predefined parts
          if (!empty($lConf->predefinedparts) && !empty($lConf->predefinedparts->part)) {
            foreach ($lConf->predefinedparts->part as $part) {
              foreach ($coolparts as $ck => $cp) {
                
                $par = false;
                if (!empty($part['regexp']) && $part['regexp']==1) {
                  if (preg_match('~^'.$part['key'].'$~', $cp)) {
                    $par = preg_replace('~^'.$part['key'].'$~',(string)$part->value,$cp);
                  }
                } else {
                  if ($part['key']==$cp) $par = (string)$part->value;
                }
                // we found a match in predef parts
                if ($par) {
                  
                  // first we find out if it's possible to find anything in the db
                  $cantranslate = true;
                  if (!empty($part->lookindb->translatefromif)) {
                    $cantranslate = Link_Func::constraint($par,$part->lookindb->translatefromif);
                  }
                  // we don't look into db for result
                  if (empty($part->lookindb) || !$cantranslate) {
                    $finaluriparts[(string)$part->parameter] = $par;
                  // we do
                  } else {
                  	$db = Link_DB::getInstance();
                  	$res = $db->query(preg_replace('~^'.$db->escape($part['key']).'$~',(string)$part->lookindb->from,$cp));
                  	$row = $db->fetch_row($res);
                    // we return value only if we found something
                    if (!empty($row[0])) $finaluriparts[(string)$part->parameter] = $row[0];
                  }

                  // we found a match, so we throw out this cool part, no matter if we got a result
                  unset($coolparts[$ck]);
                }

              }
            }
          } // end predefined parts
          
          // find stuff in a valuemaps
          if (!empty($lConf->valuemaps) && !empty($lConf->valuemaps->valuemap)) {
          	foreach ($lConf->valuemaps->valuemap as $vm) {
          		if (!empty($vm->value)) {
          			foreach ($vm->value as $val) {
          				if (in_array((string)$val['key'],$coolparts)) {
          					$finaluriparts[(string)$vm->parameter] = (string)$val;
          					$key = array_search((string)$val['key'],$coolparts);
          					unset($coolparts[$key]);
          				}
          			}
          		}
          	}
          } // end valuemaps

          $nottranslated = Array();
          // something's still left
          if (!empty($coolparts) && !empty($lConf->uriparts) && !empty($lConf->uriparts->part)) {
            
            $lastonthepath = null; // here will be kept last part of the pagepath
            
            // we'll match cool uri against array
            for ($i=0,$j=0; $i<count($coolparts) && $j<count($lConf->uriparts->part); $i++,$j++) {
              if (empty($coolparts[$i])) continue; // we don't have a item on this key, shouldn't happen
              
              // if a part is not required and next (static) part matches, we skip to it
              // i.e. category.example.com vs. example.com (in this case "example" would be 
              // considered for a category otherwise)
              if (!empty($lConf->uriparts->part[$j]['notrequired']) && $lConf->uriparts->part[$j]['notrequired']==1
               && !empty($lConf->uriparts->part[$j+1]) && !empty($lConf->uriparts->part[$j+1]['static'])
               && $lConf->uriparts->part[$j+1]['static']==1 && (string)$lConf->uriparts->part[$j+1]->value==$coolparts[$i]
              ) {
                ++$j; // we skip to next key in conf 
              }
              
              // if current part is static and matches, we move on
              // if is static, but doesn't match, we move on
              if (!empty($lConf->uriparts->part[$j]['static']) && $lConf->uriparts->part[$j]['static']==1) {
                if ((string)$lConf->uriparts->part[$j]->value==$coolparts[$i]) {
                  continue;
                } else {
                  ++$j;
                }
              }
              
              // this should be a dynamic param
              if (!empty($lConf->uriparts->part[$j]->parameter)) {
                // we preset the variable, if may change after
                $finaluriparts[(string)$lConf->uriparts->part[$j]->parameter] = $coolparts[$i];
                
                // first we find out if it's possible to find anything in the db
                $cantranslate = true;
                if (!empty($lConf->uriparts->part[$j]->lookindb->translatefromif)) {
                  $cantranslate = Link_Func::constraint($coolparts[$i],$lConf->uriparts->part[$j]->lookindb->translatefromif);
                }
                
                if (!empty($lConf->uriparts->part[$j]->lookindb) && $cantranslate) {
                  $proceed = true;
                  $sql = $lConf->uriparts->part[$j]->lookindb->from;
                  $sql = str_replace('$1',$coolparts[$i],$sql);
                  /* undocumented */
                  if (preg_match_all('~\$[^ ]+ ~',$sql.' ',$vars)) {
                    if (!empty($vars[0])) {
                      foreach ($vars[0] as $var) {
                      	if (isset($nottranslated[substr(trim($var),1)])) $proceed = false; // this var wasn't found in db b4, no need to try to translate
                        else {
                        	if (empty($finaluriparts[substr(trim($var),1)])) continue; // subst not found - query res would be empty
                          else $sql = str_replace(trim($var),Link_DB::escape($finaluriparts[substr(trim($var),1)]),$sql);
                        }
                      }
                    }
                  }
                  /* undocumented */
                  if ($proceed) {
                  	$db = Link_DB::getInstance();
                    $res = $db->query($sql);
                    if (!$db->error() && $db->num_rows($res)>0) { // no match found - not translating
                      $row = $db->fetch_row($res);
                      $finaluriparts[(string)$lConf->uriparts->part[$j]->parameter] = $row[0];
                    } else {
                      $nottranslated[(string)$lConf->uriparts->part[$j]->parameter] = true; // this param wasn't translated
                    }
                  } else {
                      $nottranslated[(string)$lConf->uriparts->part[$j]->parameter] = true; // this param wasn't translated
                  }
                } elseif (!empty($lConf->pagepath) && !empty($lConf->uriparts->part[$j]['pagepath']) && $lConf->uriparts->part[$j]['pagepath']=='1') {
                	// this param is part of the pagepath, let's try that
                	if ($lastonthepath==null || !empty($finaluriparts[$lastonthepath])) {
	                	$db = Link_DB::getInstance();
	                	$sql = 'SELECT '.(string)$lConf->pagepath->id.' FROM '.(string)$lConf->pagepath->table;
						$sql .= ' WHERE '.(string)$lConf->pagepath->alias.'=\''.$coolparts[$i].'\'';
						if ($lastonthepath==null) $sql .= ' AND '.(string)$lConf->pagepath->start->param.'='.(string)$lConf->pagepath->start->value;
						else $sql .= ' AND '.(string)$lConf->pagepath->connection.'='.$finaluriparts[$lastonthepath];
						$res = $db->query($sql);
						if (!$db->error() && $db->num_rows($res)>0) { // no match found - not translating
	                      $row = $db->fetch_row($res);
	                      $finaluriparts[(string)$lConf->uriparts->part[$j]->parameter] = $row[0];
	                    } else {
	                      $nottranslated[(string)$lConf->uriparts->part[$j]->parameter] = true; // this param wasn't translated
	                    }
	                    $lastonthepath = (string)$lConf->uriparts->part[$j]->parameter;
                	} else {
						          $nottranslated[(string)$lConf->uriparts->part[$j]->parameter] = true; // this param wasn't translated
                	}
                }
              }
            }
          } // end uriparts

        }
        // Cool part done, let's add dirty part
        if (!empty($dirtypart)) {
          $finaluriparts = array_merge($finaluriparts,Link_Func::convertQuerystringToArray($dirtypart));
        }
        // Now we'll compose our return pagepath to one variable (i.e. id could be one of cid,sid,lid,tid)
        if (!empty($lConf->pagepath->saveto)) {
          // first we'll set a default value (if not set already)
          if (empty($finaluriparts[(string)$lConf->pagepath->saveto])) 
            $finaluriparts[(string)$lConf->pagepath->saveto] = (string)$lConf->pagepath->default;
          
          // let's modify param constraints array a bit:
          $paramconstraints = Array();
          if (!empty($lConf->uriparts->paramconstraints->paramconstraint)) {
            foreach ($lConf->uriparts->paramconstraints->paramconstraint as $pc) {
              $paramconstraints[(string)$pc['param']] = $pc;
            }
          }
          
          $rqok = true;
          // first we'll check if a required param is OK
          if (!empty($lConf->pagepath->required) && !empty($lConf->pagepath->required->param)) {
            foreach ($lConf->pagepath->required->param as $p) {
              $p = (string)$p;
              if (empty($finaluriparts[$p]))
                $rqok = false;
              elseif (!empty($paramconstraints[$p]) && !Link_Func::constraint($finaluriparts[$p],$paramconstraints[$p]))
                $rqok = false;
              elseif (!empty($lConf->pagepath->allparamconstraints) 
                  && !Link_Func::constraint($finaluriparts[$p],$lConf->pagepath->allparamconstraints))
                $rqok = false;  
            }
          }
          
          // now we'll go through all params, check if they're ok and eventually set the final value
          if ($rqok) { // only if required is OK
          	$px = Array();
          	foreach ($lConf->uriparts->part as $p) {
          		$px[] = $p;
          	}
            foreach (array_reverse($px) as $p) {
              if (empty($p['pagepath']) || $p['pagepath']!='1') continue;
              $p = (string)$p->parameter;
              if (!empty($finaluriparts[$p])) {
                if (!empty($paramconstraints[$p]) && !Link_Func::constraint($finaluriparts[$p],$paramconstraints[$p]))
                  continue;
                  
                if (!empty($lConf->pagepath->allparamconstraints) 
                  && !Link_Func::constraint($finaluriparts[$p],$lConf->pagepath->allparamconstraints))
                  continue;
                
                // all tests passed, this is what we're looking for
                $finaluriparts[(string)$lConf->pagepath->saveto] = $finaluriparts[$p];
                break;
              }
            }
          }
        }
      } // end !empty($uri) && empty($cachedparams)
	    
      $temp =  empty($cachedparams)?$finaluriparts:$cachedparams;
      $temp = array_map('urldecode',$temp);
      
      self::$uri = $temp;
      $res = array_merge($_GET,$temp);
      if (!empty($lConf->savetranslationto)) {
      	$x = (string)$lConf->savetranslationto;
      	switch (trim($x)) {
      		case '_REQUEST': $_REQUEST = array_merge($_REQUEST,$res); break;
      		case '_GET': $_GET = array_merge($_GET,$res); break;
      		case '_POST': $_POST = array_merge($_POST,$res); break;
      		default: $GLOBALS[$x] = $res;
      	}
      } else
      	$_GET = $res;
      	
      $this->uri = $res;
      
      return $res;
    }
 }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////
  
  public function params2coolForRedirect(array $params) {
    return $this->params2cool($params,'',false);
  }
  
	public function params2cool(array $params, $file = '', $entityampersand = true, $dontconvert = false, $forceUpdate = false) {
		$lConf = &self::$conf;
	  	if (!empty($lConf->cooluris) && $lConf->cooluris==1 && !$dontconvert) {

  		// if cache is allowed, we'll look for an uri

  		if (!empty($lConf->cache) && !empty($lConf->cache->usecache) && $lConf->cache->usecache==1) {
  			// $tp = Link_Func::getTablesPrefix($lConf);
  			// $db = Link_DB::getInstance();
  			// cache is valid for only a sort period of time, after that time we need to do a recheck
  			$checkfornew = !empty($lConf->cache->params2cool)&&!empty($lConf->cache->params2cool->checkforchangeevery)?(string)$lConf->cache->params2cool->checkforchangeevery:0;
  			$originalparams = $params;
  			
  			// we don't cache params
  			if (empty($lConf->cache->cacheparams) || $lConf->cache->cacheparams!=1) {
  				if (!self::$coolParamsKeys) self::$coolParamsKeys = Link_Func::getCoolParams($lConf);
  				$originalparams = Link_Func::array_intersect_key($originalparams,self::$coolParamsKeys);
  			}
  			
  			$parameters = $originalparams;
  			$save_uid  = $parameters['id'];
  			$save_lang = ($parameters['L'])?$parameters['L']:0;
  			unset($parameters['id']);
  			unset($parameters['L']);
  			
  			$save_params = serialize($parameters);
  			$save_hash_params = md5($save_params);
  			
  			$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, path', 'tx_naworkuri_uri', 'deleted=0 AND pid='.$save_uid.' AND sys_language_uid='.$save_lang.' AND hash_params LIKE "'.$save_hash_params.'"' );
  			if ( $row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbres) ){
				return Link_Func::prepareforOutput($row['path'],$lConf);
			}
			
			/*
			$q = $db->query('SELECT *, DATEDIFF(NOW(),tstamp) AS daydiff FROM '.$tp.'cache WHERE params=\''.Link_Func::prepareParamsForCache($originalparams,$tp).'\'');
			$row = $db->fetch($q);
  			if ($row) {

    			if ($row['daydiff']==NULL) $row['daydiff'] = 2147483647; // daydiff isn't set, we force new check

  				if (($row['daydiff']>=$checkfornew && $row['sticky']==0) || $forceUpdate) {
  					$updatecacheid = $row['id'];
            $cacheduri = $row['url'];
  				} else {
  					$qs = '';
  					if (empty($lConf->cache->cacheparams) || $lConf->cache->cacheparams!=1) {
	  					$qsp = Link_Func::array_diff_key($params,$originalparams);
	  					if (!empty($qsp)) {
	  						$qs = '?'.http_build_query($qsp);	
	  					}
  					}
  					return Link_Func::prepareforOutput($row['url'],$lConf).$qs; // uri found in cache
  				}
  			} */
  		}  // end cache
		
 		
  		$originalParams = $params;  					
							
			// set language parameter
		$originalParams['L'] = (int)$originalParams['L'];
  		
			// change alias to id
		if (!is_numeric($originalParams['id'])) {
			$originalParams['id'] = $GLOBALS['TSFE']->sys_page->getPageIdFromAlias($originalParams['id']);
		}
  		
  		
  		$predefparts = Array();
  		if (!empty($lConf->predefinedparts) && !empty($lConf->predefinedparts->part)) {
			
			// first let's translate predefenied params
  			foreach ($lConf->predefinedparts->part as $ppart) {
  				if (isset($params[(string)$ppart->parameter])) {
  				  $uf = Link_Func::user_func($ppart,$params[(string)$ppart->parameter]);
  				  
  				  if ($uf!==FALSE) {
              		$predefparts[(string)$ppart->parameter] = $uf;
            	  } elseif (!empty($ppart['regexp']) && $ppart['regexp']==1) {
  						$predefparts[(string)$ppart->parameter] = preg_replace('~\([^)]+\)~',empty($ppart->lookindb)?$params[(string)$ppart->parameter]:Link_Func::lookindb($ppart->lookindb->to,$params[(string)$ppart->parameter],$ppart->lookindb),$ppart['key']);
  				  } elseif ($ppart->value==$params[(string)$ppart->parameter]) {
  						$predefparts[(string)$ppart->parameter] = empty($ppart->lookindb)?(string)$ppart['key']:Link_Func::lookindb($ppart->lookindb->to,$params[(string)$ppart->parameter],$ppart->lookindb);
  				  }
                  
                  unset($params[(string)$ppart->parameter]);
  				}
  			}
  		} // end predefinedparts
  		
  		//valuemaps (iterate thru values in maps)
  		if (!empty($lConf->valuemaps) && !empty($lConf->valuemaps->valuemap)) {
  			foreach ($lConf->valuemaps->valuemap as $vm) {
  				if (isset($params[(string)$vm->parameter])) {
  					foreach ($vm->value as $val) {
  						if ((string)$val==$params[(string)$vm->parameter]) {
  							$predefparts[(string)$vm->parameter] = (string)$val['key']; // let's just add it to the predeparts array
  							unset($params[(string)$vm->parameter]);
  						}
  					}
  				}
  			}
  		}
  		
  			// now the pagepath
  			// @TODO rewrite to TYPO3_DB
  		$translatedpagepath = Array();
  		$pagepath = Array();
  		
  		if (!empty($lConf->pagepath) && !empty($lConf->pagepath->saveto) && !empty($params[(string)$lConf->pagepath->saveto])) {
  			$uf = Link_Func::user_func($lConf->pagepath,$originalParams);
  			if ($uf===FALSE) {
  				$curid = $params[(string)$lConf->pagepath->saveto];
  				$result = true;
  				$lastpid = null;
  				$db = Link_DB::getInstance();
  				$limit = 10;
  				while ($limit>0 && (empty($lConf->pagepath->idconstraint)?$result:($result && Link_Func::constraint($curid,$lConf->pagepath->idconstraint)))) {
  					--$limit;
  					if (empty($lConf->pagepath->alias)) $sel = (string)$lConf->pagepath->title;
  					else $sel = (string)$lConf->pagepath->alias;
            
  					$sql = 'SELECT '.(string)$lConf->pagepath->connection.','.$sel.' 
  							FROM '.(string)$lConf->pagepath->table.' WHERE '.(string)$lConf->pagepath->id.'='.($lastpid==null?$params[(string)$lConf->pagepath->saveto]:$lastpid);
  					if (!empty($lConf->pagepath->additionalWhere)) {
  						$sql .= ' '.(string)$lConf->pagepath->additionalWhere;
  					}
  
  					$res = $db->query($sql);
  					if ($db->error() || !$res) {
  						$result = false; continue;
  					}
  					$row = $db->fetch_row($res);
  					if (!$row) {
  						$result = false; continue;
  					}
  					
  					if (empty($lConf->pagepath->alias)) { // we need to convert title to a uri, if alias is not set
  						$val = $row[1];
  						$k = 2;
  						while (empty($val) && isset($row[$k])) { // there may be more columns we want to have a look at
  							$val = $row[$k];
  							++$k;
  						}
  						if (!empty($conf->sanitize) && $conf->sanitize==1) {
  							$pagepath[] = Link_Func::sanitize_title_with_dashes($val);
  						} else {
  							$pagepath[] = Link_Func::URLize($val);
  						}

  					} else
  						$pagepath[] = $row[1];
  						$lastpid = $row[0];
  					}
  				} else {
  					$pagepath = $uf;
  				}

  				unset($params[(string)$lConf->pagepath->saveto]);
  				$pagepath = array_reverse($pagepath);
  			} // end pagepath
		
	  		if (!empty($lConf->uriparts) && !empty($lConf->uriparts->part)) { // a path found
	  			$counter = 0;
	  			foreach ($lConf->uriparts->part as $pp) {
	  				$uf = FALSE;
	  				if (!empty($params[(string)$pp->parameter])) {
	  					$uf = Link_Func::user_func($pp,$params[(string)$pp->parameter]);
	  					if ($uf!==FALSE) {
	  						$translatedpagepath[(string)$pp->parameter] = $uf;
	  					} else
	  						$translatedpagepath[(string)$pp->parameter] = (empty($pp->lookindb)?$params[(string)$pp->parameter]:Link_Func::lookindb($pp->lookindb->to,$params[(string)$pp->parameter],$pp->lookindb));
	  				} elseif (!empty($pp['pagepath']) && $pp['pagepath']==1 && !empty($pagepath[$counter])) {
	  					//if (!empty($pagepath[$counter])) {
	  					$translatedpagepath[(string)$pp->parameter] = $pagepath[$counter];
	  					unset($pagepath[$counter]);
	  					++$counter;
	  					//} elseif (!empty($params[(string)$pp->parameter])) {
	  					//$translatedpagepath[(string)$pp->parameter] = (empty($pp->lookindb)?$params[(string)$pp->parameter]:Link_Func::lookindb($pp->lookindb->to,$params[(string)$pp->parameter]));
	  					//}
	  				}
				  
	  			}
	  		}
  		
		  		
	  		$path = '';
	  		$paramsinorder = Array();
	  		if (!empty($lConf->paramorder) && !empty($lConf->paramorder->param)) {
	  			$statics = Array();
	  			if (!empty($lConf->uriparts) && !empty($lConf->uriparts->part)) {
	  				foreach ($lConf->uriparts->part as $part) {
	  					if (!empty($part['static']) && $part['static']==1) {
	  						$statics[(string)$part->value] = (string)$part->value;
	  					}
	  				}
	  			}
	  		} // end paramorder
    		
    		// we need list of separators
	  		$seps = Array();
	  		if (!empty($lConf->predefinedparts) && !empty($lConf->predefinedparts->part)) {
	  			foreach ($lConf->predefinedparts->part as $part) {
	  				$seps[(string)$part->parameter] = Link_Func::getSeparator($part);
    			}
    		}
    		if (!empty($lConf->valuemaps) && !empty($lConf->valuemaps->valuemap)) {
  				foreach ($lConf->valuemaps->valuemap as $part) {
    				$seps[(string)$part->parameter] = Link_Func::getSeparator($part);
    			}
    		}
    		if (!empty($lConf->uriparts) && !empty($lConf->uriparts->part)) {
    			foreach ($lConf->uriparts->part as $part) {
    				if (!empty($part['static']) && $part['static']==1) {
    					$seps[(string)$part->value] = Link_Func::getSeparator($part);
    				} else  {
    					$seps[(string)$part->parameter] = Link_Func::getSeparator($part);
    				}
    			}
    		}
    		if (!empty($lConf->paramorder) && !empty($lConf->paramorder->param)) {
    			foreach ($lConf->paramorder->param as $par) {
    				$paramsinorder[(string)$par] = true;
    				if (!empty($predefparts[(string)$par])) {
    					$path .= $predefparts[(string)$par].$seps[(string)$par];
					} elseif (!empty($translatedpagepath[(string)$par])) {
						$path .= $translatedpagepath[(string)$par].$seps[(string)$par];
					} elseif (!empty($statics[(string)$par])) {
						$path .= $statics[(string)$par].$seps[(string)$par];
					}
				}
	  	  	}
    	
  		
	  	  	$vm = '';
	  	  	if (!empty($lConf->valuemaps) && !empty($lConf->valuemaps->valuemap)) {
	  	  		foreach ($lConf->valuemaps->valuemap as $part) {
	  	  			if (!empty($predefparts[(string)$part->parameter]) && empty($paramsinorder[(string)$part->parameter])) {
	  					$vm .= $predefparts[(string)$part->parameter].Link_Func::getSeparator($part);
	  					unset($predefparts[(string)$part->parameter]);
	  					unset($params[(string)$part->parameter]);
	  				}
	  			}
	  		}

	  		$pp = '';
	  		if (!empty($lConf->predefinedparts) && !empty($lConf->predefinedparts->part)) {
	  			foreach ($lConf->predefinedparts->part as $part) {
	  				if (!empty($predefparts[(string)$part->parameter]) && empty($paramsinorder[(string)$part->parameter])) {
	  					$pp .= $predefparts[(string)$part->parameter].Link_Func::getSeparator($part);
	  					unset($predefparts[(string)$part->parameter]);
	  					unset($params[(string)$part->parameter]);
	  				}
	  			}
	  		}
	  		$tp = '';
	  		if (!empty($lConf->uriparts) && !empty($lConf->uriparts->part)) {
	  			foreach ($lConf->uriparts->part as $part) {
	  				if (!empty($part['static']) && $part['static']==1 && empty($paramsinorder[(string)$part->value])) {
	  					$tp .= (string)$part->value.Link_Func::getSeparator($part);
	  				}
	  				elseif (!empty($translatedpagepath[(string)$part->parameter]) && empty($paramsinorder[(string)$part->parameter])) {
	  					$tp .= $translatedpagepath[(string)$part->parameter].Link_Func::getSeparator($part);
	  					unset($params[(string)$part->parameter]);
	  				}
	  			}
	  		}
  		
	  		// if pagepath is not empty, that means not all pagepaths were added to $translatepagepath. We'll just add it
	  		$pagep = '';
	  		if (!empty($lConf->pagepath) && !empty($lConf->pagepath->saveto) && !empty($pagepath)) {
	  			$pagep = implode(Link_Func::getSeparator(),$pagepath).Link_Func::getSeparator();
	  		}
  		
	  		if (!empty($lConf->partorder) && !empty($lConf->partorder->part)) {
	  			$partorder = Array();
	  			foreach ($lConf->partorder->part as $p) {
	  				$partorder[] = (string)$p;
	  			}  
	  		} else {
	  			$partorder = Array('pagepath','uriparts','valuemaps','predefinedparts');
	  		}

		
	  		foreach ($partorder as $p) {
	  			switch ($p) {
	  				case 'uriparts': $path .= $tp; break;
	  				case 'valuemaps': $path .= $vm; break;
	  				case 'predefinedparts': $path .= $pp; break;
	  				case 'pagepath': $path .= $pagep; break;
	  			}
	  		}


 
	  		if (!empty($params)) {
	  			//$params = '?'.http_build_query($params);
	  			foreach ($params as $k=>$v) $params[$k] = $k.'='.$v;
	  			$params = '?'.implode('&',$params);
	  			//$params = str_replace('&amp;','&',$params);
	  			if ($entityampersand) $params = str_replace('&','&amp;',$params);
	  		}
  		
	  		// if cache is allowed, we'll save path to the cache (excluding possible prefix and suffix)
	  		if (!empty($lConf->cache) && !empty($lConf->cache->usecache) && $lConf->cache->usecache==1) {
	  			$tp = Link_Func::getTablesPrefix($lConf);
	  			$db = Link_DB::getInstance();
  			
	  			$p = '';
	  			if (!empty($lConf->cache->cacheparams) && $lConf->cache->cacheparams==1 && !empty($params)) {
	  				$p = $params;
	  			}
  			
  					// save uri to cache
  					// @TODO the path must be unique here 
	  			$path = Link_Func::prepareLinkForCache($path,$lConf);
	  			if (!empty($originalParams)) {
	  					
	  					// create params
	  				$params = $originalParams;
	  				
	  				$save_uid = $params['id'];
	  				$save_lang = ($params['L'])?$params['L']:0;
	  				unset($params['id']);
	  				unset($params['L']);
	  				
	  				$save_path   = $path;
	  				$save_params = serialize($params);
	  				$save_hash_path   = md5($path);
	  				$save_hash_params = md5($save_params);
		  				
	  					// save if record is not already present
					$dbres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tx_naworkuri_uri', 'deleted=0 AND hash_path LIKE "'.$save_hash_path.'" AND  hash_params LIKE "'.$save_hash_params.'"' );
					if ( $GLOBALS['TYPO3_DB']->sql_num_rows($dbres) == 0){
						$GLOBALS['TYPO3_DB']->exec_INSERTquery(
		  					'tx_naworkuri_uri',
		  					array(
		  						'pid' => $save_uid,
		  						'sys_language_uid' => $save_lang,
		  						'crdate' => mktime(),
		  						'path'   => $save_path,
		  						'params' => $save_params,
		  						'hash_path'    => $save_hash_path,
		  						'hash_params'  => $save_hash_params,
		  					)
		  				);
					}	
	  			
	  				if (!empty($updatecacheid)) {
	  					
	  					// first we will update the timestamp (so we will now, when the last uri check was)
	    				$db->query('UPDATE '.$tp.'cache SET tstamp=NOW() WHERE id='.$updatecacheid);
	           			if ($cacheduri!=$path.$p) {
	           				// uri is changed, we need to move the old one to the old links
	    					$db->query('INSERT INTO '.$tp.'oldlinks(link_id,url) VALUES('.$updatecacheid.',\''.$cacheduri.'\')');
	    					$db->query('UPDATE '.$tp.'cache SET url=\''.$path.$p.'\' WHERE id='.$updatecacheid);
	    					
	    					// if the path has changed back, no need to store it in the oldlinks
	    					// prevets from overflooding the DB when tampering with configuration
	    					$db->query('DELETE FROM '.$tp.'oldlinks WHERE url=\''.$path.$p.'\'');
	    					
	    				}
	    			} else {	
	    				$res = $db->query('SELECT * FROM '.$tp.'cache WHERE url=\''.$path.$p.'\'');
	    				
	    				if ($db->num_rows($res)==0 && !preg_match('/.*\/(\/)+.*/', $path)) {    				
	    					$db->query('INSERT INTO '.$tp.'cache(url,params,crdatetime) VALUES(\''.$path.$p.'\',\''.Link_Func::prepareParamsForCache($originalParams).'\',NOW())');
	    				} elseif ( $res && $path != '' && $originalParams['id'] != 0 && $row = $db->fetch($res) ) {
							$tmpParams = unserialize($row['params']);
							
							// @TODO what is this?
							//$createAlternativePath = false;
							//$arrParam = (array)$lConf->appendixcomparison;
							//foreach ($arrParam['param'] as $param) {
							//	if ($tmpParams[$param] != $originalParams[$param]) {
							//		$createAlternativePath = true;
							//	}		
							//}
							
							
							if ($createAlternativePath) {
								$path = $this->createAlternativePath($path, $originalParams, $tp, $p);
							}
	    				}
					} 
	  			} 
	  			
	  		} 
  		
	  		//if (!empty($params)) $path .= $params;
  		
	  		return Link_Func::prepareforOutput($path,$lConf).(empty($params)?'':$params);  		
	  	} else {
	  		return (empty($file)?$_SERVER['PHP_SELF']:$file).(empty($params)?'':'?'.http_build_query($params,'',$entityampersand?'&amp;':'&'));
	  	}  	
  }
   
    
  public function createAlternativePath($path, $params, $tp, $p, $postfix=0) {
  	$alternativePath = $path. $postfix. '/';

  	$db = Link_DB::getInstance();  	
  	$res = $db->query('SELECT * FROM '.$tp.'cache WHERE url=\''.$alternativePath.$p.'\'');	
							
 	if ($db->num_rows($res)==0) {
    	$db->query('INSERT INTO '.$tp.'cache(url,params,crdatetime) VALUES(\''.$alternativePath.$p.'\',\''.Link_Func::prepareParamsForCache($params).'\',NOW())');
    	return $alternativePath;
    } else {
    	$row = $db->fetch($res);
    	$tmpParams = unserialize($row['params']);    	
    	
		$lConf = &self::$conf;
    	$createAlternativePath = false;		
		$arrParam = (array)$lConf->appendixcomparison;
		foreach ($arrParam['param'] as $param) {
			if ($tmpParams[$param] != $params[$param]) {
				$createAlternativePath = true;
			}		
		}
		
		
		if ($createAlternativePath) {
    		$postfix += 1;
    		return $this->createAlternativePath($path, $params, $tp, $p, $postfix);
		} else {
			return $alternativePath;
		}
    }
  }
  
    
  public function GET($var) {
    if (!isset($this->uri[$var])) return false;
    return stripslashes($this->uri[$var]);
  }
  
  public function sGET($var) {
    return addslashes(self::GET($var));
  }
  
  public function GETall() {
    return $this->uri;
  }
  
  public static function replaceAllLinks($string) {
    
    function replace($link) {
      if (!preg_match('~^http://~',$link[2])) {
        $parts = explode('?',$link[2]);
        if (empty($parts[1])) return $link[0];
        $lt = Link_Translate::getInstance();
        $link[2] = str_replace('&amp;','&',$link[2]);
        $link[2] = $lt->params2cool(Link_Func::convertQueryStringToArray($parts[1]),$parts[0]);
        unset($link[0]);
        return implode('',$link);
      } else {
        return $link[0];
      }
    }
    
    $string = preg_replace_callback('~(href=[\'"])([^\'"]+)([\'"])~', 'replace', $string);
    $string = preg_replace_callback('~(method=[\'"])([^\'"]+)([\'"])~', 'replace', $string);
    
    return $string;
  }
  
}

?>
