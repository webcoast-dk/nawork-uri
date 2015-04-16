<?php

namespace Nawork\NaworkUri\Domain\Model;

/**
 * Description of Url
 *
 * @author thorben
 */
class Language extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

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
		if(file_exists(PATH_site.'typo3/gfx/flags/'.$this->flag.'.gif')) {
			return $this->flag;
		}
		return NULL;
	}

}

?>