<?php
defined('is_running') or die('Not an entry point...');

//for output handlers, see admin_theme_content.php for more info
global $GP_ARRANGE,$gpOutConf,$gpOutStarted;

$gpOutStarted = false;
$GP_ARRANGE = true;
$gpOutConf = array();
$gpOutConf['FullMenu']['method']		= array('gpOutput','GetFullMenu');
$gpOutConf['FullMenu']['link']			= 'all_links';

$gpOutConf['ExpandMenu']['method']		= array('gpOutput','GetExpandMenu');
$gpOutConf['ExpandMenu']['link']		= 'expanding_links';

$gpOutConf['ExpandLastMenu']['method']	= array('gpOutput','GetExpandLastMenu');
$gpOutConf['ExpandLastMenu']['link']	= 'expanding_bottom_links';

$gpOutConf['Menu']['method']			= array('gpOutput','GetMenu');
$gpOutConf['Menu']['link']				= 'top_level_links';

$gpOutConf['SubMenu']['method']			= array('gpOutput','GetSubMenu');
$gpOutConf['SubMenu']['link']			= 'subgroup_links';

$gpOutConf['TopTwoMenu']['method']		= array('gpOutput','GetTopTwoMenu');
$gpOutConf['TopTwoMenu']['link']		= 'top_two_links';

$gpOutConf['BottomTwoMenu']['method']	= array('gpOutput','GetBottomTwoMenu');
$gpOutConf['BottomTwoMenu']['link']		= 'bottom_two_links';

$gpOutConf['Extra']['method']			= array('gpOutput','GetExtra');
$gpOutConf['Text']['method']			= array('gpOutput','GetText');



class gpOutput{
	
	/* 
	 * 
	 * Request Type Functions
	 * functions used in conjuction with $_REQUEST['gpreq']
	 * 
	 */
	
	function Flush(){
		global $page;
		$page->GetMessages();
		echo $page->contentBuffer;
	}
	
	function Content(){
		global $page;
		$page->GetContent();
	}
	
	function BodyAsHTML(){
		global $page;
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		echo '<html xml:lang="en" xmlns="http://www.w3.org/1999/xhtml" lang="en">';
		$page->getHead();
		echo '<body>';
		$page->GetContent();
		echo '</body>';
		echo '</html>';
	}
	
	function Template(){
		global $rootDir,$page,$GP_ARRANGE;
		$themePath = $rootDir.'/themes/'.$page->theme_name.'/template.php';
		require_once($themePath);
	}
	
	
	/* 
	 * 
	 * Content Area Functions
	 * 
	 */

	
	/* static, deprecated V1.6 */
	function GetHandleIndex($name){
		global $gpOutConf;
		static $indeces = 0;
		
		if( !isset($gpOutConf[$name]) || !isset($gpOutConf[$name]['link']) ){
			return false;
		}
		
		$indeces++;
		return $indeces;
	}
	
	/* static */
	function GetContainerID($name){
		static $indices;
		if( !isset($indices[$name]) ){
			$indices[$name] = 0;
		}else{
			$indices[$name]++;
		}
		return $name.'_'.$indices[$name];
	}
	
	
	function Get($default,$arg=''){
		global $config,$langmessage,$gpOutConf,$page;
		
		
		//this shouldn't just be an integer..
		//	if someone is editing their theme, and moves handlers around, then these will get mixed up as well!
		$handle_index = gpOutput::GetHandleIndex($default); //deprecated V1.6
		$container_id = gpOutput::GetContainerID($default);
		
		
		$outSet = false;
		$outKeys = false;
		if( isset($config['theme_handlers'][$page->theme_name]) ){
			$handlers =& $config['theme_handlers'][$page->theme_name];
			
			//new method
			if( isset($handlers[$container_id]) ){
				
				$outKeys = $handlers[$container_id];
				$outSet = true;
			
			//old method
			}elseif( $handle_index && isset($handlers[$handle_index]) ){
				$function = $handlers[$handle_index];
				
				if( (substr($function,-4) == '_Out')  && (substr($function,0,3) === 'Get') ){
					$function = substr($function,3,-4);
				}
				
				$outKeys = array($function.':'.$arg);
				$outSet = true;
			}

			
		}
		
		//default values
		if( !$outSet ){
			$outKeys[] = $default.':'.$arg;
		}
		gpOutput::ForEachOutput($outKeys,$container_id);
	}
	
