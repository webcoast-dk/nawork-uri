<?php

class Link_Func {
  
  /*
  * creates array from a query string
  * @input: x=y&z=a&b=c
  * @return: [x=>y],[z=>a],[b=>c]    
  */
  
  public static function convertQuerystringToArray($string,$sep='&',$eq='=',$altSep='&amp;') {
  	$string = str_replace($altSep,$sep,$string);
    $parts = explode($sep,$string);
  	$ra = Array();
  	foreach ($parts as $k=>$v) {
  		$temp = explode($eq,$v);
  		$ra[$temp[0]]=isset($temp[1])?$temp[1]:'';
  	}
  	return $ra;
  }
  
  /*
  * shifts keys in assoc array by +1, based on key order in another array
  */  
  
  public static function arrayShiftKeys($ar,$ky) {
	
  	$ar = array_reverse($ar);
  	$kky = array_flip($ky);
  	$r = Array();
  	
  	foreach ($ar as $k=>$v) {
  		$r[$ky[$kky[$k]+1]] = $v;
  	}
  	return $r;
  }
  
  public static function addregexpslashes($string) {
    return strtr(
      $string, Array(
      '.'=>'\.','/'=>'\/','\\'=>'\\\\','+'=>'\+'
      )
    );
  }
  
  public static function compare($a,$op,$b) {
    switch ($op) {
      case 'lte': return $a<=$b;
      case 'gte': return $a>=$b;
      case 'lt': return $a<$b;
      case 'gt': return $a>$b;
      case 'eq': return $a==$b;
      case 'neq': return $a!=$b;
    }
    return true;
  }
  
  public static function validateType($value,$type) {
    switch ($type) {
      case 'int': $x = (int)$value; break;
      case 'float': $x = (float)$value; break;
      case 'string': $x = (string)$value; break;
      default: return true;
    }
    return $value == $x.''; // to prevent typecast, we turn second value to string
  }
  
  public static function constraint($value,$cons) {
    $ch = $cons->children();
    
    foreach ($ch as $type => $c) {

      $c = (string)$c;
      switch ($type) {    
        case 'match': if (!preg_match('~'.$c.'~',$value)) return false; break;
        case 'compare': if (isset($ch->value) && !self::compare($value,$c,$ch->value)) return false; break; // we don't use empty, because value can be 0
        case 'type': if (!self::validateType($value,$c)) return false; break;
      }
    }
    return true;
  }
  
  public static function clearGETArray($a) {
    $a = array_map('stripslashes',$a);
    $a = array_map('urldecode',$a);
    return $a;
  }
  
  public static function lookindb($sql,$param='',$conf) {
    $sql = str_replace('$1',$param,(string)$sql);
  	$db = Link_DB::getInstance();
  	$res = $db->query($sql);
  	if (mysql_error() || !$res) return $param;
  	$row = $db->fetch_row($res);
  	if (!$row) return $param;
  	$val = $row[0];
  	$k = 1;
  	while (empty($val) && isset($row[$k])) {
      $val = $row[$k];
      ++$k;
    }
  	if (!empty($conf->urlize) && $conf->urlize==1) {
      return self::URLize($val);
    }
    if (!empty($conf->sanitize) && $conf->sanitize==1) {
      return self::sanitize_title_with_dashes($val);
    }
    if (!empty($conf->t3conv) && $conf->t3conv==1) {
      return self::specCharsToASCII($val);
    }
  	return $val;
  }
  
  public static function getSeparator($xml = Array()) {
  	if (empty($xml['after'])) return '/';
  	else return $xml['after'];
  }
  
  public static function prepareParamsForCache($params,$tp = 'link_') {
    ksort($params);
  	return Link_DB::escape(serialize($params),$tp);
  }
  
  public static function cache2params($cache) {
  	return unserialize($cache);
  }
  
  public static function getTablesPrefix($conf) {
  	if (empty($conf->cache) || empty($conf->cache->tablesprefix)) return 'link_';
  	return trim((string)$conf->cache->tablesprefix);
  }
  
  public static function removeSlash($path) {
  	$parts = explode('?',$path);
  	$parts[0] = preg_replace('~/$~','',$parts[0]);
  	return implode('?',$parts);
  }
  
