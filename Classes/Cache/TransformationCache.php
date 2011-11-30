<?php

/**
 * Description of Tx_NaworkUri_Cache_TransformationCache
 *
 * @author thorben
 */
class Tx_NaworkUri_Cache_TransformationCache {

	protected static $parameters = array();

	public static function getTransformation($parameter, $value, $language) {
		if (array_key_exists($parameter, self::$parameters) && array_key_exists($language, self::$parameters[$parameter]) && array_key_exists($value, self::$parameters[$parameter][$language])) {
			return self::$parameters[$parameter][$language][$value];
		}
		throw new Tx_NaworkUri_Exception_TransformationValueNotFoundException($parameter, $value, $language);
	}

	public static function setTransformation($parameter, $value, $tranformation, $language) {
		if (!array_key_exists($parameter, self::$parameters)) {
			self::$parameters[$parameter] = array();
		}
		if (!array_key_exists($language, self::$parameters[$parameter])) {
			self::$parameters[$parameter][$language] = array();
		}
		self::$parameters[$parameter][$language][$value] = $tranformation;
	}

}

?>
