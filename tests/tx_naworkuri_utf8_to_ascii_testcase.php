<?php

require_once (t3lib_extMgm::extPath('nawork_uri').'/lib/class.tx_naworkuri_utf8_to_ascii.php');

class tx_naworkuri_utf8_to_ascii_testcase extends tx_phpunit_testcase {
	
	protected function setUp() {
		$this->test_subject = new tx_naworkuri_utf8_to_ascii();
	}
	
	public function provider_test_params2uri_valuemaps_works(){
		return array(
			array('foo', 'foo'),
			array('éä', 'ea'),
		);
	}
	
	/**
	 * General path encoding Tests 
	 * 
	 * @dataProvider provider_test_params2uri_valuemaps_works
	 * @param unknown_type $utf_8_string
	 * @param unknown_type $transliterated_string
	 */
	public function test_utf8_to_ascii($utf8, $asii){
		$this->assertEquals( $asii, $this->test_subject->utf8_to_ascii($utf8), '"'.$utf8.'" should be conveted to: "'.$asii.'"' );
	}
	

}

?>