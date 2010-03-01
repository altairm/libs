<?php
defined('is_running') or die('Not an entry point...');

if( !defined('gpdebug') ){
	define('gpdebug',true);
}
error_reporting(E_ALL);
global $langmessage;

includeFile('install/install_tools.php');
includeFile('admin/admin_tools.php');
includeFile('tool/ftp.php');

?>

<html>
<head>
<title>gpEasy Installation</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />

<?php

	//echo '<script src="'.common::getDir('/include/js/jquery.1.3.2.js').'"></script>';
	//echo '<script type="text/javascript" src="'. common::getDir('/include/js/admin.js') .'"></script>';
	//echo '<script type="text/javascript" src="'. common::getDir('/include/install/install.js') .'"></script>';
	
?>

<style>

body{margin:1em 5em;}
table {border-top: 1px solid #CCC; border-left: 1px solid #CCC;}
td, th {border-right: 1px solid #CCC; border-bottom: 1px solid #CCC; padding: 5px 27px;text-align:left;font-size:14px;vertical-align:top;}
th{background-color:#ccc;white-space:nowrap;font-weight:normal;}

h1{
	float:left;
}
.wrapper{
	margin:0 auto;
}
.lang_select{
	text-align:right;
}
.lang_select select{
	font-size:130%;
	padding:7px 9px;
}
.lang_select option{
}

.sm{
	font-size:smaller;
}
</style>

</head>
<body>
<table class="wrapper" cellspacing="0" ><tr><td>

<?php



//language preferences
	global $languages,$install_language;
	$install_language = 'en';

	$languages = array();
	$languages['ar'] = 'العربية';
	$languages['da'] = 'Dansk';
	$languages['de'] = 'Deutsch';
	$languages['en'] = 'English';
	$languages['es'] = 'Español';
	$languages['fr'] = 'Français';
	$languages['gl'] = 'Galego';
	$languages['hu'] = 'Magyar';
	//$languages['jp'] = '日本語';
	$languages['it'] = 'Italiano';
	$languages['nl'] = 'Nederlandse';
	$languages['pl'] = 'Polski';
	$languages['pt'] = 'Português';


	if( isset($_GET['lang']) && isset($languages[$_GET['lang']]) ){
		$install_language = $_GET['lang'];
		
	}elseif( isset($_COOKIE['lang']) && isset($languages[$_COOKIE['lang']]) ){
		$install_language = $_COOKIE['lang'];
	}
	setcookie('lang',$install_language);
	
	common::GetLangFile('install.php',$install_language);

//Install Control

echo '<h1>'.$langmessage['Installation'].'</h1>';


$_POST += array('cmd'=>'');
$installed = false;
switch($_POST['cmd']){
	
	case 'Install_Safe':
		$installed = Install_Safe();
	break;
	
	case 'Continue':
		FTP_Prepare();
	break;
	case 'Install':
		$installed = Install_Normal();
	break;
}
	
	

if( !$installed ){
	LanguageForm();
	CheckFolders();
}else{
	Installed();
}


echo '</td></tr></table>';
echo '</body></html>';


//Install Functions


	function LanguageForm(){
		global $languages, $install_language;
		
		echo '<div class="lang_select">';
		echo '<form method="get">';
		echo '<select name="lang" onchange="this.form.submit()">';
		foreach($languages as $lang => $label){
			if( $lang === $install_language ){
				echo '<option value="'.$lang.'" selected="selected">';
			}else{
				echo '<option value="'.$lang.'">';
			}
			echo $lang.' - '.$label;
			echo '</option>';
		}

		echo '</select>';
		echo '<div class="sm">';
		echo '<a href="http://ptrans.wikyblog.com/pt/gpEasy" target="_blank">Help translate gpEasy</a>';
		echo '</div>';

		echo '</form>';
		echo '</div>';
	}		
		


	function CheckFolders(){
		global $ok,$langmessage;
		
		$ok = true;
		
		$folders = array();
		$folders[] = 'data';
		
		echo '<h2>'.$langmessage['Checking_server'].'...</h2>';
		echo '<table cellpadding="5" cellspacing="0">';
		echo '<tr>';
		echo '<th>'.$langmessage['Checking'].'...</th>';
		echo '<th>'.$langmessage['Status'].'</th>';
		echo '<th>'.$langmessage['Current_Value'].'</th>';
		echo '<th>'.$langmessage['Expected_Value'].'</th>';
		echo '</tr>';
		
		foreach($folders as $folder){
			CheckFolder($folder);
		}
		
		//Check PHP Version
		echo '<tr>';
			echo '<td>';
			echo $langmessage['PHP_Version'];
			echo '</td>';
			if( !function_exists('version_compare') ){
				echo '<td style="color:#FF0000;">'.$langmessage['Failed'].'</td>';
				echo '<td style="color:#FF0000">???</td>';
				$ok = false;
			}elseif( version_compare(phpversion(),"4.1") < 0){
				echo '<td style="color:#FF0000;">'.$langmessage['Failed'].'</td>';
				echo '<td style="color:#FF0000;">'.phpversion().'</td>';
				$ok = false;
			}else{
				echo '<td style="color:#009900;">'.$langmessage['Passed'].'</td>';
				echo '<td style="color:#009900;">'.phpversion().'</td>';
			}
			echo '<td>4.1+</td>';
			echo '</tr>';
			
		
		/**
		 * PATH_INFO Check From MediaWiki
		 * 
		 * 
		 * Whether to support URLs like index.php/Page_title These often break when PHP
		 * is set up in CGI mode. PATH_INFO *may* be correct if cgi.fix_pathinfo is set,
		 * but then again it may not; lighttpd converts incoming path data to lowercase
		 * on systems with case-insensitive filesystems, and there have been reports of
		 * problems on Apache as well.
		 *
		 * To be safe we'll continue to keep it off by default.
		 *
		 * Override this to false if $_SERVER['PATH_INFO'] contains unexpectedly
		 * incorrect garbage, or to true if it is really correct.
		 *
		 * The default $wgArticlePath will be set based on this value at runtime, but if
		 * you have customized it, having this incorrectly set to true can cause
		 * redirect loops when "pretty URLs" are used.
		 */

			
		//make sure $_SERVER['SCRIPT_NAME'] is set
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://www.php.net/manual/reserved.variables.server.php" target="_blank">';
			echo 'SCRIPT_NAME';
			echo '</a>';
			echo '</td>';
			if( isset($_SERVER['SCRIPT_NAME']) || (GETENV('SCRIPT_NAME') !== FALSE) ){
				echo '<td style="color:#009900;">'.$langmessage['Passed'].'</td>';
				echo '<td style="color:#009900;">'.$langmessage['Set'].'</td>';
			}else{
				echo '<td style="color:#FF0000;">'.$langmessage['Failed'].'</td>';
				echo '<td style="color:#FF0000;">'.$langmessage['Not_Set'].'</td>';
				$ok = false;
			}
			echo '<td>'.$langmessage['Set'].'</td>';
			echo '</tr>';
			
			
			
		//Check Safe Mode
		$checkValue = ini_get('safe_mode');
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/features.safe-mode.php" target="_blank">';
			echo 'Safe Mode';
			echo '</a>';
			echo '</td>';
			if( $checkValue ){
				echo '<td style="color:orange;">'.$langmessage['See_Below'].'</td>';
				echo '<td style="color:orange">'.$langmessage['On'].'</td>';
				$ok = false;
			}else{
				echo '<td style="color:#009900;">'.$langmessage['Passed'].'</td>';
				echo '<td style="color:#009900;">'.$langmessage['Off'].'</td>';
			}
			echo '<td>'.$langmessage['Off'].'</td>';
			echo '</tr>';
			
		
		//Check register_globals
		$checkValue = ini_get('register_globals');
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/security.globals.php" target="_blank">';
			echo 'Register Globals';
			echo '</a>';
			echo '</td>';
			if( $checkValue ){
				echo '<td style="color:orange">'.$langmessage['Passed'].'</td>';
				echo '<td style="color:orange">'.$langmessage['On'].'</td>';
			}else{
				echo '<td style="color:#009900;">'.$langmessage['Passed'].'</td>';
				echo '<td style="color:#009900;">'.$langmessage['Off'].'</td>';
			}
			echo '<td>'.$langmessage['Off'].'</td>';
			echo '</tr>';
			
		//Check ini_get( 'magic_quotes_sybase' )
		$checkValue = ini_get('magic_quotes_sybase');
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/security.magicquotes.disabling.php" target="_blank">';
			echo 'Magic Quotes Sybase';
			echo '</a>';
			echo '</td>';
			if( $checkValue ){
				echo '<td style="color:#FF0000;">'.$langmessage['Failed'].'</td>';
				echo '<td style="color:#FF0000;">'.$langmessage['On'].'</td>';
				$ok = false;
			}else{
				echo '<td style="color:#009900;">'.$langmessage['Passed'].'</td>';
				echo '<td style="color:#009900;">'.$langmessage['Off'].'</td>';
			}
			echo '<td>'.$langmessage['Off'].'</td>';
			echo '</tr>';
			
		//magic_quotes_runtime
		$checkValue = ini_get('magic_quotes_runtime');
		echo '<tr>';
			echo '<td>';
			echo '<a href="http://php.net/manual/security.magicquotes.disabling.php" target="_blank">';
			echo 'Magic Quotes Runtime';
			echo '</a>';
			echo '</td>';
			if( $checkValue ){
				echo '<td style="color:#FF0000;">'.$langmessage['Failed'].'</td>';
				echo '<td style="color:#FF0000;">'.$langmessage['On'].'</td>';
				$ok = false;
			}else{
				echo '<td style="color:#009900;">'.$langmessage['Passed'].'</td>';
				echo '<td style="color:#009900;">'.$langmessage['Off'].'</td>';
			}
			echo '<td>'.$langmessage['Off'].'</td>';
			echo '</tr>';		
		
		
		echo '</table>';
		echo '<a href="">'.$langmessage['Refresh'].'</a>';
		
		if( $ok ){
			Form_Entry();
			return;
		}
		
		if( ini_get('safe_mode') ){
			Form_SafeMode();
			return;
		}
		Form_Permissions();
		
	}



	function Installed(){
		global $langmessage;
		echo '<h4>'.$langmessage['Installation_Was_Successfull'].'</h4>';
		echo '<ul>';
		echo '<li>';
		echo '<a href="">'.$langmessage['View_your_web_site'].'</a>';
		echo '</li>';
		echo '<li>';
		echo '<a href="index.php/Admin">'.$langmessage['Log_in_and_start_editing'].'</a>';
		echo '</li>';
		echo '</ul>';
	}	

	function Form_Entry() {
		global $langmessage;
		
		echo '<h3>'.$langmessage['User Details'].'</h3>';
		echo '<form action="" method="post">';
		echo '<table cellspacing="0">';
		Install_Tools::Form_UserDetails();
		echo '</table>';
		echo '<p>';
		echo '<input type="hidden" name="cmd" value="Install" />';
		echo '<input type="submit" name="aaa" value="'.$langmessage['Install'].'" />';
		echo '</p>';
		echo '</form>';
	}
	
	function Form_Permissions(){
		global $langmessage;
			
		echo '<div>';
		echo '<h3>'.$langmessage['Changing_File_Permissions'].'</h3>';
		echo '<p>';
		echo $langmessage['REFRESH_AFTER_CHANGE'];
		echo '</p>';
		
		echo '<table cellpadding="5" cellspacing="0">';
		echo '<tr><th>FTP</th>';
		echo '<th>Linux/Unix</th>';
		echo '</tr>';
		echo '<tr><td>';
		
		if( !function_exists('ftp_connect') ){
			echo $langmessage['MOST_FTP_CLIENTS'];
			
		}else{
			echo '<form method="post">';
			echo '<table cellpadding="5" cellspacing="0">';
			Form_FTPDetails();
			echo '<tr>';
				echo '<td align="left">&nbsp;</td><td>';
				echo '<input type="hidden" size="20" name="cmd" value="Continue">';
				echo '<input type="submit" size="20" name="aaa" value="'.$langmessage['Continue'].'">';
				echo '</td>';
				echo '</tr>';
			echo '</table>';
			
			echo '</form>';
			
		}
		
		echo '</td><td>';
		
		echo $langmessage['LINUX_CHMOD'];
		echo '<div style="padding:3px 3px 3px 2em;white-space:nowrap;font-size:smaller;"><tt style="background-color:#f5f5f5;padding:3px;">';
		echo 'chmod 777 "/'.$langmessage['your_install_directory'].'/data"';
		echo '</tt></div>';
		
		echo '</td></tr>';
		echo '</table>';
		echo '</div>';			
		
		
	}
	function Form_SafeMode(){
		global $langmessage;
		
		echo '<h3>'.$langmessage['Safe_Mode_Install'].'</h3>';
		
		if( !function_exists('ftp_connect') ){
			echo '<em>'.$langmessage['Safe_Mode_Unavailable'].'</em>';
			return;
		}
		
		echo '<p>'.$langmessage['FTP_INFORMATION'].'</p>';
		echo '<p> <em>'.$langmessage['Warning'].':</em> '.$langmessage['FTP_WARNING'].'</p>';
		
		echo '<form method="post">';
		echo '<table cellpadding="5" cellspacing="0">';
		Install_Tools::Form_UserDetails();
		Form_FTPDetails(true);
		echo '<tr>';
			echo '<td align="left">&nbsp;</td><td>';
			echo '<input type="hidden" size="20" name="cmd" value="Install_Safe">';
			echo '<input type="submit" size="20" name="aaa" value="'.$langmessage['Install'].'">';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		
		echo '</form>';		
	}	
	
	
	function Form_FTPDetails($required=false){
		global $langmessage;
		$_POST += array('ftp_server'=>gpftp::GetFTPServer(),'ftp_username'=>'');
		
		if( $required ){
			$required = '*';
		}
		echo '<tr>';
			echo '<td align="left"><b>'.$langmessage['FTP_Server'].$required.'</b></td><td>';
			echo '<input style="width:10em" type="text" size="20" name="ftp_server" value="'. $_POST['ftp_server'] .'">';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td align="left"><b>'.$langmessage['FTP_Username'].$required.'</b></td><td>';
			echo '<input style="width:10em" type="text" size="20" name="ftp_username" value="'.  $_POST['ftp_username'] .'">';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td align="left"><b>'.$langmessage['FTP_Password'].$required.'</b></td><td>';
			echo '<input style="width:10em" type="password" size="20" name="ftp_password" value="" />';
			echo '</td>';
			echo '</tr>';
			
	}


		
	
	
	function CheckFolder($folder){
		global $ok,$rootDir,$langmessage;
		
		echo '<tr>';
		
		echo '<td>';
		$folder = $rootDir.'/'.$folder;
		if( strlen($folder) > 23 ){
			$show = '...'.substr($folder,-20);
		}else{
			$show = $folder;
		}
		echo sprintf($langmessage['Permissions_for'],$show);
		echo ' &nbsp; ';
		echo '</td>';
		
		//$folder = $rootDir.'/'.$folder;
		$expected = FileSystem::getExpectedPerms($folder);
		
		if( !is_dir($folder)){
			if(!@mkdir($folder, 0777)) {
				echo '<td style="color:orange">'.$langmessage['See_Below'].'</td>';
				$ok=false;
			}else{
				echo '<td style="color:#009900;">'.$langmessage['Passed'].'</td>';
			}
		}elseif( is_writable($folder) ){
			echo '<td style="color:#009900;">'.$langmessage['Passed'].'</td>';
		}else{
			echo '<td style="color:orange;">'.$langmessage['See_Below'].'</td>';
			$ok=false;
		}
			
		
		if( $current = @substr(decoct(fileperms($folder)), -3) ){
			if( FileSystem::perm_compare($expected,$current) ){
				echo '<td style="color:#009900">';
				echo $current;
			}else{
				echo '<td style="color:orange">';
				echo $current;
			}
		}else{
			echo '<td style="color:orange">';
			echo '???';
		}
		echo '</td>';
		echo '<td>';
		echo $expected;
		echo '</td>';
		echo '</tr>';
	}


	function Install_Normal(){
		global $langmessage,$install_language;

		echo '<h2>'.$langmessage['Installing'].'</h2>';
		echo '<ul>';
		
		$success = Install_Tools::Install_DataFiles( false, $install_language);
		echo '</ul>';
		
		return $success;
	}
	
	
	function Install_Safe(){
		global $config,$langmessage,$install_language;
		
		echo '<h2>'.$langmessage['Installing_in_Safe_Mode'].'</h2>';
		echo '<ul>';
			
		
		$conn_id = $ftp_root = false;
		if( Install_FTPConnection($conn_id,$ftp_root) === false ){
			return false;
		}
		
		//configuration
		$config['useftp'] = true;
		$config['ftp_root'] = $ftp_root;
		$config['ftp_user'] = $_POST['ftp_username'];
		$config['ftp_server'] = $_POST['ftp_server'];
		$config['ftp_pass'] = $_POST['ftp_password'];
		
		$success = Install_Tools::Install_DataFiles(false, $install_language);
		echo '</ul>';
		
		return $success;		
	}
		
		
	
	function FTP_Prepare(){
		global $langmessage;
		
		echo '<h2>'.$langmessage['Using_FTP'].'...</h2>';
		echo '<ul>';
		
		$conn_id = $ftp_root = false;
		if( Install_FTPConnection($conn_id,$ftp_root) === false ){
			return;
		}
		
		//Change Mode of /data
		echo '<li>';
			$ftpData = $ftp_root.'/data';
			$modDir = ftp_site($conn_id, 'CHMOD 0777 '. $ftpData );
			if( !$modDir ){
				echo '<span style="color:#FF0000">';
				echo sprintf($langmessage['Could_Not_'],'<em>CHMOD 0777 '. $ftpData.'</em>');
				//echo 'Could not <em>CHMOD 0777 '. $ftpData.'</em>';
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span style="color:#009900">';
				echo sprintf($langmessage['FTP_PERMISSIONS_CHANGED'],'<em>'.$ftpData.'</em>');
				//echo 'File permissions for <em>'.$ftpData.'</em> changed.';
				echo '</span>';
			}
			echo '</li>';

		
		
		echo '<li>';
				echo '<span style="color:#009900">';
				echo '<b>'.$langmessage['Success_continue_below'].'</b>';
				echo '</span>';
				echo '</li>';
		
		echo '</ul>';
	}
	
	function Install_FTPConnection(&$conn_id,&$ftp_root){
		global $rootDir,$langmessage;
		
		//test for functions
		echo '<li>';
			if( !function_exists('ftp_connect') ){
				echo '<span style="color:#FF0000">';
				echo $langmessage['FTP_UNAVAILABLE'];
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span style="color:#009900">';
				echo $langmessage['FTP_AVAILABLE'];
				echo '</span>';
			}
			echo '</li>';
			
		//Try to connect
		echo '<li>';
			$conn_id = @ftp_connect($_POST['ftp_server'],21,6);
			if( !$conn_id ){
				echo '<span style="color:#FF0000">';
				echo sprintf($langmessage['FAILED_TO_CONNECT'],'<em>'.$_POST['ftp_server'].'</em>');
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span style="color:#009900">';
				echo sprintf($langmessage['CONNECTED_TO'],'<em>'.$_POST['ftp_server'].'</em>');
				//echo 'Connected to <em>'.$_POST['ftp_server'].'</em>';
				echo '</span>';
			}
			echo '</li>';
		
		//Log in
		echo '<li>';
			$login_result = @ftp_login($conn_id, $_POST['ftp_username'], $_POST['ftp_password']);
			if( !$login_result ){
				echo '<span style="color:#FF0000">';
				echo sprintf($langmessage['NOT_LOOGED_IN'],'<em>'.$_POST['ftp_username'].'</em>');
				//echo 'Could not log in user  <em>'.$_POST['ftp_username'].'</em>';
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span style="color:#009900">';
				echo sprintf($langmessage['LOGGED_IN'],'<em>'.$_POST['ftp_username'].'</em>');
				//echo 'User <em>'.$_POST['ftp_username'].'</em> logged in.';
				echo '</span>';
			}
			echo '</li>';
			
		//Get FTP Root
		echo '<li>';
			$ftp_root = gpftp::GetFTPRoot($conn_id,$rootDir);
			if( !$login_result ){
				echo '<span style="color:#FF0000">';
				echo $langmessage['ROOT_DIRECTORY_NOT_FOUND'];
				echo '</span>';
				echo '</li></ul>';
				return false;
			}else{
				echo '<span style="color:#009900">';
				echo sprintf($langmessage['FTP_ROOT'],'<em>'.$ftp_root.'</em>');
				//echo 'FTP Root found: <em>'.$ftp_root.'</em>';
				echo '</span>';
			}
			echo '</li>';			
			
		return true;
	}
	
	
	
	

