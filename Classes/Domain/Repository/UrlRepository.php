<?php

namespace Nawork\NaworkUri\Domain\Repository;
use Nawork\NaworkUri\Domain\Model\Filter;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Description of UrlRepository
 *
 * @author thorben
 */
class UrlRepository extends Repository {

	public function findUrlsByFilter(Filter $filter) {
		$query = $this->buildUrlQueryByFilter($filter);
		if ($filter->getLimit() > 0) {
			$query->setOffset((int) $filter->getOffset());
			$query->setLimit((int) $filter->getLimit());
		}
		return $query->setOrderings(array('path' => QueryInterface::ORDER_ASCENDING))->execute();
	}

	public function countUrlsByFilter(Filter $filter) {
		$query = $this->buildUrlQueryByFilter($filter);
		return $query->count();
	}

	public function findRedirectsByFilter(Filter $filter) {
		$query = $this->buildRedirectQueryByFilter($filter);
		if ($filter->getLimit() > 0) {
			$query->setOffset((int) $filter->getOffset());
			$query->setLimit((int) $filter->getLimit());
		}
		return $query->setOrderings(array('path' => QueryInterface::ORDER_ASCENDING))->execute();
	}

	public function countRedirectsByFilter(Filter $filter) {
		$query = $this->buildRedirectQueryByFilter($filter);
		return $query->count();
	}

	private function getPidsRecursive($id, &$pids, $depth = 0) {
		if ($depth == 5) {
//			return;
		}
		/* @var $db \TYPO3\CMS\Core\Database\DatabaseConnection */
		$db = $GLOBALS['TYPO3_DB'];
		$rows = $db->exec_SELECTgetRows('uid', 'pages', 'pid=' . intval($id));
		foreach ($rows as $page) {
			$pids[] = $page['uid'];
			$this->getPidsRecursive($page['uid'], $pids, ($depth + 1));
		}
	}

	/**
	 *
	 * @param \Nawork\NaworkUri\Domain\Model\Filter $filter
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	private function buildUrlQueryByFilter(Filter $filter) {
		$query = $this->createQuery();
		// ignore language, because all urls should be selected
		$query->getQuerySettings()->setRespectSysLanguage(FALSE);

		$constraints = array();

		$domainContraints = array();
		foreach($filter->getDomains() as $domain) {
			$domainContraints[] = $query->equals('domain', $domain);
		}
		if(count($domainContraints) > 0) {
			$constraints[] = $query->logicalOr($domainContraints);
		}

		if ($filter->getLanguage() > -1) {
			$constraints[] = $query->equals('sysLanguageUid', $filter->getLanguage());
		}

		if (count($filter->getTypes()) > 0) {
			$typeConstraints = array();
			if (in_array('normal', $filter->getTypes())) {
				$typeConstraints[] = $query->equals('type', 0);
			}

			if (in_array('old', $filter->getTypes())) {
				$typeConstraints[] = $query->equals('type', 1);
			}

			if (in_array('locked', $filter->getTypes())) { // if we show locked, set locked=1 and type=0, because only type 0 urls can be locked
				$typeConstraints[] = $query->logicalAnd([$query->equals('locked', 1), $query->equals('type', 0)]);
			}
			if (count($typeConstraints) > 0) {
				$constraints[] = $query->logicalOr($typeConstraints);
			}
		}
		switch ($filter->getScope()) {
			case 'subtree':
				$pidConstraints = array();
				$pageIds = array($filter->getPageId());
				$this->getPidsRecursive($filter->getPageId(), $pageIds);
				foreach ($pageIds as $pid) {
					$pidConstraints[] = $query->equals('pageUid', $pid);
				}
				if (count($pidConstraints) > 0) {
					$constraints[] = $query->logicalOr($pidConstraints);
				}
				break;
			case 'global':
				break;
			case 'page':
			default:
				$constraints[] = $query->equals('pageUid', $filter->getPageId());
				break;
		}
		$path = $filter->getPath();
		if(!empty($path)) {
			$constraints[] = $query->like('path', str_replace('*', '%', $path));
		}
		$constraints[] = $query->logicalNot($query->equals('type', 2)); // we do not want redirects in this result
		if (count($constraints) > 0) {
			$query = $query->matching($query->logicalAnd($constraints));
		}
		return $query;
	}

	private function buildRedirectQueryByFilter(Filter $filter) {
		$query = $this->createQuery();
		// ignore language, because all urls should be selected
		$query->getQuerySettings()->setRespectSysLanguage(FALSE);$constraints = array();

		$domainContraints = array();
		foreach($filter->getDomains() as $domain) {
			$domainContraints[] = $query->equals('domain', $domain);
		}
		if(count($domainContraints) > 0) {
			$constraints[] = $query->logicalOr($domainContraints);
		}

		$path = $filter->getPath();
		if(!empty($path)) {
			$constraints[] = $query->like('path', str_replace('*', '%', $path));
		}
		$constraints[] = $query->logicalOr([$query->equals('type', 2), $query->equals('type', 3)]); // we want only redirects in this result
		if (count($constraints) > 0) {
			$query = $query->matching($query->logicalAnd($constraints));
		}
		return $query;
	}

}
