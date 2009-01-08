<?php

require_once (t3lib_extMgm::extPath('nawork_uri').'/lib/class.tx_naworkuri_helper.php');

class tx_naworkuri_helper_testcase extends tx_phpunit_testcase {
	
	protected function setUp() {
		$this->test_subject = new tx_naworkuri_helper();
	}
	
	/**
	 * Enter description here...
	 *
	 */	
	public function tearDown(){
		unset($this->test_subject);
	}
	
	public function provider_test_param_implode(){
		return array(
			array(array('foo[bar]'=>123,'id'=>2,'L'=>1 ), 'L=1&foo[bar]=123&id=2' ),
			array(array('id'=>2,'foo[bar]'=>123,'L'=>1 ), 'L=1&foo[bar]=123&id=2' ),
		);
	}
	
	/**
	 * Enter description here...
	 * 
	 * @dataProvider provider_test_param_implode
	 */
	public function test_param_implode($array, $imploded_array, $error='') {
		$this->assertEquals(
			$imploded_array,
			$this->test_subject->implode_parameters( $array),
			$error
		);
	}
	
	/**
	 * Test-Data for test_param_explode
	 *
	 * @return array
	 */
	public function provider_test_param_explode(){
		return array(
			array('L=1&foo[bar]=123&id=2', array('foo[bar]'=>123,'id'=>2,'L'=>1 ) ),
			array('foo[bar]=123&L=1&id=2', array('foo[bar]'=>123,'id'=>2,'L'=>1 ) ),
		);
	}
	 
	/**
	 * Enter description here...
	 * 
	 * @dataProvider provider_test_param_explode
	 */
	public function test_param_explode($path, $exploded_array, $error= '') {
		$this->assertEquals( 
			$exploded_array , 
			$this->test_subject->explode_parameters($path),
			$error
		 );
	}

	/**
	 * General transliteration Tests 
	 * 
	 * @dataProvider provider_test_sanitizing_of_uri
	 * @param unknown_type $utf_8_string
	 * @param unknown_type $transliterated_string
	 */
	public function test_sanitizing_of_uri($utf_8_string, $transliterated_string) {
		$this->assertEquals( $this->test_subject->sanitize_uri($utf_8_string), $transliterated_string );
	}

	public function provider_test_sanitizing_of_uri(){
		return array(
			array('über/ß', 'ueber/ss'),
			array('foo bar/das das/', 'foo-bar/das-das/'),
			array('foo bar/das<br/>das/', 'foo-bar/dasdas/'),
			array('foobar/das
			
			das/', 'foobar/das-das/'),
			array('foo&bar/', 'foo-bar/'),
			array('Über Fielmann/', 'ueber-fielmann/'),
		);
	}
}
?>