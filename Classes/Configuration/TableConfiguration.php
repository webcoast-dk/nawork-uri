<?php

namespace Nawork\NaworkUri\Configuration;

/**
 * Description of TableConfiguration
 *
 * @author Thorben Kapp <thorben@work.de>
 */
final class TableConfiguration implements \TYPO3\CMS\Core\SingletonInterface {

	protected $urlTable = 'tx_naworkuri_uri';
	protected $pageTable = 'pages';
	protected $domainTable = 'sys_domain';

	public function getUrlTable() {
		return $this->urlTable;
	}

	public function getPageTable() {
		return $this->pageTable;
	}

	public function getDomainTable() {
		return $this->domainTable;
	}

	public function setUrlTable($urlTable) {
		if (!empty($urlTable)) {
			$this->urlTable = $urlTable;
		}
	}

	public function setPageTable($pageTable) {
		if (!empty($pageTable)) {
			$this->pageTable = $pageTable;
		}
	}

	public function setDomainTable($domainTable) {
		if (!empty($domainTable)) {
			$this->domainTable = $domainTable;
		}
	}

}

?>