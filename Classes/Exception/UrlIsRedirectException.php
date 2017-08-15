<?php

namespace Nawork\NaworkUri\Exception;

/**
 * Description of UrlIsRedirectException
 *
 * @author thorben
 */
class UrlIsRedirectException extends \Exception {

    /**
     * @var array
     */
	protected $url;

    /**
     * UrlIsRedirectException constructor.
     *
     * @param array $url
     */
	public function __construct($url) {
		parent::__construct('The url is a redirect');
		$this->url = $url;
	}

    /**
     * @return array
     */
	public function getUrl() {
		return $this->url;
	}

}
