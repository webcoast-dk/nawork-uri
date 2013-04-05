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

	public function initializeAction() {
		parent::initializeAction();
		$this->pageId = intval(t3lib_div::_GP('id'));
		if ($this->pageRenderer instanceof t3lib_PageRenderer) {
			$this->pageRenderer->addInlineLanguageLabelFile('EXT:nawork_uri/Resources/Private/Language/locallang_mod_url.xml', '', '', 2);
			$this->pageRenderer->addInlineLanguageLabel('header_module', 'foo');
			$this->pageRenderer->addJsFile(t3lib_extMgm::extRelPath('nawork_uri') . 'Resources/Public/JavaScript/jquery.urlModule.js');
			$this->pageRenderer->addJsFile(t3lib_extMgm::extRelPath('nawork_uri') . 'Resources/Public/JavaScript/urlModule.js');
		}
		$this->urlRepository = $this->objectManager->get('Tx_NaworkUri_Domain_Repository_UrlRepository');
		$this->domainRepository = $this->objectManager->get('Tx_NaworkUri_Domain_Repository_DomainRepository');
		$this->languageRepository = $this->objectManager->get('Tx_NaworkUri_Domain_Repository_LanguageRepository');
	}

	public function initializeIndexRedirectsAction() {
		$this->pageRenderer->addJsFile(t3lib_extMgm::extRelPath('nawork_uri') . 'Resources/Public/JavaScript/redirectModule.js');
	}

	public function indexUrlsAction() {
		if (!$this->pageId > 0) {
			$this->forward('noPageId');
		}
		$this->view->assign('domains', $this->domainRepository->findAll());
		$this->view->assign('languages', $this->languageRepository->findAll());
		$this->view->assign('userSettings', json_encode($this->userSettings));
	}

	public function indexRedirectsAction() {
		$this->view->assign('domains', $this->domainRepository->findAll());
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
	 * @param Tx_NaworkUri_Domain_Model_Domain $domain
	 * @param string $path
	 * @param int $offset
	 * @param int $limit
	 */
	public function ajaxLoadRedirectsAction(Tx_NaworkUri_Domain_Model_Domain $domain = NULL, $path = NULL, $offset = NULL, $limit = NULL) {
		/* @var $filter Tx_NaworkUri_Domain_Model_Filter */
		$filter = $this->objectManager->get('Tx_NaworkUri_Domain_Model_Filter');

		if ($domain instanceof Tx_NaworkUri_Domain_Model_Domain) {
			$filter->setDomain($domain);
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

		$this->view->assign('urls', $this->urlRepository->findRedirectsByFilter($filter));
		return json_encode(array(
				'html' => $this->view->render(),
				'count' => $this->urlRepository->countRedirectsByFilter($filter)
			));
	}

	/**
	 *
	 * @param Tx_NaworkUri_Domain_Model_Url $url
	 * @param boolean $includeAdd
	 */
	public function contextMenuAction(Tx_NaworkUri_Domain_Model_Url $url, $includeAddOption = FALSE) {
		$this->view->assign('url', $url);
		$this->view->assign('includeAddOption', $includeAddOption);
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

}

?>