	function ForEachOutput($outKeys,$container_id){
		
		if( !is_array($outKeys) || (count($outKeys) == 0) ){
			$info = array();
			$info['gpOutKey'] = '';
			gpOutput::CallOutput($info,$container_id);
			return;
		}
		
		foreach($outKeys as $gpOutKey){
			
			$info = gpOutput::GetgpOutInfo($gpOutKey);
			if( $info === false ){
				trigger_error('gpOutKey <i>'.$gpOutKey.'</i> not set');
				continue;
			}
			$info['gpOutKey'] = $gpOutKey;
			gpOutput::CallOutput($info,$container_id);
		}
	}
	
	/* static */
	function GetgpOutInfo($key){
		global $gpOutConf,$config;
		
		$info = false;
		$arg = '';
		$pos = strpos($key,':');
		if( $pos > 0 ){
			$arg = substr($key,$pos+1);
			$key = substr($key,0,$pos);
		}
		
		
		if( isset($gpOutConf[$key]) ){
			$info = $gpOutConf[$key];
		}elseif( isset($config['gadgets'][$key]) ){
			$info = $config['gadgets'][$key];
		}else{
			return false;
		}
		$info['key'] = $key;
		$info['arg'] = $arg;
		return $info;
	}
	
	
	function CallOutput($info,$container_id){
		global $dataDir,$GP_ARRANGE,$page,$langmessage,$gpOutStarted;
		static $linkCount = 1;
		$gpOutStarted = true;
		
		if( isset($info['disabled']) ){
			return;
		}
		
		//gpOutKey identifies the output function used, there can only be one 
		if( !isset($info['gpOutKey']) ){
			trigger_error('gpOutKey not set for $info in CallOutput()');
			return;
		}
				
		$edit = $GP_ARRANGE;
		$GP_ARRANGE = true;
		
		$arg =& $info['arg'];
		$param = $container_id.'|'.$info['gpOutKey'];
		$class = 'gpArea_'.str_replace(':','_',$info['gpOutKey']);
		
		$permission = common::LoggedIn() && admin_tools::HasScriptsPermission('Admin_Theme_Content');

		if( $edit !== false ){
			echo '<div class="output_area '.$class.'">';
			if( $permission ){
				echo common::Link('Admin_Theme_Content',$param,'cmd=drag&dragging='.$param.'&to=%s',' style="display:none" name="creq"');
			}
		}
		
		$wrapLink = false;
		if( isset($info['link']) && $permission ){
			$wrapLink = true;
			
			$label =& $langmessage[$info['link']];
			echo '<a class="menu_marker" name="'.$info['key'].'" style="display:none"></a>'; //for menu arrangement, admin_menu.js
			echo '<div class="editable_area">'; // class="edit_area" added by javascript
			echo common::Link('Admin_Theme_Content',$langmessage['edit'],'cmd=edit&handle='.$param,' class="ExtraEditLink" rel="links" name="ajax_box" title="'.$label.'" ');
			$linkCount++;
		}

		if( isset($info['addon']) ){
			AddonTools::SetDataFolder($info['addon']);
		}
		
		$empty = true;
		if( isset($info['script']) ){
			if( file_exists($dataDir.$info['script']) ){
				require($dataDir.$info['script']);
				$empty = false;
			}
		}
		
		if( isset($info['data']) ){
			if( file_exists($dataDir.$info['data']) ){
				require($dataDir.$info['data']);
				$empty = false;
			}
		}
		
		if( isset($info['class']) ){
			if( class_exists($info['class']) ){
				new $info['class'](); //should $arg and $info be passed to class
				$empty = false;
			}
		}
		
		if( isset($info['method']) ){
			call_user_func($info['method'],$arg,$info);
			$empty = false;
		}
		
		if( $empty && common::LoggedIn() ){
			echo '&nbsp;';
		}
		
		AddonTools::ClearDataFolder();
		
		if( $wrapLink ){
			echo '</div>';
		}
		
		if( $edit !== false ){
			echo '</div>';
		}
		
	}
	
	/* the issue here involves moving gadgets out, reorganizing gadgets and installing new gadgets 
	How do we detect a newly installed gadget after an organized list has been created?
	.. do we add it to the list when the addon is installed? remove when uninstalled?
			.. still going to have to remove from the lists they're uninstalled
	*/
	
