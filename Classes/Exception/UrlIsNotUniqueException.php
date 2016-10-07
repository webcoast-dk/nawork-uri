<?php

namespace Nawork\NaworkUri\Exception;

/**
 * Description of UrlIsNotUniqueException
 *
 * @author thorben
 */
class UrlIsNotUniqueException extends \Exception {
	private $path;
	private $domain;
	private $parameters;
	private $language;

    /**
     * UrlIsNotUniqueException constructor.
     *
     * @param string $path
     * @param int    $domain
     * @param array  $parameters
     * @param int    $language
     */
	function __construct($path, $domain, $parameters, $language) {
		$this->path = $path;
		$this->domain = $domain;
		$this->parameters = $parameters;
		$this->language = $language;
	}

	public function getPath() {
		return $this->path;
	}

	public function getDomain() {
		return $this->domain;
	}

	public function getParameters() {
		return $this->parameters;
	}

	public function getLanguage() {
		return $this->language;
	}

}
