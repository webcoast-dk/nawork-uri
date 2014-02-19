<?php

namespace Nawork\NaworkUri\Domain\Repository;

/**
 * Description of UrlRepository
 *
 * @author thorben
 */
class AbstractRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	public function createQuery() {
		$query = parent::createQuery();
		$query->getQuerySettings()->setRespectStoragePage(FALSE)->setRespectSysLanguage(FALSE);
		return $query;
	}

}

?>
