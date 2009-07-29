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
	
	public function provider_test_sanitizing_of_uri(){
		return array(
			array('über/ß', 'ueber/ss'),
			array('foo bar/das das/', 'foo-bar/das-das/'),
			array('foo bar/das<br/>das/', 'foo-bar/dasdas/'),
			array('foobar/das
			
			das/', 'foobar/das-das/'),
			array('foo&bar/', 'foo-bar/'),
			array('Über Fielmann/', 'ueber-fielmann/'),
			array('Service & Beratung/', 'service-beratung/'),
			array('Statistik Informiert ... 11/94/', 'statistik-informiert-...-11/94/'),
			
		);
	}
	
	/**
	 * General transliteration Tests 
	 * 
	 * @dataProvider provider_test_sanitizing_of_uri
	 * @param unknown_type $utf_8_string
	 * @param unknown_type $transliterated_string
	 */
	public function test_sanitizing_of_uri($utf_uri, $sanitized_uri) {
		$this->assertEquals( $this->test_subject->sanitize_uri($utf_uri), $sanitized_uri );
	}


	public function provider_test_uri_limit_allowed_chars(){
		return array(
			array('foo/bar/' ,'foo/bar/'),
			array('fooä/bar/dasä/' ,'foo/bar/das/'),
			array('A-Za-z0-9-_.~' ,'A-Za-z0-9-_.~'),
			array('Расширенный/поиск', '/'),
			array('fooРасширенныйbar/поискbaz', 'foobar/baz'),
		);
	}
	
	/**
	 * Limit the path to the allowed chars
	 * 
	 * Allowed chars :: A-Za-z0-9 - _ . ~
	 * 
	 * @dataProvider provider_test_uri_limit_allowed_chars
	 * @param unknown_type $uri
	 * @param unknown_type $res
	 * @param unknown_type $error
	 */
	public function test_uri_limit_allowed_chars($uri, $res, $error=''){
		$this->assertEquals( $this->test_subject->uri_limit_allowed_chars($uri), $res,  $error);
	}
	
	//" #   & '               <   > ? @ [ \ ] ^ ` { | } %
	
	public function provider_test_uri_handle_punctuation(){
		return array(
			array('"#&\'<>?@[\\]^`{|}%' ,'-'),
			array('"#&\'<>?@[\\]^`{|}%' ,'-'),
			array('!$()*,=:.;+','!$()*,=:.;+'),
			array('!"#$foo&\'()*+,/bar.;<=>?@[\\]baz^`{|}' ,'!-$foo-()*+,/bar.;-=-baz-'),
			array('statistik informiert ... 11/94/', 'statistik informiert ... 11/94/'),
		);
	}
	
	/**
	 * Limit the path to the allowed chars
	 * 
	 * @dataProvider provider_test_uri_handle_punctuation
	 * @param unknown_type $uri
	 * @param unknown_type $res
	 * @param unknown_type $error
	 */
	public function test_uri_handle_punctuation($uri, $res, $error=''){
		$this->assertEquals( $this->test_subject->uri_handle_punctuation($uri), $res,  $error);
	}
	
	public function provider_test_uri_make_wellformed(){
		return array(
			array('/foo/bar/' ,'foo/bar/'),
			array('foo//bar///baz' ,'foo/bar/baz'),
			array('foo/-foo-bar-/-baz/', 'foo/foo-bar/baz/'),
		);
	}
	
	/**
	 * Limit the path to the allowed chars
	 * 
	 * @dataProvider provider_test_uri_make_wellformed
	 * @param unknown_type $uri
	 * @param unknown_type $res
	 * @param unknown_type $error
	 */
	public function test_uri_make_wellformed($uri, $res, $error=''){
		$this->assertEquals( $this->test_subject->uri_make_wellformed($uri), $res,  $error);
	}
	
}
?>