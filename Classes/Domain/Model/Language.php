<?php

namespace Nawork\NaworkUri\Domain\Model;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Description of Url
 *
 * @author thorben
 */
class Language extends AbstractEntity {

	/**
	 *
	 * @var string
	 */
	protected $title;
	/**
	 *
	 * @var string
	 */
	protected $flag;

	public function getTitle() {
		return $this->title;
	}

	public function getFlag() {
        return $this->flag;
	}

}
