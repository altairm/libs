<?php
defined('is_running') or die('Not an entry point...');


class special_display extends display{
	var $pagetype = 'special_display';
	var $requested = false;
	var $scripts = array();

	function special_display($title){
		global $rootDir,$langmessage,$config;
		
		$this->requested = $title;
		$this->title = $title;
		$this->label = 'Special';
		$this->SetTheme();
		
		
		$this->scripts['Special_Site_Map']['script'] = '/include/special/special_map.php';
		$this->scripts['Special_Site_Map']['label'] = $langmessage['site_map'];
		$this->scripts['Special_Site_Map']['class'] = 'special_map';

		$this->scripts['Special_Galleries']['script'] = '/include/special/special_galleries.php';
		$this->scripts['Special_Galleries']['label'] = $langmessage['galleries'];
		$this->scripts['Special_Galleries']['class'] = 'special_galleries';

		$this->scripts['Special_Contact']['script'] = '/include/special/special_contact.php';
		$this->scripts['Special_Contact']['label'] = $langmessage['contact'];
		$this->scripts['Special_Contact']['class'] = 'special_contact';
		
		
		
	}
	
	function RunScript(){
		global $langmessage,$rootDir,$gptitles;
		ob_start();
		
		
		
		$scriptinfo = false;
		if( isset($this->scripts[$this->requested]) ){
			
			$scriptinfo = $this->scripts[$this->requested];
			
		}elseif( isset($gptitles[$this->requested]) ){
			$scriptinfo = $gptitles[$this->requested];
			
			if( isset($scriptinfo['addon']) ){
				if( !file_exists($rootDir.$scriptinfo['script']) ){
					$scriptinfo = false;
				}else{
					AddonTools::SetDataFolder($scriptinfo['addon']);
				}
			}
			
		}
			
		if( $scriptinfo !== false ){
			if( isset($scriptinfo['label']) ){
				$this->label = $scriptinfo['label'];
			}
			
			if( isset($scriptinfo['script']) ){
				require($rootDir.$scriptinfo['script']);
			}
			if( isset($scriptinfo['class']) ){
				new $scriptinfo['class']();
			}
			AddonTools::ClearDataFolder();
		}else{
			message($langmessage['OOPS_TITLE']);
		}
		
		$this->contentBuffer = common::get_clean();
	}
		
	
	
	function GetContent(){
		$this->GetMessages();
		echo $this->contentBuffer;
	}
	

}
