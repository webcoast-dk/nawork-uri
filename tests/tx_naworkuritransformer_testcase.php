<?php

require_once (t3lib_extMgm::extPath('nawork_uri').'/lib/class.tx_naworkuri_transformer.php');

class tx_naworkuritransformer_testcase extends tx_phpunit_testcase {
	
	protected function setUp() {
		$this->test_subject = new tx_naworkuri_transformer();
		$this->test_subject->loadConfiguration( t3lib_extMgm::extPath('nawork_uri').'/lib/default_UriConf.xml' );
		$this->test_subject->loadRootline( array() );
	}
	
	public function test_test_system() {
		$this->assertEquals(0, 0);
	}
	
	public function test_param_implode_works_basically() {
		$this->assertEquals( 'id=2&L=1&foo[bar]=123', $this->test_subject->implode_params(array('id'=>2,'L'=>1,'foo[bar]'=>123 ) ) );
	}
	
	public function test_param_explode_works_basically() {
		$this->assertEquals( array('id'=>2,'L'=>1,'foo[bar]'=>123) , $this->test_subject->explode_params('id=2&L=1&foo[bar]=123') );
	}

	
	
}
?>