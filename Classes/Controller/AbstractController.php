<?php

namespace Nawork\NaworkUri\Controller;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;

/**
 * Description of AbstractController
 *
 * @author thorben
 */
abstract class AbstractController extends ActionController {

	protected $extensionName = 'NaworkUri';

	/**
	 *
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected $pageRenderer;

	/**
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @inject
	 */
	protected $template;
	protected $userSettingsKey = '';
	protected $userSettings = NULL;
	protected $userSettingsUpdated = FALSE;

	/**
	 * Processes a general request. The result can be returned by altering the given response.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request object
	 * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, modified by this handler
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException if the controller doesn't support the current request type
	 * @return void
	 */
	public function processRequest(RequestInterface $request, ResponseInterface $response) {

		if (intval(GeneralUtility::_GP('ajax')) < 1) {
			$this->pageRenderer = $this->template->getPageRenderer();
			$this->pageRenderer->addCssFile(ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/CSS/module.css');
			$this->pageRenderer->addJsFile(ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/Contrib/jQuery/jquery-1.9.0.min.js');
			$this->pageRenderer->addJsFile(ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/Contrib/mootools/mootoolsCore-1.4.5.js');
			$this->pageRenderer->addJsFile(ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/Contrib/moo4q/Class.Mutators.jQuery.js');
			$this->pageRenderer->addInlineLanguageLabelFile('EXT:nawork_uri/Resources/Private/Language/locallang_mod_url.xml', '', '', 2);

			$GLOBALS['SOBE'] = new \stdClass();
			$GLOBALS['SOBE']->doc = $this->template;

			parent::processRequest($request, $response);
			$pageHeader = $this->template->startPage(
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
	 *
	 * @return int
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
		$keyParts = GeneralUtility::trimExplode('.', $key, TRUE);
		$tmp = array(
			$this->userSettings
		);
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
