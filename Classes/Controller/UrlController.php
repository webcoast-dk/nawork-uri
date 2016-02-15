<?php

namespace Nawork\NaworkUri\Controller;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

	/**
	 * @var DatabaseConnection
	 */
	protected $databaseConnection;

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
		$this->databaseConnection = $GLOBALS['TYPO3_DB'];
	}

	public function initializeIndexRedirectsAction() {
		$this->pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/JavaScript/redirectModule.js');
	}

	public function indexUrlsAction() {
		if (!$this->pageId > 0) {
			$this->forward('noPageId');
		}
		$pageRoots = $this->determineRootPages($this->pageId);
		$pageIds = array();
		foreach($pageRoots as $page) {
			$pageIds[] = $page['uid'];
		}
		$this->view->assign('domains', $this->domainRepository->findByRootPage($pageIds));
		$this->view->assign('languages', $this->languageRepository->findAll());
		$this->view->assign('userSettings', json_encode($this->userSettings));
		$this->view->assign('id', $this->pageId);
	}

	public function indexRedirectsAction() {
		$pageRoots = $this->determineRootPages();
		// get page roots
		if(count($pageRoots) === 1) {
			$pageId = $pageRoots[0]['uid'];
		} else {
			$pageId = GeneralUtility::_GP('pageRoot');
			if(empty($pageId)) {
				$pageId = $this->userSettings['pageRoot'];
			}
		}
		if(empty($pageId)) {
			$pageId = $pageRoots[0]['uid'];
		}
		// make sure we store the selected page root
		$this->setUserSettings('pageRoot', $pageId);
		$this->view->assign('domains', $this->domainRepository->findByRootPage($pageId)->toArray());
		$this->view->assign('userSettings', json_encode($this->userSettings));
		$this->view->assign('pageRoots', $pageRoots);
		$this->view->assign('currentPageRoot', $pageId);
	}

	private function determineRootPages($pid = NULL) {
		$pageRoots = array();
		if($pid > 0) {
			do {
				$page = $this->databaseConnection->exec_SELECTgetSingleRow('*', 'pages', 'uid=' . (int) $pid);
				$pid = $page['pid'];
			} while($page['pid'] != 0);
			$pageRoots[] = $page;
		} else {
			// get page roots
			$pageRoots = $this->databaseConnection->exec_SELECTgetRows('uid, title', 'pages', 'is_siteroot=1');

		}
		if (!is_array($pageRoots) || empty($pageRoots)) {
			$pageRoots = $this->databaseConnection->exec_SELECTgetRows(
				'uid, title',
				'pages',
				'pid=0',
				'',
				'sorting ASC',
				1
			);
		}
		return $pageRoots;
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
			$filter->setDomains(array($domain));
		}

		if ($language > -1) {
			$filter->setLanguage($language);
		}

		if (is_array($types) && count($types) > 0) {
			foreach ($types as $t) {
				$filter->addType($t);
			}
		} else {
			$filter->setTypes(array('normal', 'locked', 'old'));
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
		$tsConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pageId);
		if(is_array($tsConfig) && !empty($tsConfig['mod.']['SHARED.']['defaultLanguageLabel'])) {
			$this->view->assign('defaultLanguage', array('label' => $tsConfig['mod.']['SHARED.']['defaultLanguageLabel'], 'flag' => $tsConfig['mod.']['SHARED.']['defaultLanguageFlag']));
		}
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
		$pageRoot = $this->userSettings['pageRoot'];
		/* @var $filter \Nawork\NaworkUri\Domain\Model\Filter */
		$filter = $this->objectManager->get('Nawork\\NaworkUri\\Domain\\Model\\Filter');

		if ($domain instanceof \Nawork\NaworkUri\Domain\Model\Domain) {
			$filter->setDomains(array($domain));
		} elseif($domain === NULL) {
			$filter->setDomains($this->domainRepository->findByRootPage($pageRoot));
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
		$tsConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($pageRoot);
		if(is_array($tsConfig) && !empty($tsConfig['mod.']['SHARED.']['defaultLanguageLabel'])) {
			$this->view->assign('defaultLanguage', array('label' => $tsConfig['mod.']['SHARED.']['defaultLanguageLabel'], 'flag' => $tsConfig['mod.']['SHARED.']['defaultLanguageFlag']));
		}
		return json_encode(array(
				'html' => $this->view->render(),
				'count' => $this->urlRepository->countRedirectsByFilter($filter)
			));
	}

	/**
	 *
	 * @param \Nawork\NaworkUri\Domain\Model\Url $url
	 * @param boolean $includeAddOption
     * @param string  $returnUrl
	 */
	public function contextMenuAction($url, $includeAddOption = FALSE,  $returnUrl = '') {
		$this->view->assign('url', $url);
		$this->view->assign('includeAddOption', $includeAddOption);
        $this->view->assign('returnUrl', $returnUrl);
	}

	/**
	 * Toggle the lock state of an url
	 *
	 * @param \Nawork\NaworkUri\Domain\Model\Url $url
	 * @return string
	 */
	public function lockToggleAction($url) {
		$url->setLocked(!$url->getLocked());
		$this->urlRepository->update($url);
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
