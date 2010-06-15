<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2002 René Fritz (r.fritz@colorcube.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
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
 * Module 'AWStats' for the 'cc_awstats' extension.
 *
 * Redirect and configuration script for the third party module, AWstats
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 */



	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
include ('conf.php');
include ($BACK_PATH.'init.php');
include ($BACK_PATH.'template.php');

	// needed here because locallang.php used some of them
$MCONF['awstatsFullDir']=t3lib_extMgm::extPath('cc_awstats').'awstats/';
$MCONF['awstatsSubDir']='../awstats/';
$MCONF['awstatsScript']='awstats.pl';

// check if logfile path is relative or not
if (substr($TYPO3_CONF_VARS['FE']['logfile_dir'],0,1) == '/') {
	$MCONF['logfile_dir'] = $TYPO3_CONF_VARS['FE']['logfile_dir'];
} else {
	$MCONF['logfile_dir'] = str_replace ('//', '/', PATH_site.	$TYPO3_CONF_VARS['FE']['logfile_dir']);
}
$MCONF['awstats_data_dir'] = $MCONF['logfile_dir'] .'.awstats-data/';
$MCONF['awstats_conf'] = $MCONF['awstats_data_dir'].'awstats-module.conf';

if (ini_get('safe_mode')) {
	die ("'safe_mode' is enabled in PHP what makes it impossible for this script to run AWstats.<br />You may install the pure AWstats script by yourself.<br />Get it from http://awstats.sourceforge.net");
}

include ('locallang.php');
include_once(PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

class tx_ccawstats_module1 extends t3lib_SCbase {
	var $pageinfo;


	/**
	 * Main function of the module. Write the content to $this->content
	 */
	function main()	{
		global $AB,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		
		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		
		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
	
				// Draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			/* unused - is for web module ?!
			$this->doc->postCode='
				<script language="javascript">
					script_ended = 1;
					if (top.theMenu) top.theMenu.recentuid = "'.intval($this->id).'";
				</script>
			';
*/
#			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->php3Lang['labels']['path'].': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);

			$this->content.= $this->doc->startPage($LANG->getLL('title'));
			$this->content.= $this->doc->header($LANG->getLL('title'));
			$this->content.= $this->doc->spacer(5);
#			$this->content.= $this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function'])));
#			$this->content.= $this->doc->divider(5);

			
			// Render content:
			$this->moduleContent();

			
			// ShortCut
#			if ($BE_USER->mayMakeShortcut())	{
#				$this->content.= $this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
#			}
		
			$this->content.= $this->doc->spacer(10);
		} else {
				// If no access or if ID == zero
		
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
		
			$this->content.= $this->doc->startPage($LANG->getLL('title'));
			$this->content.= $this->doc->header($LANG->getLL('title'));
			$this->content.= $this->doc->spacer(5);
			$this->content.= $this->doc->spacer(10);
		}
	}
	function printContent()	{
		global $SOBE;

		$this->content.= $this->doc->middle();
		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
	
	
	function moduleContent()	{
		
		global $LANG, $TBE_TEMPLATE;

		$os = stristr(PHP_OS,'win')&&!stristr(PHP_OS,'darwin')?'WIN':'UNIX';
		
		
			// if the user has selected one logfile we run awstats
		if (t3lib_div::GPvar('config'))	{


				// Set some environment values for awstats.conf:

				// this magic prevents calling the awstats script direktly
			putenv ('TYPO3_MAGIC=1');
			
			
			putenv ('AWS_LANG='.$LANG->lang);


				// this is really bad but we need the data of the name of the logfile additional domains that will not be submitted by awstat.pl
			if (t3lib_div::GPvar('domains')=='') {
				if (@is_file($this->MCONF['awstats_conf'])) {
					$fp = fopen ($this->MCONF['awstats_conf'],'r');
					while (list ($lfile, $extra) = fscanf ($fp, "%s\t%s\n")) {
						$domains = explode (',',$extra);
						if ($domains[0] == t3lib_div::GPvar('config')) {
							putenv ('AWS_DOMAIN='. $domains[0]);
							putenv ('AWS_DOMAINS='. implode(' ',$domains));
							putenv ('AWS_LOGFILE='. $this->MCONF['logfile_dir'].$lfile);
						}
					}
					fclose($fp);
				}	
			} else {
				$domains = explode (' ',t3lib_div::GPvar('domains'));
				putenv ('AWS_DOMAIN='. $domains[0]);
				putenv ('AWS_DOMAINS='. t3lib_div::GPvar('domains'));
				putenv ('AWS_LOGFILE='. $this->MCONF['logfile_dir'].t3lib_div::GPvar('logfile'));
			}

				// check for the existance of the awstats data dir
			if (!@is_dir($this->MCONF['awstats_data_dir'].t3lib_div::GPvar('config').'/')) {
					//create the awstats data dir - what mode is safe here??
				mkdir ($this->MCONF['awstats_data_dir'].t3lib_div::GPvar('config').'/', 0777);
			} else {
				chmod ($this->MCONF['awstats_data_dir'].t3lib_div::GPvar('config').'/', 0777);
			}

				// I hope this works
			putenv ('GATEWAY_INTERFACE=');

			putenv ('AWS_DIR='. $this->MCONF['awstatsSubDir']);
			putenv ('AWS_ICON_DIR='.$this->MCONF['awstatsSubDir'].'icon/');
			putenv ('AWS_CACHE_DIR='. $this->MCONF['awstats_data_dir'].t3lib_div::GPvar('config').'/');
			putenv ('AWS_WRAPPER='. 'index.php');

			putenv ('AWS_CSS=../res/awstats_default.css');

			putenv ('AWS_BGCOLOR='. $TBE_TEMPLATE->bgColor);
			putenv ('AWS_TBT_BGCOLOR='. $TBE_TEMPLATE->bgColor5);
			putenv ('AWS_TB_BGCOLOR='. $TBE_TEMPLATE->bgColor4);
			putenv ('AWS_TB_COLOR='. t3lib_div::modifyHTMLColor($TBE_TEMPLATE->bgColor4,-10,-10,-10));
			putenv ('AWS_TBR_BGCOLOR='. t3lib_div::modifyHTMLColor($TBE_TEMPLATE->bgColor4,+15,+15,+15));

				// building the command line parameters for awstats.pl

			$parameter = '';

			#$parameter = ' -configdir='.urlencode(t3lib_extMgm::extPath('cc_awstats'));
			
			$parameter.= ' -config='.t3lib_div::_GP('config');

			if (t3lib_div::GPvar('output'))    {
				$parameter.= ' -output='.t3lib_div::GPvar('output');
			} else {
				$parameter.= ' -output';
			}

			if (t3lib_div::GPvar('update'))    {
				$parameter.= ' -update='.t3lib_div::GPvar('update');
			}

			if (t3lib_div::GPvar('year'))    {
				$parameter.= ' -year='.t3lib_div::GPvar('year');
			}
			if (t3lib_div::GPvar('month'))    {
				$parameter.= ' -month='.t3lib_div::GPvar('month');
			}

				// exec script
			clearstatcache ();
			if ($os=='UNIX' AND !is_executable ($this->MCONF['awstatsFullDir'].$this->MCONF['awstatsScript'])) {
				if(!chmod ($this->MCONF['awstatsFullDir'].$this->MCONF['awstatsScript'], 0755)) {
					if (!is_executable ($this->MCONF['awstatsFullDir'].$this->MCONF['awstatsScript'])) { // better check twice :-)
						die('<br />Please set the awstats.pl script <b>executable</b> for the webserver!<br /><br />shell command:<br />chmod a+x '.$this->MCONF['awstatsFullDir'].$this->MCONF['awstatsScript'].'<br /><br />or by ftp.');
					}
				} 
			}
#TODO use t3lib_exec			
			if ($os=='UNIX') {
				if (is_executable ('/usr/bin/perl')) {
					$perl = '/usr/bin/perl ';
				} else {
					if (is_executable ('/usr/local/bin/perl')) {
						$perl = '/usr/local/bin/perl ';
					} else {
						die("Perl was not found at '/usr/local/bin/perl'. The awstats.pl script can't be executed!");
					}
					die("Perl was not found at '/usr/bin/perl'. The awstats.pl script can't be executed!");
				}
			} elseif ($checkWindows=false) {
				if (is_file ('/usr/bin/perl')) {
					$perl = '/usr/bin/perl ';
				} else {
					if (is_file ('/usr/local/bin/perl')) {
						$perl = '/usr/local/bin/perl ';
					} else {
						die("Perl was not found at '/usr/local/bin/perl'. The awstats.pl script can't be executed!");
					}
					die("Perl was not found at '/usr/bin/perl'. The awstats.pl script can't be executed!");
				}
			}



			if (!t3lib_div::GPvar(dbg)) {
				passthru($perl.' '.$this->MCONF['awstatsFullDir'].$this->MCONF['awstatsScript'].escapeshellcmd ($parameter), $retval);
			}
			
			if ($retval OR t3lib_div::GPvar(dbg)) {
				echo('<h1>DEBUG OUTPUT</h1>');
				if ($retval) {
					echo('<p>There was something wrong with the call of the AWStats script!<br />This debug information may help to find the reason.</p><br /><br />');
				}
				debug($this->MCONF);

				$env = array();
				$env['AWS_LANG']=getenv ('AWS_LANG');
				$env['AWS_DOMAIN']=getenv ('AWS_DOMAIN');
				$env['AWS_DOMAINS']=getenv ('AWS_DOMAINS');
				$env['AWS_LOGFILE']=getenv ('AWS_LOGFILE');
				$env['AWS_DIR']=getenv ('AWS_DIR');
				$env['AWS_ICON_DIR']=getenv ('AWS_ICON_DIR');
				$env['AWS_CACHE_DIR']=getenv ('AWS_CACHE_DIR');
				$env['AWS_WRAPPER']=getenv ('AWS_WRAPPER');
				debug($env);

				$parameter.= ' -debug=1';
				debug($perl.' '.$this->MCONF['awstatsFullDir'].$this->MCONF['awstatsScript'].escapeshellcmd ($parameter));
				passthru($perl.' '.$this->MCONF['awstatsFullDir'].$this->MCONF['awstatsScript'].escapeshellcmd ($parameter));
				phpinfo();
			}

			die();

		} else {

			define ('LOGF_EXCLUDE', 1);
			define ('LOGF_REGISTERED', 2);
			define ('LOGF_UNREGISTERED', 3);
			define ('LOGF_CHECKED', 4);
			define ('ICON_LOGFILE', '<img src="logfile.gif" width="18" height="16" hspace="2" border="0" align="absmiddle" alt=""/>');

			$logfiles = array();

			$data = t3lib_div::GPvar('data');

				// collect submitted form data
			if (!empty($data[logfiles])) {
				reset($logfiles);
				while (list ($lfile, $extra) = each($data[logfiles])) {
					if ($extra['domains'] == '-') {
						$logfiles[$lfile]['type'] = LOGF_EXCLUDE;
					} elseif ($extra['domains'] == '') {
						$logfiles[$lfile]['type'] = LOGF_UNREGISTERED;
					} else {
						$logfiles[$lfile]['type'] = LOGF_REGISTERED;
						$logfiles[$lfile]['domains'] = explode (',',str_replace (' ', '', trim($extra['domains'])));
					}
				}	
			}
		//debug($logfiles);


				// check for the existance of the logfile folder
			if ($this->MCONF['logfile_dir']=='') {
				$content.= $this->doc->section('',$LANG->getLL('aws_errLogfileDir_conf'));
			} elseif (!@is_dir($this->MCONF['logfile_dir'])) {
				$content.= $this->doc->section('',$LANG->getLL('aws_errLogfileDir'));
			} else {

					// check for the existance of the awstats data dir
				if (!@is_dir($this->MCONF['awstats_data_dir'])) {
						//create the awstats data dir - what mode is safe here??
					if (!@mkdir ($this->MCONF['awstats_data_dir'], 0777)) {
						$content.= $this->doc->section('',$LANG->getLL('aws_errCreateLogfileDir'));
						$this->content.= $content;
						return;
					}
				}
					// get logfile config from config file and merge with submitted data
				if (@is_file($this->MCONF['awstats_conf'])) {

					$fp = fopen ($this->MCONF['awstats_conf'],'r');
					while (list ($lfile, $extra) = fscanf ($fp, "%s\t%s\n")) {
							// we don't want to overwrite just submitted data
						if ($lfile && empty($logfiles[$lfile])) {
							if ($extra == '-') {
								$logfiles[$lfile]['type'] = LOGF_EXCLUDE;
							} elseif (trim($extra) == '') {
								unset($logfiles[$lfile]);
							} else {
								$logfiles[$lfile]['type'] = LOGF_REGISTERED;
								$logfiles[$lfile]['domains'] = explode (',',$extra);
							}
						}
					}
					fclose($fp);
				}	

					// write the merged data if there's some submitted data
				if (t3lib_div::GPvar('logf_save_conf')) {
					@chmod ($this->MCONF['awstats_conf'], 0666);
					$fp = fopen ($this->MCONF['awstats_conf'],'w');
					while (list ($lfile, $extra) = each($logfiles)) {
						if ($logfiles[$lfile]['type'] == LOGF_EXCLUDE) {							
							fputs ($fp, $lfile."\t-\n");
						} elseif ($logfiles[$lfile]['type'] == LOGF_UNREGISTERED) {							
							// nix
						} else {
							fputs ($fp, $lfile."\t".implode(',',$logfiles[$lfile]['domains'])."\n");
						}
					}
					fclose($fp);
					@chmod ($this->MCONF['awstats_conf'], 0666);
				}

		//debug($logfiles);

					// get logfiles
				$d = dir($this->MCONF['logfile_dir']);
				while($entry=$d->read()) {
					if (@is_file($this->MCONF['logfile_dir'].$entry) && (preg_match("/.*log.*\.txt/i", $entry) OR preg_match("/.*\.log/i", $entry))) {
						if (empty($logfiles[$entry])) {
							$logfiles[$entry]['type'] = LOGF_UNREGISTERED;
						} elseif ($logfiles[$entry]['type']==LOGF_REGISTERED) {
							$logfiles[$entry]['type'] = LOGF_CHECKED;
						}
					}
				}
				$d->close();

					// no logfiles found
				if (!count($logfiles)) {		
					$content.= $this->doc->section('',$LANG->getLL('aws_noLogfilesFound'));
					$this->content.= $content;
					return;
				}


					// collect logfiles for display
				$theCodeChecked='';
				$theCodeUnreg='';
				$theCodeEdit='';
				reset($logfiles);
				while (list ($lfile, $extra) = each($logfiles)) {
					if ($logfiles[$lfile]['type'] == LOGF_CHECKED) {

						if (t3lib_div::GPvar('logf_clear_cache')) {
							$path=$this->MCONF['awstats_data_dir'].$logfiles[$lfile]['domains'][0].'/';

							$dir     = dir($path);
							while ($item_name = $dir->read()) {
								$file = $path.$item_name;
								if (is_file($file)  && preg_match("/.*\.txt/i", $file)) {
									#debug($file);
									#echo '<br />';
									unlink($file);
								}
							}
							$dir->close();
						}

						$url = 'index.php?config='.urlencode($logfiles[$lfile]['domains'][0]);
						if (t3lib_div::GPvar(dbg)) {
							$url.= '&dbg=1';
						}
						$url.= '&logfile='.urlencode($lfile).'&domains='.urlencode(implode(' ',$logfiles[$lfile]['domains']));
						
						$theCodeChecked.= '<a href="'.htmlspecialchars($url).'">'.ICON_LOGFILE.$lfile.'</a><br />';
					}
					if ($logfiles[$lfile]['type'] == LOGF_UNREGISTERED) {
						$theCodeUnreg.= '<tr><td>'.ICON_LOGFILE.$lfile.'&nbsp;</td>';
						$theCodeUnreg.= '<td><input type="text" name="data[logfiles]['.htmlspecialchars($lfile).'][domains]"'.$this->doc->formWidth(35).'>';
					} else {
						$theCodeEdit.= '<tr><td>'.ICON_LOGFILE.$lfile.'&nbsp;</td>';
						if ($logfiles[$lfile]['type'] == LOGF_EXCLUDE) {
							$theCodeEdit.= '<td><input type="text" value="-" name="data[logfiles]['.htmlspecialchars($lfile).'][domains]"'.$this->doc->formWidth(35).'>';
						} else {
							$theCodeEdit.= '<td><input type="text" value="'.implode(',',$logfiles[$lfile]['domains']).'" name="data[logfiles]['.htmlspecialchars($lfile).'][domains]"'.$this->doc->formWidth(35).'>';
						}
					}
				}

					// output logfiles for selection
				if ($theCodeChecked) {
					if (!t3lib_div::GPvar('logf_edit_conf')) {
						$theCodeChecked.= '<br /><br /><input type="submit" name="logf_edit_conf" value="'.$LANG->getLL('aws_btnEditConf').'"><br />';
					}
					$content.= $this->doc->section($LANG->getLL('aws_hdrSelectLogfile'),'<br />'.$theCodeChecked.'<br />',0,1);
				}


					// output logfiles for configuration
				if ($theCodeUnreg) {
					$theCode=$LANG->getLL('aws_msg1ConfLogfile').'<br /><br />';
					$theCode.= '<table border="0" cellspacing="0" cellpadding="0">';
					$theCode.= $theCodeUnreg;
					$theCode.= '<tr><td colspan="2"><br /><input type="submit" name="logf_save_conf" value="'.$LANG->getLL('aws_btnSaveConf').'"></td></tr></table>';
					$theCode.= '<br /><br />'.$LANG->getLL('aws_msg2ConfLogfile');
					$content.= $this->doc->section($LANG->getLL('aws_hdrConfLogfile'),'<br />'.$theCode.'<br />',0,1);
				}

					// edit logfiles conf
				if (t3lib_div::GPvar('logf_edit_conf') && $theCodeEdit) {
					$theCode='<table border="0" cellspacing="0" cellpadding="0">';
					$theCode.= $theCodeEdit;
					$theCode.= '<tr><td colspan="2"><br /><input type="submit" name="logf_save_conf" value="'.$LANG->getLL('aws_btnSaveConf').'"></td></tr></table>';
					if ($theCodeUnreg=='') {
						$theCode.= '<br /><br />'.$LANG->getLL('aws_msg2ConfLogfile');
					}
					$content.= $this->doc->section($LANG->getLL('aws_hdrEditLogfile'),'<br />'.$theCode.'<br />',0,1);
				}


					// button to delete cache files
				if ($theCodeChecked) {
					$content.= $this->doc->spacer(15);
					if (t3lib_div::GPvar('logf_clear_cache')) {
						$theCode=''.$LANG->getLL('aws_cacheCleared').'';
					} else {
						$theCode='<input type="submit" name="logf_clear_cache" value="'.$LANG->getLL('aws_btnClearCache').'"><br /><br />';
						$theCode.= ''.$LANG->getLL('aws_descrClearCache').'';
					}
					$content.= $this->doc->section($LANG->getLL('aws_hdrClearCache'),'<br />'.$theCode.'<br />',0,1);
				}

			}

				// Help text:
			if (!t3lib_div::GPvar('config') && $GLOBALS['BE_USER']->uc['helpText'])	{
				$content.= $this->doc->divider(10);
				$content.= $this->doc->section('','<br /><br />'.'<img src="'.$GLOBALS['BACK_PATH'].'gfx/helpbubble.gif" width="14" height="14" hspace="2" align="top" alt="" />'.$LANG->getLL('aws_helpText'));
			}

#			$content.= $this->doc->section('','<br /><br /><br /><font face="sans-serif" size="2" color="#999999">V 0.4 | for feedback contact <a href="mailto:r.fritz@colorcube.de"><font face="sans-serif" size="2" color="#999999">r.fritz@colorcube.de</a>');

			$this->content.= $content;

		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cc_awstats/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cc_awstats/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_ccawstats_module1');
$SOBE->init();

// Include files?
reset($SOBE->include_once);	
while(list(,$INC_FILE)=each($SOBE->include_once))	{include_once($INC_FILE);}

$SOBE->main();
$SOBE->printContent();

?>
