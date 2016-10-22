<?php

namespace Nawork\NaworkUri\Tests\Unit\Cache;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Thorben Kapp <thorben@work.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Description of class
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class CacheTest extends \Nawork\NaworkUri\Tests\TestBase {

	public function uniqueReturnsUniquePathProvider() {
		return array(
			array(
				array(
					array(
						'pageUid' => 1,
						'parameters' => array(),
						'path' => ''
					)
				),
				array(
					'pageUid' => 1,
					'parameters' => array('no_cache' => 1),
					'path' => ''
				),
				'1/',
			),
			array(
				array(
					array(
						'pageUid' => 2,
						'parameters' => array(),
						'path' => 'sub-1/'
					)
				),
				array(
					'pageUid' => 2,
					'parameters' => array('no_cache' => 1),
					'path' => 'sub-1/'
				),
				'sub-1-1/'
			),
			array(
				array(
					array(
						'pageUid' => 2,
						'parameters' => array(),
						'path' => 'sub-1/'
					),
					array(
						'pageUid' => 2,
						'parameters' => array('no_cache' => 1),
						'path' => 'sub-1-1/'
					)
				),
				array(
					'pageUid' => 2,
					'parameters' => array('no_cache' => 2),
					'path' => 'sub-1/'
				),
				'sub-1-2/',
			),
			array(
				array(
					array(
						'pageUid' => 11,
						'parameters' => array(),
						'path' => 'sub-3/sub-3-1/'
					)
				),
				array(
					'pageUid' => 14,
					'parameters' => array(),
					'path' => 'sub-3/sub-3-1/'
				),
				'sub-3/sub-3-1-1/'
			),
			array(
				array(
					array(
						'pageUid' => 11,
						'parameters' => array(),
						'path' => 'sub-3/sub-3-1/'
					),
					array(
						'pageUid' => 11,
						'parameters' => array('no_cache' => 1),
						'path' => 'sub-3/sub-3-1-1/'
					)
				),
				array(
					'pageUid' => 14,
					'parameters' => array(),
					'path' => 'sub-3/sub-3-1/'
				),
				'sub-3/sub-3-1-2/'
			),
			array(
				array(
					array(
						'pageUid' => 10,
						'parameters' => array(),
						'path' => 'sub-2/sub-2-4/'
					),
					array(
						'pageUid' => 10,
						'parameters' => array('cHash' => 123),
						'path' => 'sub-2/sub-2-4-1/'
					)
				),
				array(
					'pageUid' => 10,
					'parameters' => array('cHash' => 456),
					'path' => 'sub-2/sub-2-4/'
				),
				'sub-2/sub-2-4-2/'
			)
		);
	}

	/**
	 * @test
	 * @dataProvider uniqueReturnsUniquePathProvider
	 */
	public function uniqueReturnsUniquePath($preparedUris, $test, $expected) {
		foreach ($preparedUris as $uri) {
			$uri = $this->cache->createUrl($uri['pageUid'], 0, 1, $uri['parameters'], $uri['path'], $uri['path']);
		}
		$result = $this->cache->unique($test['pageUid'], 0, $test['path'], $test['parameters'], 1);
		$this->assertEquals($expected, $result);
	}

}

?>
