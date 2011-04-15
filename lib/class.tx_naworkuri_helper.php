<?php

/*
 * Helper functions
 */

class tx_naworkuri_helper {


	/**
	 * Explode URI Parameters
	 *
	 * @param string $param_string Parameter Part of URI
	 * @return array Exploded Parameters
	 */
	public function explode_parameters($param_string){
		/*
		$res = array();
		parse_str($param_string, $res);
		return $res;
		*/
		$result = array();
		$tmp = explode('&',$param_string);
		foreach ($tmp as $part){
			list($key,$value) = explode('=',$part);
			$result[$key] = $value;
		}
		ksort($result);
		return $result;
	}

	/**
	 * Implode URI Parameters
	 *
	 * @param array $params_array Parameter Array
	 * @return string Imploded Parameters
	 */
	public function implode_parameters($params_array){
		ksort($params_array);
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
	 * Sanitize the Path
	 *
	 * @param string $string
	 * @return string
	 */

	public function sanitize_uri($uri) {

		$uri = strip_tags($uri);
		$uri = strtolower($uri);

		$uri = $this->uri_handle_punctuation($uri);
		$uri = $this->uri_handle_whitespace($uri);
		$uri = $this->uri_transliterate($uri);
		$uri = $this->uri_limit_allowed_chars($uri);
 		$uri = $this->uri_make_wellformed($uri);

	    return $uri;
	}

	/**
	 * Remove whitespace characters from uri
	 *
	 * @param string $uri
	 * @return string
	 */
	function uri_handle_whitespace($uri){
		$uri = preg_replace( '/[\s\-]+/u', '-', $uri);
		return $uri;
	}

	/**
	 * Convert punctuation chars to -
	 *  ! " # $ & ' ( ) * + , : ; < = > ? @ [ \ ] ^ ` { | } <-- Old
	 *
	 *  	" #   & '               <   > ? @ [ \ ] ^ ` { | } %   < -- New

	 *
	 * @param string $uri
	 * @return string
	 */
	function uri_handle_punctuation($uri){
		$uri = preg_replace( '/[\!\"\#\&\'\?\@\[\\\\\]\^\`\{\|\}\%\<\>\,\+]+/u', '-', $uri);
		return $uri;
	}

	/**
	 * remove not allowed chars from uri
	 * allowed chars A-Za-z0-9 - _ . ~ ! ( ) * + , : ; =
	 *
	 * @param unknown_type $uri
	 * @return unknown
	 */
	function uri_limit_allowed_chars($uri){
		return preg_replace( '/[^A-Za-z0-9\/\-\_\.\~\!\(\)\*\:\;\=]+/u', '', $uri);
	}

	/**
	 * Remove some ugly uri-formatings:
	 * - slashes from the Start
	 * - double slashes
	 * - -/ /-
	 *
	 * @param string $uri
	 * @return string
	 */
	function uri_make_wellformed($uri){
		$uri = preg_replace( '/[\-]*[\/]+[\-]*/u', '/', $uri);
		$uri = preg_replace( '/^[\/]+/u', '', $uri);
		$uri = preg_replace('/\-$/','', $uri);
		return $uri;
	}

	/**
	 * Transliterate of strange utf-8 chars
	 *
	 * @TODO make translitertaion better
	 * @param string $uri
	 * @return string
	 */
	function uri_transliterate($uri){
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
        $uri = strtr($uri, $chars);

        return $uri;
	}

	public static function isActiveBeUserSession() {
		if(array_key_exists('be_typo_user', $_COOKIE) && !empty($_COOKIE['be_typo_user'])) {
			$tstamp = time() - $GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'];
			$beSessionResult = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'be_sessions', 'ses_id=\''.$GLOBALS['TYPO3_DB']->quoteStr($_COOKIE['be_typo_user'], 'be_sessions').'\' AND ses_tstamp>'.$tstamp);
			if(count($beSessionResult) == 1) {
				return true;
			}
		}
		return false;
	}
}

?>