	function GetAllGadgets(){
		global $config,$rootDir,$page;
		
		if( !isset($config['gadgets']) ){
			return;
		}
		
		$list = false;
		if( isset($config['theme_handlers'][$page->theme_name]['GetAllGadgets']) ){
			gpOutput::ForEachOutput($config['theme_handlers'][$page->theme_name]['GetAllGadgets'],'GetAllGadgets');
			
		}else{
		
			foreach($config['gadgets'] as $gadget => $info){
				
				if( isset($info['addon']) ){
					$info['gpOutKey'] = $gadget;
					gpOutput::CallOutput($info,'GetAllGadgets');
				}
			}
		}
	}
	
	function GetExtra($name='Side_Menu'){
		global $dataDir,$langmessage,$page;
		$file = $dataDir.'/data/_extra/' . $name . '.php';
		
		$wrap =  common::LoggedIn() && admin_tools::HasScriptsPermission('Admin_Extra');
		
		if( $wrap ){
			echo '<div class="editable_area" >'; // class="edit_area" added by javascript
			echo common::Link('Admin_Extra',$langmessage['edit'],'cmd=edit&file='.$name,' class="ExtraEditLink" rel="extra" title="'.$name.'" ');
		}
		if( file_exists($file) ){
			include($file);
		}
		
		if( $wrap ){
			echo '</div>';
		}
	}


	
	function GetFullMenu(){
		global $gpmenu;
		gpOutput::OutputMenu($gpmenu,0);
	}
	
	function GetMenu(){
		global $gpmenu;
		
		$sendMenu = array();
		foreach($gpmenu as $title => $level){
			if( (int)$level !== 0 ){
				continue;
			}
			$sendMenu[$title] = $level;
		}
		gpOutput::OutputMenu($sendMenu,0);
		
	}
	
	function GetSubMenu(){
		global $gpmenu,$page;
		
		$menu = array();
		$foundGroup = false;
		foreach($gpmenu as $title => $level){
			if( $foundGroup ){
				if( $level == 0 ){
					break;
				}
			}
				
			if( $title == $page->title ){
				$foundGroup = true;
			}
			
			if( $level == 0 ){
				$menu = array();
				continue;
			}
			$menu[$title] = $level;
		}
		if( !$foundGroup ){
			gpOutput::OutputMenu(array(),1);
		}else{
			gpOutput::OutputMenu($menu,1);
		}
	}
	
	function GetTopTwoMenu(){
		global $gpmenu;
		
		$sendMenu = array();
		foreach($gpmenu as $title => $level){
			if( $level == 2 ){
				continue;
			}
			$sendMenu[$title] = $level;
		}
		gpOutput::OutputMenu($sendMenu,0);
	}
	function GetBottomTwoMenu(){
		global $gpmenu;
		
		$sendMenu = array();
		foreach($gpmenu as $title => $level){
			if( $level == 0 ){
				continue;
			}
			$sendMenu[$title] = $level;
		}
		gpOutput::OutputMenu($sendMenu,0);
	}
	
	function GetExpandLastMenu(){
		global $gpmenu,$page;
		
		$menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($gpmenu as $title => $level){
			
			if( ($level == 0) || ($level == 1) ){
				$submenu = array();
				$foundGroup = false;
			}
			
			if( $title == $page->title ){
				$foundGroup = true;
				$menu = $menu + $submenu; //not using array_merge because of numeric indexes
			}
			
			
			if( $foundGroup ){
				$menu[$title] = $level;
			}elseif( ($level == 0) || ($level == 1) ){
				$menu[$title] = $level;
			}else{
				$submenu[$title] = $level;
			}
		}
		
		gpOutput::OutputMenu($menu,0);
	}
	
