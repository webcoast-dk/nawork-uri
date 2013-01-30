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
class Tx_NaworkUri_Domain_Repository_DomainRepository extends Tx_NaworkUri_Domain_Repository_AbstractRepository {

	public function findAll() {
		$query = $this->createQuery();
		return $query->matching($query->equals('tx_naworkuri_masterdomain', ''))->execute();
	}

}

?>
