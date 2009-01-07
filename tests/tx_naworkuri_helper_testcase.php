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
	
	/**
	 * Enter description here...
	 *
	 */
	public function test_param_implode_works_basically() {
		$this->assertEquals( 'id=2&L=1&foo[bar]=123', $this->test_subject->implode_parameters(array('id'=>2,'L'=>1,'foo[bar]'=>123 ) ) );
	}
	
	/**
	 * Enter description here...
	 *
	 */
	public function test_param_explode_works_basically() {
		$this->assertEquals( array('id'=>2,'L'=>1,'foo'=>array('bar'=>123 ) ) , $this->test_subject->explode_parameters('id=2&L=1&foo[bar]=123') );
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