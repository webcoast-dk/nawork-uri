<?php

namespace Nawork\NaworkUri\Configuration;


class TransliterationsConfiguration {
	protected $characters = array();

	public function addCharacter($from, $to) {
		$this->characters[$from] = $to;
	}

	/**
	 * @return array
	 */
	public function getCharacters() {
		return $this->characters;
	}
}
 