<?php

namespace Nawork\NaworkUri\Domain\Repository;

/**
 * Description of UrlRepository
 *
 * @author thorben
 */
class DomainRepository extends AbstractRepository {

	public function findAll() {
		$query = $this->createQuery();
		return $query->matching($query->equals('tx_naworkuri_masterdomain', ''))->execute();
	}

	public function findByRootPage($pageIds) {
		$query = $this->createQuery();
		if(!is_array($pageIds)) {
			$pageIds = array($pageIds);
		}
		$query->getQuerySettings()->setRespectStoragePage(FALSE)->setIgnoreEnableFields(TRUE)->setEnableFieldsToBeIgnored(array('hidden'));
		$pidConstraints = array();
		foreach($pageIds as $pid) {
			$pidConstraints[] = $query->equals('pid', $pid);
		}
		return $query->matching($query->logicalAnd(array($query->logicalOr($pidConstraints), $query->equals('tx_naworkuri_masterdomain', 0))))->execute();
	}

}

?>