	function GetExpandMenu(){
		global $gpmenu,$page;

		$menu = array();
		$submenu = array();
		$foundGroup = false;
		foreach($gpmenu as $title => $level){
			
			if( $level == 0 ){
				$submenu = array();
				$foundGroup = false;
			}
			
			if( $title == $page->title ){
				$foundGroup = true;
				$menu = $menu + $submenu; //not using array_merge because of numeric indexes
			}
			
			
			
			if( $foundGroup ){
				$menu[$title] = $level;
			}elseif( $level == 0 ){
				$menu[$title] = $level;
			}else{
				$submenu[$title] = $level;
			}
		}
		
		gpOutput::OutputMenu($menu,0);
	}

	
	function OutputMenu($menu,$startLevel){
		global $langmessage,$gpmenu,$page;
		
		
		if( count($menu) == 0 ){
			echo '<div class="emtpy_menu"></div>'; //an empty <ul> is not valid xhtml
			return;
		}
		
		$rmenu = array_reverse( $gpmenu, true );
		$haschildren = false;
		$childselected = false;
		$childLevel = false;
		$result = array();
		$prevLevel = $startLevel;
		$open = false;
		
		$result[] = "\n\n";
		$result[] = '</ul>';
		foreach($rmenu as $title => $thisLevel){
			$class = '';
			$hmm = '';
			
			//create link if in $menu
			if( isset($menu[$title]) ){
				
				//classes
				if( $title == $page->title ){
					$class .= 'selected ';
				}elseif( $childselected && ($thisLevel < $childLevel) ){
					$class .= 'childselected ';
					$childLevel = $thisLevel;
				}
				
				if( $haschildren && ($thisLevel < 2) ){
					$class .= 'haschildren ';
				}
			
				if( !$open ){
					$result[] = '</li>';
				}
				
				if( $thisLevel < $prevLevel ){
					
					while( $thisLevel < $prevLevel ){
						$result[] = '<ul><li>';
						$prevLevel--;
					}
					
				}elseif( $thisLevel > $prevLevel ){
					
					if( $open ){
						$result[] = '</li><li>';
					}
					
					while( $thisLevel > $prevLevel ){
						$result[] = '</li></ul>';
						$prevLevel++;
					}
					
				}elseif( $open ){
					$result[] = '</li><li>';
				}
				
				$label = common::GetLabel($title,false);
				if( !empty($class) ){
					$class = 'class="'.$class.'" ';
				}
				$result[] = common::Link($title,$hmm.$label,'',$class);
				$prevLevel = $thisLevel;
				$open = true;
				
			}

				
			
			//set information for following links
			if( $title == $page->title ){
				$childselected = true;
				$childLevel = $thisLevel;
			}
			
			if( $thisLevel > 0 ){
				$haschildren = true;
			}else{
				$haschildren = false;
				$childselected = false;
			}
		}
		
		//finish it off
		while( $prevLevel >= $startLevel ){
			$result[] = '<ul><li>';
			$prevLevel--;
		}
		
		//make sure the top is labeled
		if( count($result) > 1 ){
			$result[count($result)-1] = '<ul class="menu_top"><li>';
		}
		
		$result[] = "\n";
		
		$result = array_reverse( $result);
		echo implode("\n",$result);
	}
	
	
	
	/* 
	 * 
	 * Output Additional Areas
	 * 
	 */
	
	/* draggable html and editable text */
	function Area($name,$html){
		global $gpOutConf,$gpOutStarted;
		if( $gpOutStarted ){
			trigger_error('gpOutput::Area() must be called before all other output functions');
			return;
		}
		$name = '[text]'.$name;
		$gpOutConf[$name] = array();
		$gpOutConf[$name]['method'] = array('gpOutput','GetAreaOut');
		$gpOutConf[$name]['html'] = $html;
	}
	
	function GetArea($name,$text){
		$name = '[text]'.$name;
		gpOutput::Get($name,$text);
	}
	
	function GetAreaOut($text,$info){
		global $config,$langmessage,$page;
		$name = substr($info['key'],5); //remove the "text:"
		$html =& $info['html'];
		
		$wrap = common::LoggedIn() && admin_tools::HasScriptsPermission('Admin_Theme_Content');
		if( $wrap ){
			echo '<div class="editable_area" >'; // class="edit_area" added by javascript
			echo common::Link('Admin_Theme_Content',$langmessage['edit'],'cmd=edittext&key='.urlencode($text).'&return='.$page->title,' class="ExtraEditLink" rel="extra" title="'.urlencode($text).'" name="ajax_box" ');
		}
		
		if( isset($config['customlang'][$text]) ){
			$text = $config['customlang'][$text];
			
		}elseif( isset($langmessage[$text]) ){
			$text =  $langmessage[$text];
		}
		
		echo str_replace('%s',$text,$html); //in case there's more than one %s
		
		if( $wrap ){
			echo '</div>';
		}
		
	}
	
	/* editable text, not draggable */
	function GetText($key,$html='%s'){
		echo gpOutput::ReturnText($key,$html);
	}
	
