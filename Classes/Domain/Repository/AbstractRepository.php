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
class Tx_NaworkUri_Domain_Repository_AbstractRepository extends Tx_Extbase_Persistence_Repository {

	public function createQuery() {
		$query = parent::createQuery();
		$query->getQuerySettings()->setRespectStoragePage(FALSE)->setRespectSysLanguage(FALSE);
		return $query;
	}

}

?>
