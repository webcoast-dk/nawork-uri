<?php

namespace Nawork\NaworkUri\Controller;

/**
 * Description of AbstractController
 *
 * @author thorben
 */
abstract class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	protected $extensionName = 'NaworkUri';

	/**
	 *
	 * @var t3lib_PageRenderer
	 */
	protected $pageRenderer;

	/**
	 *
	 * @var template
	 */
	protected $template;
	protected $userSettingsKey = '';
	protected $userSettings = NULL;
	protected $userSettingsUpdated = FALSE;

	/**
	 * Processes a general request. The result can be returned by altering the given response.
	 *
	 * @param Tx_Extbase_MVC_RequestInterface $request The request object
	 * @param Tx_Extbase_MVC_ResponseInterface $response The response, modified by this handler
	 * @throws Tx_Extbase_MVC_Exception_UnsupportedRequestType if the controller doesn't support the current request type
	 * @return void
	 */
	public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response) {

		if (intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ajax')) < 1) {
			$this->template = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('template');
			$this->pageRenderer = $this->template->getPageRenderer();
			$this->pageRenderer->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/CSS/module.css');
			$this->pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/Contrib/jQuery/jquery-1.9.0.min.js');
			$this->pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/Contrib/mootools/mootoolsCore-1.4.5.js');
			$this->pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/Contrib/moo4q/Class.Mutators.jQuery.js');
			$this->pageRenderer->addInlineLanguageLabelFile('EXT:nawork_uri/Resources/Private/Language/locallang_mod_url.xml', '', '', 2);

			$GLOBALS['SOBE'] = new stdClass();
			$GLOBALS['SOBE']->doc = $this->template;

			parent::processRequest($request, $response);
			$pageHeader = $this->template->startpage(
				$GLOBALS['LANG']->sL('LLL:EXT:nawork_uri/Resources/Private/Language/locallang_mod_url.xml:header_module')
			);
			$pageEnd = $this->template->endPage();
			$response->setContent($pageHeader . $response->getContent() . $pageEnd);
		} else {
			parent::processRequest($request, $response);
		}
	}

	protected function initializeAction() {
		$this->loadUserSettings();
	}

	protected function callActionMethod() {
		parent::callActionMethod();
		$this->storeUserSettings();
	}

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function updateSettingsAction($key, $value) {
		if (!empty($key) && !empty($value)) {
			$this->setUserSettings($key, $value);
			return '0';
		}
		return '1';
	}

	protected function loadUserSettings() {
		/* @var $BE_USER \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
		global $BE_USER;
		$this->userSettings = $BE_USER->getModuleData($this->userSettingsKey);
		if ($this->userSettings == NULL || !is_array($this->userSettings)) {
			$this->userSettings = array(
				'filter' => array(
					'pageSize' => 0,
					'domain' => -1,
					'language' => -1,
					'scope' => 'page'
				),
				'columnWidth' => array(
					'path' => 0
				)
			);
			$BE_USER->pushModuleData($this->userSettingsKey, $this->userSettings);
		}
	}

	protected function storeUserSettings() {
		/* @var $BE_USER \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
		global $BE_USER;
		if ($this->userSettingsUpdated) {
			$BE_USER->pushModuleData($this->userSettingsKey, $this->userSettings);
		}
	}

	protected function setUserSettings($key, $value) {
		$keyParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('.', $key, TRUE);
		$tmp = array(
			$this->userSettings
		);
		$settingsKeyNotFound = FALSE;
		foreach ($keyParts as $index => $p) {
			if (!array_key_exists($p, $tmp[$index])) {
				$tmp[$index][$p] = ($index < count($keyParts) - 1) ? array() : $value;
			}
			$tmp[($index + 1)] = $tmp[$index][$p];
		}
		$tmp[count($tmp) - 1] = $value;
		$keyParts = array_reverse($keyParts);
		foreach ($keyParts as $index => $revPart) {
			$tmp[count($tmp) - $index - 2][$revPart] = $tmp[count($tmp) - $index - 1];
		}
		$this->userSettings = $tmp[0];
		$this->userSettingsUpdated = TRUE;
	}

}

?>
