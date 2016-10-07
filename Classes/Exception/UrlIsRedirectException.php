<?php

namespace Nawork\NaworkUri\Exception;

/**
 * Description of UrlIsRedirectException
 *
 * @author thorben
 */
class UrlIsRedirectException extends \Exception {

	protected $url;

	public function __construct($url) {
		parent::__construct('The url is a redirect');
		$this->url = $url;
	}

	public function getUrl() {
		return $this->url;
	}

}
