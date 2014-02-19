<?php

namespace Nawork\NaworkUri\Domain\Model;

/**
 * Description of Url
 *
 * @author thorben
 */
class Domain extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 *
	 * @var string
	 */
	protected $domainname;

	public function getDomainname() {
		return $this->domainname;
	}

}

?>
