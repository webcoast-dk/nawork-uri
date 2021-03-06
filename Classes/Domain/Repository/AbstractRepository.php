<?php

namespace Nawork\NaworkUri\Domain\Repository;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Description of UrlRepository
 *
 * @author thorben
 */
class AbstractRepository extends Repository {

	public function createQuery() {
		$query = parent::createQuery();
		$query->getQuerySettings()->setRespectStoragePage(FALSE)->setRespectSysLanguage(FALSE);
		return $query;
	}

}