  public static function prepareforOutput($path,$lConf) {
    if (empty($path)) return $path;
    if (!empty($lConf->removetrailingslash) && $lConf->removetrailingslash==1) {
      $path = self::removeSlash($path);
  	}
    if (!empty($lConf->urlprefix)) {
  		$path = (string)$lConf->urlprefix.$path;
  	}
  	if (!empty($lConf->urlsuffix)) {
  		$path .= (string)$lConf->urlsuffix;
  	}
  	return $path;
  }
  
  public static function redirect($path) {
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
  }
  
  public static function pageNotFound($lConf) {
  	if (!empty($lConf->cache->pagenotfound)) {
  		switch ((string)$lConf->cache->pagenotfound->behavior['type']) {
	  		case 'message': $res = (string)$lConf->cache->pagenotfound->behavior; break;
	  		case 'page':	$res = implode('', file((string)$lConf->cache->pagenotfound->behavior)); break;
	  		case 'redirect': header((string)$lConf->cache->pagenotfound->status); self::redirect((string)$lConf->cache->pagenotfound->behavior); break;
	  		default: $res = '';
	  	}
	  	
	  	header('Content-Type: text/html; charset=utf-8');
	  	header((string)$lConf->cache->pagenotfound->status);
	  	echo $res;
	  	exit;
  	}
  }
  
  public static function getCoolParams($lConf) {
  	$pars = $lConf->xpath('//parameter');
  	$ret = Array();
  	foreach ($pars as $p) {
  		$ret[(string)$p] = true;
  	}
  	if (!empty($lConf->pagepath->id)) $ret[(string)$lConf->pagepath->id] = true;
  	if (!empty($lConf->pagepath->saveto)) $ret[(string)$lConf->pagepath->saveto] = true;
  	return $ret;
  }
  
  public static function array_intersect_key($arr1, $arr2) {
    $res = array();
    foreach($arr1 as $key=>$value) {
	   if(array_key_exists($key, $arr2)) $res[$key] = $arr1[$key];
    }
    return $res;
  }
  
  public static function array_diff_key($arr1,$arr2) {
  	$res = array();
  	foreach ($arr1 as $key=>$value) {
  		if (!array_key_exists($key,$arr2)) $res[$key] = $arr1[$key];
  	}
  	return $res;
  }
  
