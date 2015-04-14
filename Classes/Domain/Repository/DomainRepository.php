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
		$querySettings = $query->getQuerySettings();
		$querySettings->setRespectStoragePage(FALSE);
		$querySettings->setIgnoreEnableFields(TRUE);
		$querySettings->setEnableFieldsToBeIgnored(array('hidden'));
		$pidConstraints = array();
		foreach($pageIds as $pid) {
			$pidConstraints[] = $query->equals('pid', $pid);
		}
		return $query->matching($query->logicalAnd(array($query->logicalOr($pidConstraints), $query->equals('tx_naworkuri_masterdomain', 0))))->execute();
	}

}

?>
