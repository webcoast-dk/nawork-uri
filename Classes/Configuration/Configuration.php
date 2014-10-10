<?php

namespace Nawork\NaworkUri\Configuration;


class Configuration {
	/**
	 * @var \Nawork\NaworkUri\Configuration\GeneralConfiguration
	 */
	protected $generalConfiguration;
	/**
	 * @var \Nawork\NaworkUri\Configuration\TransliterationsConfiguration
	 */
	protected $transliterationsConfiguration;
	/**
	 * @var \Nawork\NaworkUri\Configuration\PageNotFoundConfiguration
	 */
	protected $pageNotFoundConfiguration;
	/**
	 * @var \Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration
	 */
	protected $pageNotAccessibleConfiguration;
	/**
	 * @var \Nawork\NaworkUri\Configuration\PageNotTranslatedConfiguration
	 */
	protected $pageNotTranslatedConfiguration;
	/**
	 * @var \Nawork\NaworkUri\Configuration\ParametersConfiguration
	 */
	protected $parametersConfiguration;

	/**
	 * @return \Nawork\NaworkUri\Configuration\GeneralConfiguration
	 */
	public function getGeneralConfiguration() {
		return $this->generalConfiguration;
	}

	/**
	 * @return \Nawork\NaworkUri\Configuration\TransliterationsConfiguration
	 */
	public function getTransliterationsConfiguration() {
		return $this->transliterationsConfiguration;
	}

	/**
	 * @return \Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration
	 */
	public function getPageNotAccessibleConfiguration() {
		return $this->pageNotAccessibleConfiguration;
	}

	/**
	 * @return \Nawork\NaworkUri\Configuration\PageNotFoundConfiguration
	 */
	public function getPageNotFoundConfiguration() {
		return $this->pageNotFoundConfiguration;
	}

	/**
	 * @return \Nawork\NaworkUri\Configuration\PageNotTranslatedConfiguration
	 */
	public function getPageNotTranslatedConfiguration() {
		return $this->pageNotTranslatedConfiguration;
	}

	/**
	 * @return \Nawork\NaworkUri\Configuration\ParametersConfiguration
	 */
	public function getParametersConfiguration() {
		return $this->parametersConfiguration;
	}

	/**
	 * @param \Nawork\NaworkUri\Configuration\GeneralConfiguration $generalConfiguration
	 */
	public function setGeneralConfiguration($generalConfiguration) {
		$this->generalConfiguration = $generalConfiguration;
	}

	/**
	 * @param \Nawork\NaworkUri\Configuration\TransliterationsConfiguration $transliterationsConfiguration
	 */
	public function setTransliterationsConfiguration($transliterationsConfiguration) {
		$this->transliterationsConfiguration = $transliterationsConfiguration;
	}

	/**
	 * @param \Nawork\NaworkUri\Configuration\PageNotAccessibleConfiguration $pageNotAccessibleConfiguration
	 */
	public function setPageNotAccessibleConfiguration($pageNotAccessibleConfiguration) {
		$this->pageNotAccessibleConfiguration = $pageNotAccessibleConfiguration;
	}

	/**
	 * @param \Nawork\NaworkUri\Configuration\PageNotFoundConfiguration $pageNotFoundConfiguration
	 */
	public function setPageNotFoundConfiguration($pageNotFoundConfiguration) {
		$this->pageNotFoundConfiguration = $pageNotFoundConfiguration;
	}

	/**
	 * @param \Nawork\NaworkUri\Configuration\PageNotTranslatedConfiguration $pageNotTranslatedConfiguration
	 */
	public function setPageNotTranslatedConfiguration($pageNotTranslatedConfiguration) {
		$this->pageNotTranslatedConfiguration = $pageNotTranslatedConfiguration;
	}

	/**
	 * @param \Nawork\NaworkUri\Configuration\ParametersConfiguration $parametersConfiguration
	 */
	public function setParametersConfiguration($parametersConfiguration) {
		$this->parametersConfiguration = $parametersConfiguration;
	}
}
 