<?php

require_once (t3lib_extMgm::extPath('nawork_uri').'/lib/class.tx_naworkuri_transformer.php');

class tx_naworkuritransformer_testcase extends tx_phpunit_testcase {
	
	protected function setUp() {
		$this->test_subject = new tx_naworkuri_transformer();
	}
		
	public function tearDown(){
		unset($this->test_subject);
	}
	
	public function test_param_implode_works_basically() {
		$this->assertEquals( 'id=2&L=1&foo[bar]=123', $this->test_subject->implode_parameters(array('id'=>2,'L'=>1,'foo[bar]'=>123 ) ) );
	}
	
	public function test_param_explode_works_basically() {
		$this->assertEquals( array('id'=>2,'L'=>1,'foo[bar]'=>123) , $this->test_subject->explode_parameters('id=2&L=1&foo[bar]=123') );
	}
	
	public function test_uri2params_works_basically() {
		$this->assertEquals( array('id'=>20, 'type'=>50, "L"=>0 ), $this->test_subject->uri2params('kontaktlinsen/bam/text/') );
	}
	
	public function test_uri2params_works_ignores_extra_params() {
		$this->assertEquals( array('id'=>20, 'type'=>50, "L"=>0 ), $this->test_subject->uri2params('kontaktlinsen/bam/text/?unknown_param=unknown_value') );
	}
		
	public function test_params2uri_predefinedparts_works(){
		$params = array(
			'number'=>123,
			'predef_uri_value' => 1,
			'type' => 0,
			'no_cache' => 'no_cache',
			'not_encoded_params' => 'not_encoded_value'
		);
		
		$encoded_params = array();
		$parts = $this->test_subject->params2uri_predefinedparts(&$params, &$encoded_params);
		
			// check unencoded_params
		$this->assertEquals(
			array('not_encoded_params' => 'not_encoded_value'),
			$params,
			'noenc'
		);
			
			// check encoded_params
		$this->assertEquals(
			array(
				'number'=>123,
				'predef_uri_value' => 1,
				'type' => 0,
				'no_cache' => 'no_cache'
			),
			$encoded_params,
			'enc'
		);
			
			// check path
		$this->assertEquals( array('predef_uri_part','number-123') ,$parts);
		
	}
	
	public function test_params2uri_valuemaps_works(){
		$params = array( 
			'L'=>1,
			'type'=>99,
			'not_encoded_params' => 'not_encoded_value'
		);
		
		$encoded_params = array();
		$parts = $this->test_subject->params2uri_valuemaps(&$params, &$encoded_params);
		
		$this->assertEquals(
			array('not_encoded_params' => 'not_encoded_value'),
			$params 
		);
		
		$this->assertEquals(
			array('L'=>1, 'type'=>99),
			$encoded_params 
		);
		
		$this->assertEquals(
			array('en','text'),
			$parts 
		);
	}
	
	public function test_params2uri_uriparts_works(){
		$params = array( 
			'pages[uid]'=>20,
			'not_encoded_params' => 'not_encoded_value'
		);
		
		$encoded_params = array();
		$parts = $this->test_subject->params2uri_uriparts(&$params, &$encoded_params);
		
		$this->assertEquals(
			array('not_encoded_params' => 'not_encoded_value'),
			$params,
			"not encoded params are wrong"
		);
		
		$this->assertEquals(
			array('pages[uid]'=>20),
			$encoded_params,
			'encoded params wrong'
		);
		
		$this->assertEquals(
			array('bam'),
			$parts,
			'created path is wrong'
		);
	}
	
	public function test_params2uri_pagepath_works(){
			$params = array( 
			'id'=>20,
			'not_encoded_params' => 'not_encoded_value'
		);
		
		$encoded_params = array();
		$parts = $this->test_subject->params2uri_pagepath(&$params, &$encoded_params);
		
		$this->assertEquals(
			array('not_encoded_params' => 'not_encoded_value'),
			$params,
			"not encoded params are wrong"
		);
		
		$this->assertEquals(
			array('id'=>20),
			$encoded_params,
			'encoded params wrong'
		);
		
		$this->assertEquals(
			array('Kontaktlinsen','bam'),
			$parts,
			'created path is wrong'
		);
	}
	
	public function test_params2uri_works_basically() {
		$this->assertEquals( 'Kontaktlinsen/bam/text/', $this->test_subject->params2uri( array('id'=>20, 'type'=>99, "L"=>0 ) ) );
	}
	
	public function test_params2uri_works_with_unknown_parameters() {
		$this->assertEquals( 'Kontaktlinsen/bam/text/?unknown_param=unknown_value', $this->test_subject->params2uri( array('id'=>20, 'type'=>99, "L"=>0 , 'unknown_param'=>'unknown_value') ) );
	}

	
}
?>