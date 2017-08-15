<?php

namespace Nawork\NaworkUri\Controller;
use Nawork\NaworkUri\Backend\Template\Components\Menu\Menu;
use Nawork\NaworkUri\Domain\Model\Domain;
use Nawork\NaworkUri\Domain\Model\Filter;
use Nawork\NaworkUri\Domain\Model\Language;
use Nawork\NaworkUri\Domain\Model\Url;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\View\TemplateView;

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

    protected $pageRootIds = [];

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

    /**
     * @var \TYPO3\CMS\Extbase\Service\ExtensionService
     * @inject
     */
    protected $extensionService;

	protected $userSettingsKey = 'tx_naworkuri_moduleUrl';

    public function __construct()
    {
        parent::__construct();
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
        $this->pageId = intval(GeneralUtility::_GP('id'));
        $pageRoots = $this->determineRootPages($this->pageId);
        foreach($pageRoots as $page) {
            $this->pageRootIds[] = $page['uid'];
        }
    }

    public function initializeAction() {
		parent::initializeAction();

        if (!$this->request->hasArgument('filter')) {
            /** @var Filter $filter */
            $filter = GeneralUtility::makeInstance(Filter::class);
            $filter->setPageId($this->pageId);
            $filter->setDomain($this->domainRepository->findByRootPage($this->pageRootIds)->getFirst());
            $filter->setLanguage(null);
            $filter->setScope('page');
            $this->request->setArgument('filter', $filter);
        }

//		if ($this->pageRenderer instanceof PageRenderer) {
//			$this->pageRenderer->addInlineLanguageLabelFile('EXT:nawork_uri/Resources/Private/Language/locallang_mod_url.xml', '', '', 2);
//			$this->pageRenderer->addInlineLanguageLabel('header_module', 'foo');
//			$this->pageRenderer->addJsFile(ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/JavaScript/jquery.urlModule.js');
//			$this->pageRenderer->addJsFile(ExtensionManagementUtility::extRelPath('nawork_uri') . 'Resources/Public/JavaScript/urlModule.js');
//		}

        if ($this->arguments->hasArgument('filter')) {
            $this->arguments->getArgument('filter')->getPropertyMappingConfiguration()->allowAllProperties();
            $this->arguments->getArgument('filter')->getPropertyMappingConfiguration()->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);
        }
	}

    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);

        if ($view instanceof BackendTemplateView) {
            $this->view->getModuleTemplate()->getPageRenderer()->addCssFile('../typo3conf/ext/nawork_uri/Resources/Public/CSS/styles.css' ,'stylesheet', 'all', '', false, false, '', true);
            $this->view->getModuleTemplate()->getPageRenderer()->addJsFile('../typo3conf/ext/nawork_uri/Resources/Public/JavaScript/script.js' ,'text/javascript', false, false, '', true);
            if (VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getCurrentTypo3Version()) <= 8005000) {
                $this->view->assign('includeRequireJsModules', ['TYPO3/CMS/Backend/ClickMenu']);
                $this->view->assign('menu', 'ClickMenu');
            } else {
                $this->view->assign('includeRequireJsModules', ['TYPO3/CMS/Backend/ContextMenu', 'TYPO3/CMS/NaworkUri/ContextMenuActions']);
                $this->view->assign('menu', 'ContextMenu');
            }

            if ($this->actionMethodName !== 'messageAction') {
                $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
                $returnUrl = rawurlencode(BackendUtility::getModuleUrl('naworkuri_NaworkUriUri'));
                $parameters = GeneralUtility::explodeUrl2Array('edit[tx_naworkuri_uri][0]=new&returnUrl=' . $returnUrl);
                $addUserLink = BackendUtility::getModuleUrl('record_edit', $parameters);
                $title = $this->getLanguageService()->sL(
                    'LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newRecordGeneral'
                );
                $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon(
                    'actions-document-new',
                    Icon::SIZE_SMALL
                );
                $addButton = $buttonBar->makeLinkButton()
                    ->setHref($addUserLink)
                    ->setTitle($title)
                    ->setIcon($icon);
                $buttonBar->addButton($addButton, ButtonBar::BUTTON_POSITION_LEFT);
            }

            if ($this->actionMethodName === 'indexRedirectsAction') {
                if (empty($this->pageId)) {
                    $this->pageId = $this->pageRootIds[0];
                }
                $this->addPageRootMenu();
            }
            if ($this->actionMethodName !== 'messageAction') {
                $this->addDomainMenu();
            }
            if ($this->actionMethodName === 'indexUrlsAction') {
                $this->addTypesMenu();
                $this->addLanguageMenu();
                $this->addScopeMenu();
            }
            $this->view->assign('moduleParameterPrefix', $this->extensionService->getPluginNamespace($this->request->getControllerExtensionName(), $this->request->getPluginName()));
        }

    }

    /**
     * @param Filter $filter
     */
	public function indexUrlsAction(Filter $filter) {
	    $forwardToMessage = false;
		if (!$filter->getDomain() instanceof Domain) {
		    $this->addFlashMessage('Please configure your domain records correctly. You need at least one domain record without a master domain.', 'Missing or faulty domain records', AbstractMessage::ERROR);
		    $forwardToMessage = true;
        } elseif (!$this->pageId > 0) {
		    $this->addFlashMessage('Please select a page from the page tree on the left', 'Select page', AbstractMessage::INFO);
		    $forwardToMessage = true;
		}
        if ($forwardToMessage) {
            $this->forward('message');
        }
        $this->view->assignMultiple(
            [
                'filter' => json_encode($filter),
                'userSettings' => json_encode($this->userSettings),
                'labels' => $this->buildLabelsObject()
            ]
        );
	}

    public function initializeIndexRedirectsAction()
    {
        if ($this->request->hasArgument('pageId')) {
            $this->pageId = $this->request->getArgument('pageId');
        }
        if (empty($this->pageId)) {
            $this->pageId = $this->userSettings['pageRoot'];
        }
	}

    /**
     * @param \Nawork\NaworkUri\Domain\Model\Filter $filter
     */
	public function indexRedirectsAction(Filter $filter) {
	    if (!$filter->getDomain() instanceof Domain) {
		    $this->addFlashMessage('Please configure your domain records correctly. You need at least one domain record without a master domain.', 'Missing or faulty domain records', AbstractMessage::ERROR);
		    $this->forward('message');
        }
		$this->setUserSettings('pageRoot', $this->pageId);
		$this->view->assignMultiple(
            [
                'filter' => json_encode($filter),
                'userSettings' => json_encode($this->userSettings),
                'labels' => $this->buildLabelsObject()
            ]
        );
	}

    public function messageAction() {

	}

    public function initializeLoadUrlsAction()
    {
        $this->defaultViewObjectName = TemplateView::class;
	}

	/**
	 *
     * @param \Nawork\NaworkUri\Domain\Model\Filter $filter
	 *
	 * @return string
	 */
	public function loadUrlsAction(Filter $filter) {
		$this->view->assign('urls', $this->urlRepository->findUrlsByFilter($filter));
		$tsConfig = BackendUtility::getPagesTSconfig($this->pageId);
		if(is_array($tsConfig) && !empty($tsConfig['mod.']['SHARED.']['defaultLanguageLabel'])) {
			$this->view->assign('defaultLanguage', array('label' => $tsConfig['mod.']['SHARED.']['defaultLanguageLabel'], 'flag' => $tsConfig['mod.']['SHARED.']['defaultLanguageFlag']));
		}
		$count = $this->urlRepository->countUrlsByFilter($filter);
        $maxPages = $count > 0 ? (int)($count / 100 + 1): 0;
        $end = ($filter->getOffset() + 1) * 100;
        if (!($filter->getOffset() < $maxPages - 1)) {
            $end -= (100 - $count % 100);
        } elseif ($count < 100) {
            $end = $count;
        }
        return json_encode(
            array(
                'html' => $this->view->render(),
                'count' => $count,
                'start' => $count > 0 ? $filter->getOffset() * 100 + 1 : 0,
                'end' => $end,
                'page' => $count > 0 ? $filter->getOffset() : 0,
                'pagesMax' => $maxPages
            )
        );
	}

	public function initializeLoadRedirectsAction()
    {
        $this->defaultViewObjectName = TemplateView::class;
	}

    /**
     * @param \Nawork\NaworkUri\Domain\Model\Filter $filter
     *
     * @return string
     */
	public function loadRedirectsAction(Filter $filter) {
	    $this->view->assign('urls', $this->urlRepository->findRedirectsByFilter($filter));
		$count = $this->urlRepository->countRedirectsByFilter($filter);
        $maxPages = $count > 0 ? (int)($count / 100 + 1): 0;
        $end = ($filter->getOffset() + 1) * 100;
        if (!($filter->getOffset() < $maxPages - 1)) {
            $end -= (100 - $count % 100);
        } elseif ($count < 100) {
            $end = $count;
        }
        return json_encode(
            array(
                'html' => $this->view->render(),
                'count' => $count,
                'start' => $count > 0 ? $filter->getOffset() * 100 + 1 : 0,
                'end' => $end,
                'page' => $count > 0 ? $filter->getOffset() : 0,
                'pagesMax' => $maxPages
            )
        );
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
     * Set lock state on the given url
     *
     * @param Url $url
     *
     * @return string
     */
    public function lockAction(Url $url)
    {
        $url->setLocked(true);
        $this->urlRepository->update($url);

        return '';
	}

	/**
	 * Unset lock state on the given url
	 *
	 * @param Url $url
     *
	 * @return string
	 */
	public function unlockAction(Url $url) {
		$url->setLocked(false);
		$this->urlRepository->update($url);

		return '';
	}

	/**
	 * Delete a url
	 *
	 * @param Url $url
	 * @return string
	 */
	public function deleteAction(Url $url) {
		$this->urlRepository->remove($url);

		return '';
	}

    /**
     * @param array $uids
     *
     * @return string
     */
    public function deleteSelectedAction(array $uids)
    {
        $this->urlRepository->deleteByUids($uids);

        return '';
	}

    private function addPageRootMenu()
    {
        if (count($this->pageRootIds) > 1) {
            $pageRootMenu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $pageRootMenu->setIdentifier('PageRootMenu')->setLabel(LocalizationUtility::translate('menu.pageRoots', 'NaworkUri'));
            foreach($this->pageRootIds as $rootId) {
                $pageRecord = BackendUtility::getRecord('pages', $rootId);
                if (is_array($pageRecord)) {
                    $pageRootMenu->addMenuItem(
                        $pageRootMenu->makeMenuItem()->setActive($this->pageId == $rootId)->setHref(
                            $this->uriBuilder->reset()->uriFor('indexRedirects', ['pageId' => $rootId])
                        )->setTitle($pageRecord['title'] . ' [ID: ' . $rootId . ']')
                    );
                }
            }

            $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($pageRootMenu);
        }
	}

    private function addDomainMenu()
    {
        // add domain menu
        /** @var Menu $domainMenu */
        $domainMenu = GeneralUtility::makeInstance(Menu::class);
        $domainMenu->setIdentifier('DomainMenu')->setLabel(LocalizationUtility::translate('menu.domain', 'NaworkUri'));
        $pageRoots = $this->determineRootPages($this->pageId);
		$pageIds = array();
		foreach($pageRoots as $page) {
			$pageIds[] = $page['uid'];
		}
        $this->uriBuilder->reset();
        $this->uriBuilder->setRequest($this->request);
        /** @var Filter $filter */
        $filter = $this->arguments->getArgument('filter')->getValue();
		/** @var Domain $domain */
        foreach($this->domainRepository->findByRootPage($pageIds) as $domain) {
            $domainMenu->addMenuItem(
                $domainMenu->makeMenuItem()
                    ->setTitle($domain->getDomainname())
                    ->setActive(
                        $domain === $filter->getDomain()
                    )
                    ->setDataAttributes(
                        [
                            'domain' => $domain->getUid()
                        ]
                    )
            );
        }
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($domainMenu);
	}

	private function addTypesMenu()
    {
        // add types menu
        /** @var Menu $typesMenu */
        $typesMenu = GeneralUtility::makeInstance(Menu::class);
        $typesMenu->setIdentifier('TypesMenu')->setLabel(LocalizationUtility::translate('menu.types', 'NaworkUri'))->setRenderAsCheckbox(true);
        $typesMenu->addMenuItem(
            $typesMenu->makeMenuItem()->setTitle(LocalizationUtility::translate('menu.types.normal', 'NaworkUri'))->setDataAttributes(['type' => 'normal'])
        );
        $typesMenu->addMenuItem(
            $typesMenu->makeMenuItem()->setTitle(LocalizationUtility::translate('menu.types.old', 'NaworkUri'))->setDataAttributes(['type' => 'old'])
        );
        $typesMenu->addMenuItem(
            $typesMenu->makeMenuItem()->setTitle(LocalizationUtility::translate('menu.types.locked', 'NaworkUri'))->setDataAttributes(['type' => 'locked'])
        );
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($typesMenu);
    }

    private function addLanguageMenu()
    {
        /** @var Filter $filter */
        $filter = $this->arguments->getArgument('filter')->getValue();
        /** @var Menu $languageMenu */
        $languageMenu = GeneralUtility::makeInstance(Menu::class);
        $languageMenu->setIdentifier('LanguageMenu')->setLabel(LocalizationUtility::translate('menu.language', 'NaworkUri'));
        $languageMenu->addMenuItem(
            $languageMenu->makeMenuItem()
                ->setTitle(LocalizationUtility::translate('menu.language.all', 'NaworkUri'))
                ->setActive($filter->getIgnoreLanguage() === 1)
                ->setDataAttributes(['language' => -1])
        );
        $languageMenu->addMenuItem(
            $languageMenu->makeMenuItem()
                ->setTitle(LocalizationUtility::translate('menu.language.default', 'NaworkUri'))
                ->setActive($filter->getLanguage() === null && $filter->getIgnoreLanguage() !== 1)
                ->setDataAttributes(['language' => 0])
        );
        /** @var Language $language */
        foreach($this->languageRepository->findAll() as $language) {
            $languageMenu->addMenuItem(
                $languageMenu->makeMenuItem()
                    ->setTitle($language->getTitle())
                    ->setActive($filter->getLanguage() === $language)
                    ->setDataAttributes(['language' => $language->getUid()])
            );
        }
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
    }

    private function addScopeMenu()
    {
        // add scope menu
        /** @var Menu $scopeMenu */
        $scopeMenu = GeneralUtility::makeInstance(Menu::class);
        $scopeMenu->setIdentifier('ScopeMenu')
            ->setLabel(LocalizationUtility::translate('menu.scope', 'NaworkUri'));
        $scopeMenu->addMenuItem(
            $scopeMenu->makeMenuItem()->setTitle(LocalizationUtility::translate('menu.scope.page', 'NaworkUri'))->setDataAttributes(['scope' => 'page'])
        );
        $scopeMenu->addMenuItem(
            $scopeMenu->makeMenuItem()->setTitle(LocalizationUtility::translate('menu.scope.subpages', 'NaworkUri'))->setDataAttributes(['scope' => 'subpages'])
        );
        $scopeMenu->addMenuItem(
            $scopeMenu->makeMenuItem()->setTitle(LocalizationUtility::translate('menu.scope.global', 'NaworkUri'))->setDataAttributes(['scope' => 'global'])
        );
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($scopeMenu);
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

	private function buildLabelsObject()
    {
        return json_encode(
            [
                'numberOfRecords' => LocalizationUtility::translate('label.numberOfRecords', $this->extensionName),
                'loadingMessage' => LocalizationUtility::translate('label.loadingMessage', $this->extensionName),
                'title' => [
                    'error' => LocalizationUtility::translate('label.title.error', $this->extensionName),
                    'delete' => LocalizationUtility::translate('label.title.delete', $this->extensionName),
                    'deleteSelected' => LocalizationUtility::translate('label.title.deleteSelected', $this->extensionName),
                ],
                'message' => [
                    'error' => LocalizationUtility::translate('label.message.error', $this->extensionName),
                    'delete' => LocalizationUtility::translate('label.message.delete', $this->extensionName),
                    'deleteSelected' => LocalizationUtility::translate('label.message.deleteSelected', $this->extensionName),
                ]
            ]
        );
    }
}
