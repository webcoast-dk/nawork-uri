<?php

namespace Nawork\NaworkUri\Domain\Model;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Description of Url
 *
 * @author thorben
 */
class Domain extends AbstractEntity {

	/**
	 *
	 * @var string
	 */
	protected $domainname;

	public function getDomainname() {
		return $this->domainname;
	}

}
