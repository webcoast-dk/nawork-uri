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

}

?>
