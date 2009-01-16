<?php

require_once (t3lib_extMgm::extPath('nawork_uri').'/lib/class.tx_naworkuri_transformer.php');

class tx_naworkuri_transformer_testcase extends tx_phpunit_testcase {
	
	protected function setUp() {
		$this->test_subject = new tx_naworkuri_transformer(false, true);
	}
	
	/**
	 * Enter description here...
	 *
	 */	
	public function tearDown(){
		unset($this->test_subject);
	}
	
	
	public function test_empty_cache_db() {
		$dbres = $GLOBALS['TYPO3_DB']->sql_query('TRUNCATE TABLE `tx_naworkuri_uri`' );
	 	$this->assertEquals( 1,1);
	}

	public function provider_test_params2uri_predefinedparts_works(){
		return array(
			array(
				array( 
					'number'=>123,
					'predef_uri_value' => 1,
					'no_cache' => 'no_cache',
					'not_encoded_params' => 'not_encoded_value'
				),
				array('predef_uri_value'=>'predef_uri_part','number'=>'number-123') 
			),
		);
	}
	
	/**
	 * Enter description here...
	 *
	 * @dataProvider provider_test_params2uri_predefinedparts_works
	 * 
	 */
	public function test_params2uri_predefinedparts_works($params, $expected_parts, $error=''){
		$encoded_params = array();
		$parts = $this->test_subject->params2uri_predefinedparts(&$params, &$params, &$encoded_params);
		$this->assertEquals(
			$expected_parts ,
			$parts,
			$error
		);
		
	}
	
	public function provider_test_params2uri_valuemaps_works(){
		return array(
			array(
				array('L'=>1,'type'=>50,'not_encoded_params' => 'not_encoded_value'),
				array('L'=>'en','type'=>'text'),
			),
			array(
				array( 'L'=>1,'type'=>0,'not_encoded_params' => 'not_encoded_value'),
				array('L'=>'en'),
			),
		);
	}
	
	/**
	 * General path encoding Tests 
	 * 
	 * @dataProvider provider_test_params2uri_valuemaps_works
	 * @param unknown_type $utf_8_string
	 * @param unknown_type $transliterated_string
	 */
	public function test_params2uri_valuemaps_works($params, $expected_parts, $error=''){
		$encoded_params = array();
		$parts = $this->test_subject->params2uri_valuemaps(&$params, &$params, &$encoded_params);
		
		$this->assertEquals(
			$expected_parts,
			$parts,
			$error
		);
	}
	

	public function provider_test_params2uri_uriparts_works(){
		return array(
			array( 
				array('pages[uid]'=>20,'not_encoded_params' => 'not_encoded_value'),
				array('pages[uid]'=>'bam'),
			),
			array(
				array('pages[uid]'=>32),
				array('pages[uid]'=>'blub'),
			)
		);
	}
	
	/**
	 * General path encoding Tests 
	 * 
	 * @dataProvider provider_test_params2uri_uriparts_works
	 * @param unknown_type $utf_8_string
	 * @param unknown_type $transliterated_string
	 */
	
	public function test_params2uri_uriparts_works($params, $expected_parts, $error=''){
		$encoded_params = array();
		$parts = $this->test_subject->params2uri_uriparts(&$params, &$params, &$encoded_params);
		
		$this->assertEquals(
			$expected_parts,
			$parts,
			$error
		);
	}

	
	public function provider_test_params2uri_pagepath(){
		return array(
			array(array(id=>'20'), 'Kontaktlinsen/bam', 'simple id is converted' ),
			array(array(id=>'29'), 'Brillenmode/hidden page', 'hidden pages are shown' ),
			array(array(id=>'31'), 'Brillenmode/folder/folder content', 'sysfolders are shown in path' ),
			array(array(id=>'35'), 'Brillenmode/blubbbb', 'sysfolders are shown in path' ),
			array(array(id=>'19','L'=>'1'), 'Glasses/foo_en', 'sysfolders are shown in path' ),
			array(array(id=>'foobar'), 'foobar', 'alias id works' ),
			array(array(id=>'foobarbaz'), '', 'unknown alias id works' ),
		);
	}
	
	/**
	 * General path encoding Tests 
	 * 
	 * @dataProvider provider_test_params2uri_pagepath
	 * @param unknown_type $utf_8_string
	 * @param unknown_type $transliterated_string
	 */
	public function test_params2uri_pagepath($params, $path, $error){

		$encoded_params = array();
		$parts = $this->test_subject->params2uri_pagepath(&$params, &$params, &$encoded_params);
		
		$this->assertEquals(
			implode('/',$parts),
			$path,
			$error
		);
		
	}
	
	
	public function provider_test_params2uri(){
		return array(
			array( 'id=5'  , 'ueber-fielmann/'),
			array( 'id=23' , 'ueber-fielmann/die-geschichte-der-brille/' ),
			array( 'id=23' , 'ueber-fielmann/die-geschichte-der-brille/' ),
			array( 'id=20&type=50&L=0' , 'kontaktlinsen/bam/text/'),
			array( 'id=20&type=50&L=0&unknown_param=unknown_value' , 'kontaktlinsen/bam/text/?unknown_param=unknown_value'),
			array( 'id=23&type=99', 'ueber-fielmann/die-geschichte-der-brille/?type=99' ),
			array( 'id=23&type=50', 'ueber-fielmann/die-geschichte-der-brille/text/' ),
			array( 'id=1'  , ''),		
			array( 'id=1&no_cache=1',  '1/'),		
			array( 'no_cache=1&id=1',  '1/'),		
			array( 'id=23&pages[uid]=20&L=0',  'ueber-fielmann/die-geschichte-der-brille/bam/'),		
		);	
	}
	
	/**
	 * General Path encoding tests
	 * 
	 * @dataProvider provider_test_params2uri
	 * @param array $params Parameters to encode
	 * @param string $uri Expected URI
	 */
	public function test_params2uri($params, $uri){
		$this->assertEquals( $this->test_subject->params2uri($params), $uri );
	}
	
	/**
	 * Test data for method test_uri2params
	 *
	 * @return array
	 */
	public function provider_test_uri2params(){
		return array(
		 	array('kontaktlinsen/bam/text/', array('id'=>20, 'type'=>50, "L"=>0 )),
		 	array('kontaktlinsen/bam/text/?unknown_param=unknown_value', array('id'=>20, 'type'=>50, "L"=>0,"unknown_param"=>'unknown_value' )),
		  	array('kontaktlinsen/bam/text/?type=0&L=1', array('id'=>20, 'type'=>0, "L"=>1 )),
		 	array('ueber-fielmann/die-geschichte-der-brille/bam/' , array( 'pages'=> array( 'uid' => 20) , 'id'=>23 , 'L'=> 0 ) ),
		 	array('ueber-fielmann/die-geschichte-der-brille/bam/?pages[foo]=bar' , array( 'pages'=> array( 'uid' => 20, 'foo'=>'bar') , 'id'=>23 , 'L'=> 0 ) ),
		 	array('ueber-fielmann/die-geschichte-der-brille/bam/?pages[uid]=23' , array( 'pages'=> array( 'uid' => 23 ) , 'id'=>23 , 'L'=> 0 ) )
		 	
		 );
	}
	
	/**
	 * General Path encoding tests
	 * 
	 * @dataProvider provider_test_uri2params
	 * @param array $params Parameters to encode
	 * @param string $uri Expected URI
	 */	
	public function test_uri2params($uri, $params, $error='') {
		$this->assertEquals($params, $this->test_subject->uri2params($uri), $error );
	}
}
?>