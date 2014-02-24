<?php

namespace Nawork\NaworkUri\Controller;

/**
 * Description of UrlController
 *
 * @author thorben
 */
class UrlController extends AbstractController {

	/**
	 *
	 * @var int
	 */
	protected $pageId;

	/**
	 *
	 * @var \Nawork\NaworkUri\Domain\Repository\UrlRepository
	 * @inject
	 */
	protected $urlRepository;

	/**
	 *
	 * @var \Nawork\NaworkUri\Domain\Repository\DomainRepository
	 * @inject
	 */
	protected $domainRepository;

	/**
	 *
	 * @var \Nawork\NaworkUri\Domain\Repository\LanguageRepository
	 * @inject
	 */
	protected $languageRepository;
	protected $userSettingsKey = 'tx_naworkuri_moduleUrl';

	public function initializeAction() {
		parent::initializeAction();
		$this->pageId = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'));
		if ($this->pageRenderer instanceof \TYPO3\CMS\Core\Page\PageRenderer) {
			$this->pageRenderer->addInlineLanguageLabelFile('EXT:nawork_uri/Resources/Private/Language/locallang_mod_url.xml', '', '', 2);
			$this->pageRenderer->addInlineLanguageLabel('header_module', 'foo');
			$this->pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/JavaScript/jquery.urlModule.js');
			$this->pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/JavaScript/urlModule.js');
		}
	}

	public function initializeIndexRedirectsAction() {
		$this->pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/JavaScript/redirectModule.js');
	}

	public function indexUrlsAction() {
		if (!$this->pageId > 0) {
			$this->forward('noPageId');
		}
		$this->view->assign('domains', $this->domainRepository->findAll());
		$this->view->assign('languages', $this->languageRepository->findAll());
		$this->view->assign('userSettings', json_encode($this->userSettings));
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
		$this->view->assign('storagePage', $extConf['storagePage']);
		$this->view->assign('id', $this->pageId);
	}

	public function indexRedirectsAction() {
		$this->view->assign('domains', $this->domainRepository->findAll());
		$this->view->assign('userSettings', json_encode($this->userSettings));
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
		$this->view->assign('storagePage', $extConf['storagePage']);
	}

	public function noPageIdAction() {
		
	}

	/**
	 *
	 * @param \Nawork\NaworkUri\Domain\Model\Domain $domain
	 * @param int $language
	 * @param array $types
	 * @param string $scope
	 * @param string $path
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return string
	 */
	public function ajaxLoadUrlsAction($domain = NULL, $language = NULL, $types = array(), $scope = NULL, $path = NULL, $offset = NULL, $limit = NULL) {
		/* @var $filter \Nawork\NaworkUri\Domain\Model\Filter */
		$filter = $this->objectManager->get('Nawork\\NaworkUri\\Domain\\Model\\Filter');
		$filter->setPageId($this->pageId);

		if ($domain instanceof \Nawork\NaworkUri\Domain\Model\Domain) {
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
	 * @param \Nawork\NaworkUri\Domain\Model\Domain $domain
	 * @param string $path
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return string
	 */
	public function ajaxLoadRedirectsAction($domain = NULL, $path = NULL, $offset = NULL, $limit = NULL) {
		/* @var $filter \Nawork\NaworkUri\Domain\Model\Filter */
		$filter = $this->objectManager->get('Nawork\\NaworkUri\\Domain\\Model\\Filter');

		if ($domain instanceof \Nawork\NaworkUri\Domain\Model\Domain) {
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
	 * @param \Nawork\NaworkUri\Domain\Model\Url $url
	 * @param boolean $includeAddOption
	 */
	public function contextMenuAction($url, $includeAddOption = FALSE) {
		$this->view->assign('url', $url);
		$this->view->assign('includeAddOption', $includeAddOption);
	}

	/**
	 * Toggle the lock state of an url
	 *
	 * @param \Nawork\NaworkUri\Domain\Model\Url $url
	 * @return string
	 */
	public function lockToggleAction($url) {
		$url->setLocked(!$url->getLocked());
		return '';
	}

	/**
	 * Delete a url
	 *
	 * @param \Nawork\NaworkUri\Domain\Model\Url $url
	 * @return string
	 */
	public function deleteAction($url) {
		$this->urlRepository->remove($url);
		return '';
	}

}

?>
