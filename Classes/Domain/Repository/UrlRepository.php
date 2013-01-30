<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UrlRepository
 *
 * @author thorben
 */
class Tx_NaworkUri_Domain_Repository_UrlRepository extends Tx_NaworkUri_Domain_Repository_AbstractRepository {

	public function findUrlsByFilter(Tx_NaworkUri_Domain_Model_Filter $filter) {
		$query = $this->buildUrlQueryByFilter($filter);
		$query->setOffset((int) $filter->getOffset());
		if ($filter->getLimit() > 0) {
			$query->setLimit((int) $filter->getLimit());
		}
		return $query->setOrderings(array('path' => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING))->execute();
	}

	public function countUrlsByFilter(Tx_NaworkUri_Domain_Model_Filter $filter) {
		$query = $this->buildUrlQueryByFilter($filter);
		return $query->count();
	}

	private function getPidsRecursive($id, &$pids, $depth = 0) {
		if ($depth == 5) {
//			return;
		}
		/* @var $db t3lib_db */
		$db = $GLOBALS['TYPO3_DB'];
		$rows = $db->exec_SELECTgetRows('uid', 'pages', 'pid=' . intval($id));
		foreach ($rows as $page) {
			$pids[] = $page['uid'];
			$this->getPidsRecursive($page['uid'], $pids, ($depth + 1));
		}
	}

	/**
	 *
	 * @param Tx_NaworkUri_Domain_Model_Filter $filter
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	private function buildUrlQueryByFilter(Tx_NaworkUri_Domain_Model_Filter $filter) {
		$query = $this->createQuery();
		$constraints = array();

		if ($filter->getDomain() instanceof Tx_NaworkUri_Domain_Model_Domain) {
			$constraints[] = $query->equals('domain', $filter->getDomain());
		}

		if ($filter->getLanguage() instanceof Tx_NaworkUri_Domain_Model_Language) {
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
				$typeConstraints[] = $query->logicalAnd($query->equals('locked', 1), $query->equals('type', 0));
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
					$constraints = $query->logicalOr($pidConstraints);
				}
				break;
			case 'global':
				break;
			case 'page':
			default:
				$constraints[] = $query->equals('pageUid', $filter->getPageId());
				break;
		}
		$constraints[] = $query->logicalNot($query->equals('type', 2)); // we do not want redirects in this result
		if (count($constraints) > 0) {
			$query = $query->matching($query->logicalAnd($constraints));
		}
		return $query;
	}

}

?>
