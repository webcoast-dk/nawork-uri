<?php

namespace Nawork\NaworkUri\Tests\Unit\Utility;


class ConfigurationUtilityTest extends \Tx_Phpunit_TestCase {
	/**
	 * @test
	 */
	public function getConfigurationReaderReturnsConfigurationReaderObject() {
		$this->assertInstanceOf('Nawork\\NaworkUri\\Configuration\\ConfigurationReader', \Nawork\NaworkUri\Utility\ConfigurationUtility::getConfigurationReader());
	}
} 