<?php

namespace Nawork\NaworkUri\Cache;
use Nawork\NaworkUri\Exception\TransformationValueNotFoundException;

/**
 * Description of TransformationCache
 *
 * @author thorben
 */
class TransformationCache {

	protected static $parameters = array();

	public static function getTransformation($parameter, $value, $language) {
		if (array_key_exists($parameter, self::$parameters) && array_key_exists($language, self::$parameters[$parameter]) && array_key_exists($value, self::$parameters[$parameter][$language])) {
			return self::$parameters[$parameter][$language][$value];
		}
		throw new TransformationValueNotFoundException($parameter, $value, $language);
	}

	public static function setTransformation($parameter, $value, $transformation, $language) {
		if (!array_key_exists($parameter, self::$parameters)) {
			self::$parameters[$parameter] = array();
		}
		if (!array_key_exists($language, self::$parameters[$parameter])) {
			self::$parameters[$parameter][$language] = array();
		}
		self::$parameters[$parameter][$language][$value] = $transformation;
	}
}
