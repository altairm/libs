<?php
defined('is_running') or die('Not an entry point...');


includeFile('admin/admin_addon_install.php'); // admin_addon_install extends admin_addon_tool

class admin_theme extends admin_addon_install{
//class admin_theme extends admin_addons_tool{
	var $themes = array();
	var $scriptUrl = 'Admin_Theme';
	var $themeDir;
	
	//for remote install
	var $addon_type = 'theme';
	
	
	function admin_theme(){
		global $langmessage,$config,$rootDir,$dataDir,$page;
		
		$page->head .= '<link rel="stylesheet" type="text/css" href="'.common::getDir('/include/css/admin_themes.css').'" />';
		$this->themeDir = $rootDir.'/themes';
		$this->InitRating();
		$this->current = $config['theme'];
		
		$this->theme_layout = dirname($this->current);
		$this->theme_color = basename($this->current);

		
		$cmd = common::GetCommand();
		$show = true;
		switch($cmd){
			
			
			case 'remote_install':
				$this->admin_addon_install($cmd);
				$show = false;
			break;
			
			case 'remote_install2':
				$this->admin_addon_install($cmd);
			break;
			
			
			case 'browse':
				$this->RemoteBrowse('themes');
				$show = false;
			break;
			
			case 'viewtheme':
				$this->View();
			break;
			
			case 'usetheme':
				$this->UseTheme();
			break;
			
			
			case 'Update Review';
			case 'Send Review':
			case 'rate':
				$this->RatingFunctions($cmd);
			return;
		}
		
		if( $show ){
			$this->ThemeForm();
		}
		
	}
	