  public static function URLize($text) {

	      $text = strtr($text, self::$sonderzeichen);
        $text = self::utf2ascii($text);
        $text = str_replace('\'', '', $text);
        $text = preg_replace('/\W+/', '-', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        return $text;
  }
  
  public static function utf2ascii($s)
  {
      if (function_exists('iconv')) return iconv('UTF-8', 'ASCII//TRANSLIT', $s);
      static $tbl = array("\xc3\xa1"=>"a","\xc3\xa4"=>"a","\xc4\x8d"=>"c","\xc4\x8f"=>"d","\xc3\xa9"=>"e","\xc4\x9b"=>"e","\xc3\xad"=>"i","\xc4\xbe"=>"l","\xc4\xba"=>"l","\xc5\x88"=>"n","\xc3\xb3"=>"o","\xc3\xb6"=>"o","\xc5\x91"=>"o","\xc3\xb4"=>"o","\xc5\x99"=>"r","\xc5\x95"=>"r","\xc5\xa1"=>"s","\xc5\xa5"=>"t","\xc3\xba"=>"u","\xc5\xaf"=>"u","\xc3\xbc"=>"u","\xc5\xb1"=>"u","\xc3\xbd"=>"y","\xc5\xbe"=>"z","\xc3\x81"=>"A","\xc3\x84"=>"A","\xc4\x8c"=>"C","\xc4\x8e"=>"D","\xc3\x89"=>"E","\xc4\x9a"=>"E","\xc3\x8d"=>"I","\xc4\xbd"=>"L","\xc4\xb9"=>"L","\xc5\x87"=>"N","\xc3\x93"=>"O","\xc3\x96"=>"O","\xc5\x90"=>"O","\xc3\x94"=>"O","\xc5\x98"=>"R","\xc5\x94"=>"R","\xc5\xa0"=>"S","\xc5\xa4"=>"T","\xc3\x9a"=>"U","\xc5\xae"=>"U","\xc3\x9c"=>"U","\xc5\xb0"=>"U","\xc3\x9d"=>"Y","\xc5\xbd"=>"Z");
      return strtr($s, $tbl);
  }
  
  public static function user_func($lConf,$value) {
    if (!empty($lConf->userfunc)) {
      $uf = explode('->',(string)$lConf->userfunc);
      if (!isset($uf[1])) $uf = $uf[0];
      $res = call_user_func($uf,$lConf,$value);
      return $res;
    }
    return FALSE;
  }
  
  /* sanitize - stuff from WordPress */
public static function sanitize_title_with_dashes($title) {
	    $title = strip_tags($title);
	    // Preserve escaped octets.
	    $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
	    // Remove percent signs that are not part of an octet.
	    $title = str_replace('%', '', $title);
	    // Restore octets.
	    $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);
	
	    $title = self::remove_accents($title);
	    if (self::seems_utf8($title)) {
	        if (function_exists('mb_strtolower')) {
	            $title = mb_strtolower($title, 'UTF-8');
	        }
	        $title = self::utf8_uri_encode($title, 200);
	    }
	
	    $title = strtolower($title);
	    $title = preg_replace('/&.+?;/', '', $title); // kill entities
	    $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
	    $title = preg_replace('/\s+/', '-', $title);
	    $title = preg_replace('|-+|', '-', $title);
	    $title = trim($title, '-');
	
	    return $title;
	}

public static function utf8_uri_encode( $utf8_string, $length = 0 ) {
	    $unicode = '';
	    $values = array();
	    $num_octets = 1;
	
	    for ($i = 0; $i < strlen( $utf8_string ); $i++ ) {
	
	        $value = ord( $utf8_string[ $i ] );
	
	        if ( $value < 128 ) {
	            if ( $length && ( strlen($unicode) + 1 > $length ) )
	                break;
	            $unicode .= chr($value);
	        } else {
	            if ( count( $values ) == 0 ) $num_octets = ( $value < 224 ) ? 2 : 3;
	
	            $values[] = $value;
	
	            if ( $length && ( (strlen($unicode) + ($num_octets * 3)) > $length ) )
	                break;
	            if ( count( $values ) == $num_octets ) {
	                if ($num_octets == 3) {
	                    $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
	                } else {
	                    $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
	                }
	
	                $values = array();
	                $num_octets = 1;
	            }
	        }
	    }
	
	    return $unicode;
	}

public static function seems_utf8($Str) { # by bmorel at ssi dot fr
	    for ($i=0; $i<strlen($Str); $i++) {
	        if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
	        elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
	        elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
	        elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
	        elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
	        elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
	        else return false; # Does not match any model
	        for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
	            if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80))
	            return false;
	        }
	    }
	    return true;
	}


private static $cs = null;
public static function specCharsToASCII($s)
{
	if (!self::$cs) {
    if (!class_exists('t3lib_div')) return self::utf2ascii($s);
    self::$cs = t3lib_div::makeInstance('t3lib_cs');
  }
	$charset = $GLOBALS['TSFE']->metaCharset;
	if ($charset == "") $charset = "utf-8";
  $text = self::$cs->specCharsToASCII($charset, $s);
  $text = str_replace('\'', '', $text);
  $text = preg_replace('/\W+/', '-', $text);
  $text = trim($text, '-');
  $text = strtolower($text);
  return $text;
}	

public static function prepareLinkForCache($path,$lConf) {
  if (!empty($lConf->cache->prefix)) {
    $path = ($lConf->cache->prefix).$path;
  }
  if (!empty($lConf->cache->suffix)) {
    $path .= ($lConf->cache->suffix);
  }
  if (!empty($lConf->removetrailingslash) && $lConf->removetrailingslash==1) {
    $path = self::removeSlash($path);
	}
  return $path;
  
}

private static $sonderzeichen = array( 
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

public static function remove_accents($string) { 
      $string = strtr($string, self::$sonderzeichen);
      
      if ( !preg_match('/[\x80-\xff]/', $string) )
	        return $string;
	
	    if (self::seems_utf8($string)) {
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
	        chr(194).chr(163) => '');
	
	        $string = strtr($string, $chars);
	    } else {
	        // Assume ISO-8859-1 if not UTF-8
	        $chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
	            .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
	            .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
	            .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
	            .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
	            .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
	            .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
	            .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
	            .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
	            .chr(252).chr(253).chr(255);
	
	        $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";
	
	        $string = strtr($string, $chars['in'], $chars['out']);
	        $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
	        $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
	        $string = str_replace($double_chars['in'], $double_chars['out'], $string);
	    }
	
	    return $string;
	}  
}

?>
