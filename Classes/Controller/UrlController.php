<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UrlController
 *
 * @author thorben
 */
class Tx_NaworkUri_Controller_UrlController extends Tx_NaworkUri_Controller_AbstractController {

	/**
	 *
	 * @var int
	 */
	protected $pageId;

	/**
	 *
	 * @var Tx_NaworkUri_Domain_Repository_UrlRepository
	 */
	protected $urlRepository;

	/**
	 *
	 * @var Tx_NaworkUri_Domain_Repository_DomainRepository
	 */
	protected $domainRepository;

	/**
	 *
	 * @var Tx_NaworkUri_Domain_Repository_LanguageRepository
	 */
	protected $languageRepository;
	protected $userSettingsKey = 'tx_naworkuri_moduleUrl';
	protected $userSettings = NULL;
	protected $userSettingsUpdated = FALSE;

	public function initializeAction() {
		$this->pageId = intval(t3lib_div::_GP('id'));
		if ($this->pageRenderer instanceof t3lib_PageRenderer) {
			$this->pageRenderer->addInlineLanguageLabelFile('EXT:nawork_uri/Resources/Private/Language/locallang_mod_url.xml', '', '', 2);
			$this->pageRenderer->addInlineLanguageLabel('header_module', 'foo');
			$this->pageRenderer->addJsFile(t3lib_extMgm::extRelPath('nawork_uri') . 'Resources/Public/JavaScript/jquery.urlModule.js');
		}
		$this->urlRepository = $this->objectManager->get('Tx_NaworkUri_Domain_Repository_UrlRepository');
		$this->domainRepository = $this->objectManager->get('Tx_NaworkUri_Domain_Repository_DomainRepository');
		$this->languageRepository = $this->objectManager->get('Tx_NaworkUri_Domain_Repository_LanguageRepository');
		$this->loadUserSettings();
	}

	protected function callActionMethod() {
		parent::callActionMethod();
		$this->storeUserSettings();
	}

	public function indexAction() {
		if (!$this->pageId > 0) {
			$this->forward('noPageId');
		}
		$this->view->assign('domains', $this->domainRepository->findAll());
		$this->view->assign('languages', $this->languageRepository->findAll());
		$this->view->assign('userSettings', json_encode($this->userSettings));
	}

	public function noPageIdAction() {

	}

	/**
	 *
	 * @param Tx_NaworkUri_Domain_Model_Domain $domain
	 * @param mixed $language
	 * @param array $types
	 * @param string $scope
	 * @param string $path
	 * @param int $offset
	 * @param int $limit
	 */
	public function ajaxLoadUrlsAction(Tx_NaworkUri_Domain_Model_Domain $domain = NULL, $language = NULL, $types = array(), $scope = NULL, $path = NULL, $offset = NULL, $limit = NULL) {
		/* @var $filter Tx_NaworkUri_Domain_Model_Filter */
		$filter = $this->objectManager->get('Tx_NaworkUri_Domain_Model_Filter');
		$filter->setPageId($this->pageId);

		if ($domain instanceof Tx_NaworkUri_Domain_Model_Domain) {
			$filter->setDomain($domain);
		}

		if ($language > -1) {
			$filter->setLanguage($language);
		}

		if (is_array($types) && count($types) > 0) {
			foreach ($types as $t) {
				$filter->addType($t);
			}
		}

		if ($scope != NULL) {
			$filter->setScope($scope);
		}

		if ($path != NULL) {
			$filter->setPath($path);
		}

		if ($offset !== NULL) {
			$filter->setOffset($offset);
		}

		if ($limit != NULL && $limit > 0) {
			$filter->setLimit($limit);
			$this->setUserSettings('filter.pageSize', $limit);
		}

		$this->view->assign('urls', $this->urlRepository->findUrlsByFilter($filter));
		return json_encode(array(
				'html' => $this->view->render(),
				'count' => $this->urlRepository->countUrlsByFilter($filter)
			));
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

	/**
	 *
	 * @param Tx_NaworkUri_Domain_Model_Url $url
	 */
	public function contextMenuAction(Tx_NaworkUri_Domain_Model_Url $url) {
		$this->view->assign('url', $url);
	}

	/**
	 * Toggle the lock state of an url
	 *
	 * @param Tx_NaworkUri_Domain_Model_Url $url
	 * @return string
	 */
	public function lockToggleAction(Tx_NaworkUri_Domain_Model_Url $url) {
		$url->setLocked(!$url->getLocked());
		return '';
	}

	/**
	 * Delete a url
	 *
	 * @param Tx_NaworkUri_Domain_Model_Url $url
	 * @return string
	 */
	public function deleteAction(Tx_NaworkUri_Domain_Model_Url $url) {
		$this->urlRepository->remove($url);
		return '';
	}

	private function loadUserSettings() {
		/* @var $BE_USER t3lib_beUserAuth */
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

	private function storeUserSettings() {
		/* @var $BE_USER t3lib_beUserAuth */
		global $BE_USER;
		if ($this->userSettingsUpdated) {
			$BE_USER->pushModuleData($this->userSettingsKey, $this->userSettings);
		}
	}

	private function setUserSettings($key, $value) {
		$keyParts = t3lib_div::trimExplode('.', $key, TRUE);
		$tmp = array(
			$this->userSettings
		);
		$settingsKeyNotFound = FALSE;
		foreach ($keyParts as $index => $p) {
			if (array_key_exists($p, $tmp[$index])) {
				$tmp[($index + 1)] = $tmp[$index][$p];
			} else {
				$settingsKeyNotFound = FALSE;
			}
		}
		if (!$settingsKeyNotFound) {
			$tmp[count($tmp) - 1] = $value;
			$keyParts = array_reverse($keyParts);
			foreach ($keyParts as $index => $revPart) {
				$tmp[count($tmp) - $index - 2][$revPart] = $tmp[count($tmp) - $index - 1];
			}
			$this->userSettings = $tmp[0];
			$this->userSettingsUpdated = TRUE;
		}
	}

}

?>
