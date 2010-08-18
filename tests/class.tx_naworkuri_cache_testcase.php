<?php
/***************************************************************
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
***************************************************************/

/**
 * Description of class
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class tx_naworkuri_cache_testcase extends tx_naworkuri_basic_tc {

	protected $transformer;
	protected $cache;
	protected $db;
	protected $configReader;

	public function uniqueReturnsUniquePathProvider() {
		return array(
			array(
				array(
					'id=1'
				),
				'',
				'1/',
			),
			array(
				array(
					'id=2',
				),
				'sub-1/',
				'sub-1-1/'
			),
			array(
				array(
					'id=2',
					'id=2&no_cache=1',
				),
				'sub-1/',
				'sub-1-2/',
			),
		);
	}


	/**
	 * @test
	 * @dataProvider uniqueReturnsUniquePathProvider
	 */
	public function uniqueReturnsUniquePath($preparedUris, $path, $expected) {
		foreach($preparedUris as $uri) {
			$this->transformer->params2uri($uri);
		}
		$this->assertEquals($expected, $this->cache->unique($path,''));
	}
}
?>
