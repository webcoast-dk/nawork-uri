<?php

/**
 * Description of Tx_NaworkUri_Cache_TransformationCache
 *
 * @author thorben
 */
class Tx_NaworkUri_Cache_TransformationCache {

	protected static $parameters = array();

	public static function getTransformation($parameter, $value) {
		if (array_key_exists($parameter, self::$parameters) && array_key_exists($value, self::$parameters[$parameter])) {
			return self::$parameters[$parameter][$value];
		}
		throw new Tx_NaworkUri_Exception_TransformationValueNotFoundException($parameter, $value);
	}

	public static function setTransformation($parameter, $value, $tranformation) {
		if (!array_key_exists($parameter, self::$parameters)) {
			self::$parameters[$parameter] = array();
		}
		self::$parameters[$parameter][$value] = $tranformation;
	}

}

?>
