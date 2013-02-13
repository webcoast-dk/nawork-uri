<?php
/**
 * Created by JetBrains PhpStorm.
 * User: martin
 * Date: 13.02.13
 * Time: 17:38
 * To change this template use File | Settings | File Templates.
 */
class Tx_Naworkuri_Command_NaworkUriCommandController extends Tx_Extbase_MVC_Controller_CommandController {

	/**
	 * Database compare
	 *
	 * Leave the argument 'actions' empty or use "help" to see the available ones
	 *
	 * @param string $actions List of actions which will be executed
	 * @param bool $verbose show the possible and executed queries plus the errors that occured
	 */
	public function monitorPathesCommand($actions = '', $verbose = FALSE) {
		$this->outputLine("hello world");
		$this->quit();
	}

}
