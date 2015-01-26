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

	public function findByRootPage($pageId) {
		$query = $this->createQuery();
		$query->getQuerySettings()->setRespectStoragePage(FALSE)->setIgnoreEnableFields(TRUE)->setEnableFieldsToBeIgnored(array('hidden'));
		return $query->matching($query->logicalAnd(array($query->equals('pid', $pageId), $query->equals('tx_naworkuri_masterdomain', 0))))->execute();
	}

}

?>