	function ReturnText($key,$html='%s'){
		global $langmessage,$config,$page;
		
		
		$result = '';
		$wrap = common::LoggedIn() && admin_tools::HasScriptsPermission('Admin_Theme_Content');
		if( $wrap ){
			$result .= '<span class="editable_area" >'; // class="edit_area" added by javascript
			$result .= common::Link('Admin_Theme_Content',$langmessage['edit'],'cmd=edittext&key='.urlencode($key),' class="ExtraEditLink" rel="extra" title="'.urlencode($key).'" name="ajax_box" ');
		}
		
		$text = $key;
		if( isset($config['customlang'][$key]) ){
			$text = $config['customlang'][$key];
			
		}elseif( isset($langmessage[$key]) ){
			$text = $langmessage[$key];
		}
		
		$result .= str_replace('%s',$text,$html); //in case there's more than one %s
		
		
		if( $wrap ){
			$result .= '</span>';
		}
		return $result;
	}
	function TextId($text){
		$id = base64_encode($text);
		return str_replace(array('='),array(''),$text);
	}
	
	
	function GetHead() {
		global $config, $wbMessageBuffer,$page;
		
		if( common::LoggedIn() ){
			common::AddColorBox();
		}
		
		echo '<title>'.$page->label.' - '.$config['title'].'</title>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		
		echo '<meta http-equiv="Content-Language" content="'.$config['language'].'" />';
		echo '<meta name="keywords" content="'.$page->label.','.$config['keywords'].'" />';
		
		if( !empty($config['desc']) ){
			echo '<meta name="description" content="';
			echo htmlspecialchars($config['desc']);
			echo '" />';
		}
		echo '<meta name="generator" content="gpEasy.com" />';

		//use local copy unless specified otherwise
		if( isset($config['jquery']) && $config['jquery'] == 'google' ){
			echo '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js" type="text/javascript"></script>';
		}else{
			echo '<script src="'.common::getDir('/include/js/jquery.1.4.2.js').'" type="text/javascript"></script>';
		}
		
		echo '<script type="text/javascript">';
		echo 'var gplinks = []';
		echo ', gpinputs = []';
		echo ', gpresponse = []';
		echo ', IE7=false, WB=new Object()';
		if( common::LoggedIn() ){
			echo ', isadmin = true;';
		}else{
			echo ', isadmin = false;';
		}
		echo '</script>';
		echo '<!--[if IE 7]>';
		echo '<script type="text/javascript">IE7=true;</script>';
		echo '<![endif]-->';
		
		echo '<link rel="stylesheet" type="text/css" href="'.common::getDir('/include/css/additional.css').'" />';
		if( !empty($page->theme_name) ){
			echo '<link rel="stylesheet" type="text/css" href="'.common::getDir('/themes/'.$page->theme_name.'/'.$page->theme_css.'/style.css').'" />';
		}
		
		echo $page->head;
		
		if( common::LoggedIn() || $page->admin_js ){
			echo '<link rel="stylesheet" type="text/css" href="'.common::getDir('/include/css/admin.css').'" />';
			echo '<script type="text/javascript" src="'. common::getDir('/include/js/admin.js') .'"></script>';
		}
		
		
		if( !empty($page->jQueryCode) ){
			echo '<script type="text/javascript">';
			echo "\n\n";
			echo '$(function(){';
			echo $page->jQueryCode;
			echo '});';
			echo "\n\n";
			echo '</script>';
		}
	}
	
	// displays the login/logout link
	function GetAdminLink(){
		global $config, $out, $langmessage,$page;
		
		//echo ' <span>';
		//echo common::Link('Special_Site_Map',$langmessage['site_map']);
		//echo '</span>';
		
		//echo ' <span>';
		//	if( common::LoggedIn() ){
		//		echo common::Link($page->title,$langmessage['logout'],'cmd=logout',' rel="nofollow" ');
		//	}else{
		//		echo common::Link('Admin',$langmessage['login'],'file='.$page->title,' rel="nofollow"');
		//	}
		//echo '</span>';
		
		
		$hide = false;
		if( isset($config['hidegplink']) && ($config['hidegplink'] == 'hide') ){
			
			$tests[] = 'googlebot';
			$tests[] = 'yahoo! slurp';
			$tests[] = 'msnbot';
			$tests[] = 'ask jeeves';
			$tests[] = 'ia_archiver';
			$tests[] = 'bot';
			$tests[] = 'spider';
			
			$agent =& $_SERVER['HTTP_USER_AGENT'];
			$agent = strtolower($agent);
			$agent = str_replace($tests,array('GP_FOUND_SPIDER'),$agent);
			if( strpos($agent,'GP_FOUND_SPIDER') === false ){
				$hide = true;
			}
		}
		if( !$hide ){
			echo ' <span>';
			echo $config['linkto'];
			echo '</span>';
		}
		
		if( common::LoggedIn() ){
			admin_tools::GetAdminPanel();
		}

	}

	
}