	function RatingFunctions($cmd){
		
		if( parent::RatingFunctions($cmd,$rate_info) ){
			return;
		}
		
		$this->ThemeForm();
		
	}
	
	
	//possible themes		
	function GetPossible(){
		global $rootDir;
		$dir = $rootDir.'/themes';
		$themes = array();
		$layouts = gpFiles::readDir($dir,1);
		asort($layouts);
		foreach($layouts as $name){
			$fullDir = $dir.'/'.$name;
			$templateFile = $fullDir.'/template.php';
			if( !file_exists($templateFile) ){
				continue;
			}
			
			//$InstallData = $this->GetAvailInstall($fullDir);
			//if( isset($InstallData['Addon_Unique_ID']) ){
			//	$themes[$name]['id'] = $InstallData['Addon_Unique_ID'];
			//}
			
			
			$subdirs = gpFiles::readDir($fullDir,1);
			asort($subdirs);
			foreach($subdirs as $subdir){
				if( $subdir == 'images'){
					continue;
				}
				$themes[$name]['colors'][$subdir] = $subdir;
			}
		}
		return $themes;
	}
	
	
	function UseTheme(){
		global $langmessage,$config,$dataDir;
		
		if( !$this->IsAvailable($_POST['theme']) ){
			message($langmessage['OOPS']);
			return;
		}
		
		$config['theme'] = $_POST['theme'];
		
		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
			return;
		}
		$this->current = $_POST['theme'];
		message($langmessage['SAVED']);
		
	}
	
	function IsAvailable($theme){
		global $rootDir;
		
		//must not be empty
		if( empty($theme) ){
			return false;
		}
		
		//must only have a single /
		$theme = str_replace('\\','/',$theme);
		$count = substr_count( $theme,'/');
		if( $count !== 1 ){
			return false;
		}
		
		//must be a directory
		$dir = $rootDir.'/themes/'.$theme;
		if( !file_exists($dir) || !is_dir($dir) ){
			return false;
		}
		
		//check version
		if( !admin_theme::CheckVersion($theme) ){
			return false;
		}
		
		
		return true;
	}
	
	function View(){
		global $langmessage,$config;
		
		if( !$this->IsAvailable($_GET['theme']) ){
			message($langmessage['OOPS']);
			return;
		}
		
		if( $config['theme'] == $_GET['theme'] ){
			return;
		}
		$show = str_replace('/',' - ',$_GET['theme']);
		$mess = '<form action="'.common::getUrl('Admin_Theme').'" method="post">';
		$mess .= sprintf($langmessage['use_theme_prompt'],$show);
		$mess .= '<input type="hidden" name="cmd" value="usetheme" />';
		$mess .= '<input type="hidden" name="theme" value="'.htmlspecialchars($_GET['theme']).'" />';
		$mess .= ' <input type="submit" name="aaa" value="'.$langmessage['use_theme'].'" />';
		$mess .= ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		$mess .= '</form>';
		message($mess);

		
		$config['theme'] = $_GET['theme'];		
	}
	
	
	function CheckVersion($theme){
		global $langmessage, $gpversion,$rootDir;
		
		list($name,$color) = explode('/',$theme);
		$dir = $rootDir.'/themes/'.$name;
		$Install = admin_addons_tool::GetAvailInstall($dir);
		if( !isset($Install['min_gpeasy_version']) ){
			return true;
		}
		
		if(version_compare($Install['min_gpeasy_version'], $gpversion,'>') ){
			$langmessage['min_version'] = 'A minimum of gpEasy %s is required for this add-on.';
			message($langmessage['min_version'],$Install['min_gpeasy_version'],$gpversion);
			return false;
		}
		
		return true;
	}
	
	
	function ThemeForm(){
		global $langmessage,$config;
		
		echo '<h1>'.$langmessage['theme_manager'].'</h1>';
		
		$this->ShowThemes();

		echo '<br/>';
		echo '<h2>'.$langmessage['add-ons'].'</h2>';
		
		if( admin_tools::CanBrowseAddons() ){
			echo '<p>';
			echo common::Link('Admin_Theme','Browse Additional Themes','cmd=browse');
			echo '</p>';
		}
		
		echo '<p>';
		echo 'Looking for more themes? We\'re building a searchable database at <a href="http://gpeasy.com/index.php/Special_Addon_Themes">gpEasy.com</a>.';
		echo '</p>';
		echo '<p>';
		echo 'Have you created a custom theme for gpEasy? Upload it to <a href="http://gpeasy.com/index.php/Special_Addon_Themes">gpEasy.com</a> and get credit and recognition for your work.';
		echo '</p>';
		echo '<br/>';
	}
	
	function ShowThemes($current = false,$link = false,$query = 'cmd=viewtheme', $linkName = 'creq'){
		global $langmessage,$config;
		$rate = false;
			
		if( $link === false ){
			$rate = true;
			$link = 'Admin_Theme';
		}
		if( $current === false ){
			$current = $config['theme'];
		}
		
		$themes = admin_theme::GetPossible();
		$current_theme_layout = dirname($current);
		$current_theme_color = basename($current);
		
		
		echo '<p style="text-align:right;">';
		echo '<b>'.$langmessage['current_theme'].'</b><br/> ';
		echo $current;
		if( $current !== $config['theme'] ){
			echo '<br/><b>'.$langmessage['default'].'</b>';
			echo '<br/>'.$config['theme'];
		}
		echo '</p>';
		
		echo '<table id="theme_selector" cellspacing="0">';
		echo '<tr>';
			echo '<th>';
			echo $langmessage['layout'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['color'];
			echo '</th>';
			if( $rate ){
				echo '<th>';
				echo $langmessage['options'];
				echo '</th>';
			}
			echo '</tr>';
			
		$i = 0;
		$classes[] = 'class="oddrow"';
		$classes[] = 'class="evenrow"';
		foreach($themes as $layout => $layout_info){
			echo '<tr '.$classes[$i%2].'>';
			$i++;
				if( $current_theme_layout == $layout){
					echo '<td class="layout current_layout">';
				}else{
					echo '<td class="layout">';
				}
				echo str_replace('_',' ',$layout);
				if( $current_theme_layout == $layout){
					echo '<br/>';
					echo '('.$current_theme_color.')';
				}
				echo '</td>';
				
			
			echo '<td>';
			foreach($layout_info['colors'] as $color => $info){
				$selector = $layout.'/'.$color;
				
				$class = '';
				if( $current == $selector ){
					$class = ' class="current" ';
					
				}elseif( $current == $selector ){
					$class = ' class="current" ';
				}
				echo common::Link($link,$color,$query.'&theme='.$selector,' name="'.$linkName.'" title="'.$langmessage['preview'].': '.$color.'" '.$class);
			}
			echo '</td>';
			
			if( $rate ){
				echo '<td>';
				echo common::Link('Admin_Theme',$langmessage['rate'],'cmd=rate&arg='.$layout,' class="rate" ');
				echo '</td>';
			}
			echo '</tr>';
		}
		echo '</table>';
	}
	
	
	
	
	/*
	 * Rating
	 */
	 
	function GetAddonRateInfo($arg,&$info,&$message){
		
		if( (strpos($arg,'/') !== false) || (strpos($arg,'\\') !== false) ){
			message($langmessage['OOPS']);
			return false;
		}
		$dir = $this->themeDir.'/'.$arg;
		$ini = $this->GetAvailInstall($dir);
		
		
		if( $ini === false ){
			$message = 'This add-on does not have an ID assigned to it. The developer must update the install configuration.';
			return false;
		}
		
		if( !isset($ini['Addon_Unique_ID']) ){
			$message = 'This add-on does not have an ID assigned to it. The developer must update the install configuration.';
			return false;
		}
		
		$info = array();
		$info['pass_arg'] = $arg;
		$info['id'] = $ini['Addon_Unique_ID'];
		$info['name'] = $ini['Addon_Name'];
		
		return true;
	}
}
