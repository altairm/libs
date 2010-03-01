<?php
defined("is_running") or die("Not an entry point...");


class gpupgrade{
	
	function gpupgrade(){
		

		$this->to10b2();
	}
	
	function to10b2(){
		global $gpmenu,$gptitles,$dataDir,$config;
		
		require_once($GLOBALS['rootDir'].'/include/admin/admin_tools.php');
		
		
		//rename extra0
		$base = $dataDir.'/data/_extra';
		$files = gpFiles::readDir($base,'php');
		foreach($files as $file => $hmm){
			
			if( strpos($file,'extra') === 0 ){
				$oldFile = $base.'/'.$file.'.php';
				$newFile = $base.'/'.substr($file,5).'.php';
				rename($oldFile,$newFile);
			}
		}
		
		//side menu
		$from = $base.'/0.php';
		$to = $base.'/Side_Menu.php';
		if( file_exists($from) ){
			rename($from,$to);
		}
		
		
		
		//titles
		//	used for type
		$gptitles['Special_Site_Map']['type'] = 'special';
		$gptitles['Special_Site_Map']['lang_index'] = 'site_map';
		
		$gptitles['Special_Galleries']['type'] = 'special';
		$gptitles['Special_Galleries']['lang_index'] = 'galleries';
		
		$gptitles['Special_Contact']['type'] = 'special';
		$gptitles['Special_Contact']['lang_index'] = 'contact';
		
		if( !admin_tools::SavePagesPHP() ){
			return;
		}
		
		
		//Footer
		$file = $dataDir.'/data/_extra/Footer.php';
		if( !file_exists($file) ){
			gpFiles::SaveFile($file,'<p>'.$config['footer'].'</p>');
		}
		
		
		//Header
		$file = $dataDir.'/data/_extra/Header.php';
		if( !file_exists($file) ){
			$contents = '<h1>'.common::Link('',$config['title']).'</h1>';
			$contents .= '<h4>'.$config['subtitle'].'</h4>';
			gpFiles::SaveFile($file,$contents);
		}
		
		//version
		$config['gpversion'] = $GLOBALS['gpversion'];
		
		//language
		if( $config['language'] == 'de_DE' ){
			$config['language'] = 'de';
		}
		if( $config['language'] == 'en_US' ){
			$config['language'] = 'en';
		}

		
		$config['path_info'] = true;
		unset($config['footer']);
		unset($config['extension']);
		admin_tools::SaveConfig();
		
	}
}
	
