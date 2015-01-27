<?php

class tx_naworkuri_configreader_testcase extends tx_phpunit_testcase {

	/**
	 *
	 * @var tx_naworkuri_configReader
	 */
	protected $configReader;

	protected function setUp() {
		$this->configReader = t3lib_div::makeInstance('tx_naworkuri_configReader', 'typo3conf/ext/nawork_uri/tests/default_UriConf.xml');
	}

	protected function tearDown() {
		unset($this->configReader);
	}

	/**
	 * 
	 */
//	public function getConfigReturnsASimpleXMLElement() {
//		$config = $this->configReader->getConfig();
//		$this->assertType('SimpleXMlElement', $config);
//	}

	/**
	 * 
	 */
//	public function getConfigValueReturnsStringByDefault() {
//		$configValue = $this->configReader->getConfigValue($this->configReader->getConfig()->append);
//		$this->assertType('string', $configValue);
//	}

	/**
	 * 
	 */
//	public function getConfigValueReturnsIntegerOnTypeInteger() {
//		$configValue = $this->configReader->getConfigValue($this->configReader->getConfig()->pagepath->limit, 'element', 'int');
//		$this->assertType('int', $configValue);
//	}

	/**
	 * 
	 */
//	public function getConfigValueReturnsBooleanOnTypeBoolean() {
//		$element = new SimpleXMLElement('<root></root>');
//		$element->addChild('test','1');
//		$configValue = $this->configReader->getConfigValue($element, 'element', 'boolean');
//		$this->assertType('boolean', $configValue);
//	}
}
?>
