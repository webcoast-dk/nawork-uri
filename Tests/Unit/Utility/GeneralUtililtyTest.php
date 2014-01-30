<?php

namespace Nawork\NaworkUri\Tests\Unit\Utility;

class GeneralUtilityTest extends \Nawork\NaworkUri\Tests\TestBase {

	public function provider_test_param_implode() {
		return array(
			array(array('foo[bar]' => 123, 'id' => 2, 'L' => 1), 'foo[bar]=123&id=2&L=1'),
			array(array('id' => 2, 'foo[bar]' => 123, 'L' => 1), 'foo[bar]=123&id=2&L=1'),
			array(array('foo' => bar, 'baz[]' => array('bla', 'fasel')), 'baz[]=bla&baz[]=fasel&foo=bar'),
		);
	}

	/**
	 * Enter description here...
	 *
	 * @dataProvider provider_test_param_implode
	 * @test
	 */
	public function test_param_implode($array, $imploded_array, $error = '') {
		$result = \Nawork\NaworkUri\Utility\GeneralUtility::implode_parameters($array, FALSE);
		$this->assertEquals($imploded_array, $result);
	}

	/**
	 * Test-Data for test_param_explode
	 *
	 * @return array
	 */
	public function provider_test_param_explode() {
		return array(
			array('L=1&foo[bar]=123&id=2', array('foo[bar]' => 123, 'id' => 2, 'L' => 1)),
			array('foo[bar]=123&L=1&id=2', array('foo[bar]' => 123, 'id' => 2, 'L' => 1)),
			array('foo=bar&baz[]=bla&baz[]=fasel', array('foo' => 'bar', 'baz[]' => array('bla', 'fasel'))),
		);
	}

	/**
	 * Enter description here...
	 *
	 * @dataProvider provider_test_param_explode
	 */
	public function test_param_explode($path, $exploded_array, $error = '') {
		$this->assertEquals(
				$exploded_array, \Nawork\NaworkUri\Utility\GeneralUtility::explode_parameters($path), $error
		);
	}

	public function provider_test_sanitizing_of_uri() {
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
		$result = \Nawork\NaworkUri\Utility\GeneralUtility::sanitize_uri($utf_uri);
		$this->assertEquals($result, $sanitized_uri);
	}

	public function provider_test_uri_limit_allowed_chars() {
		return array(
			array('foo/bar/', 'foo/bar/'),
			array('fooä/bar/dasä/', 'foo/bar/das/'),
			array('A-Za-z0-9-_.~', 'A-Za-z0-9-_.~'),
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
	public function test_uri_limit_allowed_chars($uri, $res, $error = '') {
		$this->assertEquals(\Nawork\NaworkUri\Utility\GeneralUtility::uri_limit_allowed_chars($uri), $res, $error);
	}

	//" #   & '               <   > ? @ [ \ ] ^ ` { | } %

	public function provider_test_uri_handle_punctuation() {
		return array(
			array('"#&\'<>?@[\\]^`{|}%', '-'),
			array('"#&\'<>?@[\\]^`{|}%', '-'),
			array('!$()*,=:.;+', '-$()*,=:.;-'),
			array('!"#$foo&\'()*+,/bar.;<=>?@[\\]baz^`{|}', '-$foo-()*-,/bar.;-=-baz-'),
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
	public function test_uri_handle_punctuation($uri, $res, $error = '') {
		$this->assertEquals(\Nawork\NaworkUri\Utility\GeneralUtility::uri_handle_punctuation($uri), $res, $error);
	}

	public function provider_test_uri_make_wellformed() {
		return array(
			array('/foo/bar/', 'foo/bar/'),
			array('foo//bar///baz', 'foo/bar/baz'),
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
	public function test_uri_make_wellformed($uri, $res, $error = '') {
		$this->assertEquals(\Nawork\NaworkUri\Utility\GeneralUtility::uri_make_wellformed($uri), $res, $error);
	}

	/**
	 * @test
	 * @dataProvider getCurrentDomainProvider
	 */
	public function getCurrentDomain($domainToSet, $domainUidToRetreive) {
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nawork_uri']);
		$extConf['MULTIDOMAIN'] = 1;
		$configReader = \Nawork\NaworkUri\Utility\ConfigurationUtility::getConfigurationReader();
		$configReader->setExtConfig($extConf);
		$_SERVER['HTTP_HOST'] = $domainToSet;
		$domainUid = \Nawork\NaworkUri\Utility\GeneralUtility::getCurrentDomain();

		$this->assertEquals($domainUidToRetreive, $domainUid);
	}

	public function getCurrentDomainProvider() {
		return array(
			'master domain' => array('test.test', 1),
			'one level non master' => array('test.local', 1),
			'two levels non master' => array('test.foo', 1),
			'non existing domain' => array('test.doesnotexit', 1)
		);
	}

}

?>