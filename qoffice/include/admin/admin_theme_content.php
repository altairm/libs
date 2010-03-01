<?php
defined('is_running') or die('Not an entry point...');



/*
what can be moved?
	* .editable_area

How do we position elements?
	* above, below, float:left, float:right in relation to another editable_area

How do we do locate them programatically
	* We need to know the calling functions that output the areas
		then be able to organize a list of output functions within each of the calling functions
		!each area is represented by a list, either a default value if an override hasn't been defined, or the custom list created by the user
		
How To Identify the Output Functions for the Output Lists?
	* Gadgets have:
		$info['script']
		$info['data']
		$info['class']


$gpOutConf = array() of output functions/classes.. to use with the theme content
	==potential values==
	$gpOutConf[-ident-]['script'] = -path relative to datadir or rootdir?
	$gpOutConf[-ident-]['data'] = -path relative to datadir-
	$gpOutConf[-ident-]['class'] = -path relative to datadir or rootdir?
	$gpOutConf[-ident-]['method'] = string or array: string=name of function, array(class,method)
	
	
	$config['theme_handlers']['Tan Header'][-ident-] = array(0=>-ident-,1=>-ident-)



====To Look At===
	How does it work for display::GetExtra($name) where $name is critical?
	How about $page->GetLangText($key)?
	
	See PrepJson() in admin_menu.php
		admin_menu.js and how the link areas are called when files are rearranged




*/

class admin_theme_content{
	
	function admin_theme_content(){
		global $page,$config;
			
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::getDir('/include/js/theme_content.js').'"></script>';
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::getDir('/include/js/dragdrop.js').'"></script>';
		//$page->head .= '<script type="text/javascript" language="javascript" src="'.common::getDir('/include/js/dragdrop_w_direction.js').'"></script>';
		$page->SetTheme();
		
		
		$cmd = common::GetCommand();
		switch($cmd){
			
			case 'drag':
				$this->Drag();
			break;
			
			case 'restore':
				$this->Restore();
			break;
			
			
			//links
			case 'edit':
				$this->Select();
			return;
			case 'save':
				$this->Save();
			break;
			
			//text
			case 'edittext':
				$this->EditText();
			return;
			case 'savetext':
				$this->SaveText();
			break;

		}
		
		$this->Show();
		
	}
	
	
	function SaveText(){
		global $config, $langmessage,$page;
		
		if( !isset($_POST['key']) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		if( !isset($_POST['value']) ){
			message($langmessage['OOPS'].' (1)');
			return;
		}
		
		$default = $key = $_POST['key'];
		if( isset($langmessage[$key]) ){
			$default = $value = $langmessage[$key];
		}
		
		$config['customlang'][$key] = $value = htmlspecialchars($_POST['value']);
		if( $value === $default ){
			unset($config['customlang'][$key]);
		}
		
		if( admin_tools::SaveConfig() ){
			message($langmessage['SAVED']);
		}else{
			message($langmessage['OOPS'].' (s1)');
		}
		$this->ReturnHeader();
		
	}
	
	
	function EditText(){
		global $config, $langmessage,$page;
		
		if( !isset($_GET['key']) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		
		$default = $value = $key = $_GET['key'];
		if( isset($langmessage[$key]) ){
			$default = $value = $langmessage[$key];
			
		}
		if( isset($config['customlang'][$key]) ){
			$value = $config['customlang'][$key];
		}
		
		
		echo '<div class="inline_box">';
		echo '<form action="'.common::getUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="cmd" value="savetext" />';
		echo '<input type="hidden" name="key" value="'.htmlspecialchars($key).'" />';
		echo '<input type="hidden" name="return" value="" />'; //will be populated by javascript
		
		echo '<table class="bordered">';
			echo '<tr>';
			echo '<th>';
			echo $langmessage['default'];
			echo '</th>';
			echo '<th>';
			echo '</th>';
			echo '</tr>';
			echo '<tr>';
			echo '<td>';
			echo $default;
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="value" value="'.htmlspecialchars($value).'" />';
			echo ' <input type="submit" name="aaa" value="'.$langmessage['save'].'" />';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		
		echo '</form>';
		echo '</div>';
	}

	
	function Show(){
		global $config,$page,$langmessage;
		
		echo '<h2>'.$langmessage['content_arrangement'].'</h2>';
		
		echo '<p>';
		echo $langmessage['DRAG-N-DROP-DESC'];
		echo '</p>';
		

		
	
		$theme_handlers =& $config['theme_handlers'];
	
		
		echo '<table class="bordered">';
		
		echo '<tr>';
			echo '<th>';
			echo $langmessage['themes'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['modifications'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';
			
			$this->ShowTheme($page->theme_name);
			
		foreach($theme_handlers as $theme => $info){
			if( $theme == $page->theme_name ){
				continue;
			}
				
			$this->ShowTheme($theme);
		}
		echo '</table>';
		
		echo '<p>';
		echo '<< '.common::Link($config['homepath'],str_replace('_',' ',$config['homepath']));
		echo ', '.common::Link('Admin',$langmessage['admin']);
		echo '</p>';
		
		
		//$this->Notes();
		
		$this->ShowAvailable();
	}
	
	
	function ShowAvailable(){
		global $dataDir,$langmessage;
		
		$extrasFolder = $dataDir.'/data/_extra';
		$files = gpFiles::ReadDir($extrasFolder);
		asort($files);
		
		echo '<h2>'.$langmessage['theme_content'].'</h2>';
		echo '<table class="bordered">';
		echo '<tr>';
			echo '<th>';
			echo 'Area';
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '<th>';
			echo 'Usage';
			echo '</th>';
			echo '</tr>';
		
		foreach($files as $file){
			$extraName = $file;
			echo '<tr>';
				echo '<td>';
				echo $extraName;
				echo '</td>';
				echo '<td>';
				echo common::Link('Admin_Extra',$langmessage['edit'],'cmd=edit&file='.$file);
				echo '</td>';
				echo '<td>';
				echo '&lt;?php gpOutput::Get(\'Extra\', \''.$extraName.'\' ); ?&gt;';
				echo '</td>';
				echo '</tr>';
		}
		echo '</table>';
		
		
/*
		echo '<h3>Adding More Areas</h3>';
		echo 'To add more editable theme areas to your template, all you have to do is add additional calls to <em>gpOutput::Get(\'Extra\', ...)</em> in your template.php file (located in the /themes directory of your server.';
		echo '<h4>Example</h4>';
		echo '&lt;?php gpOutput::Get(\'Extra\', \'Side_Menu\' ) ?&gt;';
*/
		
	}
	
	function ShowTheme($theme){
		global $page, $langmessage,$config;
		
		$theme_handlers =& $config['theme_handlers'];
		if( isset($theme_handlers[$theme]) ){
			$info = $theme_handlers[$theme];
		}else{
			$info = array();
		}
		
		
		echo '<tr>';

		echo '<td>';
		if( $page->theme_name == $theme ){
			echo ' <img src="'.common::GetDir('/include/imgs/accept.png').'" height="16" width="16"  alt="" float="left" title="'.$langmessage['current_theme'].'"/> ';
		}else{
			echo ' <img src="'.common::GetDir('/include/imgs/blank.gif').'" height="16" width="16"  alt="" float="left"/> ';
		}

		echo $theme;
		if( $page->theme_name == $theme ){
			echo ' <span class="admin_note">(';
			echo $langmessage['current_theme'];
			echo ')</span>';
		}
		echo '</td>';
		echo '<td>';
		$count = 0;
		foreach($info as $val){
			$count += count($val);
		}
		echo $count;
		echo '</td>';
		echo '<td>';
		
		if( is_array($info) && (count($info) > 0) ){
			echo common::Link('Admin_Theme_Content',$langmessage['restore_defaults'],'cmd=restore&theme='.$theme,' name="creq" ');
			
		}
			
		echo '</td>';
		echo '</tr>';
		
	}	
	
	
	function Notes(){
		
		
		echo '<h2>Documentation</h2>';
		
		echo '<p>';
		echo '$GP_ARRANGE = false; prevents an area from being editable';
		echo '</p>';
	}
	
	
	
	
	/*
	 * 
	 * 
	 * 
	 * Link Specific Functions
	 * 
	 * 
	 * 
	 * 
	 */

	function Save(){
		global $config,$langmessage,$gpOutConf;
		
		if( !$this->ParseHandlerInfo($_POST['handle'],$curr_info) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		
		$new_gpOutKey = $_POST['new_handle'];
		if( !isset($gpOutConf[$new_gpOutKey]) || !isset($gpOutConf[$new_gpOutKey]['link']) ){
			message($langmessage['OOPS'].' (1)');
			return;
		}
		
		$handlers = $this->GetHandlersArray();

		
		$container =& $curr_info['container'];
		
		//if it's not set, then use defaults
		if( !isset($handlers[$container]) || !is_array($handlers[$container]) ){
			$handlers[$container] = $this->GetDefaultList($container,$curr_info['key:arg']);
		}
		
		//if empty
		if( count($handlers[$container]) == 0 ){
			$handlers[$container][] = $new_gpOutKey;
			
		}else{
			
			$where = array_search($curr_info['key:arg'],$handlers[$container]);
			
			if( ($where === null) || ($where === false) ){
				message($langmessage['OOPS'].' (2)');
				return;
			}
			
			array_splice($handlers[$container],$where,1,$new_gpOutKey);
		}

		$this->SaveHandlers($handlers);
		
	}
	
	function Select(){
		global $langmessage,$config,$page,$gpOutConf;
		
		if( !$this->ParseHandlerInfo($_GET['handle'],$curr_info) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		
		$handlers = $this->GetHandlersArray();
		$curr_gpOutInfo = gpOutput::GetgpOutInfo($curr_info['gpOutKey']);
		
		if( !isset($curr_gpOutInfo['link']) ){
			message($langmessage['OOPS']);
			return;
		}
		
		
		echo '<div class="inline_box">';
		echo '<form action="'.common::getUrl('Admin_Theme_Content').'" method="post">';
		echo '<input type="hidden" name="handle" value="'.htmlspecialchars($_GET['handle']).'" />';
		echo '<input type="hidden" name="return" value="" />';
		
		echo '<h2>'.$langmessage['link_configuration'].'</h2>';
		echo '<table>';
		echo '<tr>';
			echo '<td>';
			echo '<select name="new_handle">';
			foreach($gpOutConf as $outKey => $info){
				
				if( !isset($info['link']) ){
					continue;
				}

				if( $outKey == $curr_info['gpOutKey'] ){
					echo '<option value="'.$outKey.'" selected="selected">';
				}else{
					echo '<option value="'.$outKey.'">';
				}
				if( isset($langmessage[$info['link']]) ){
					echo $langmessage[$info['link']];
				}else{
					$info['link'];
				}
				echo '</option>';
			}
			echo '</select>';
			
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td>';
			echo '<input type="hidden" name="cmd" value="save" />';
			echo '<input type="submit" name="aaa" value="'.$langmessage['save'].'" /> ';
			
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		
		echo '<p class="admin_note">';
		echo $langmessage['see_also'];
		echo ' ';
		echo common::Link('Admin_Menu',$langmessage['file_manager']);
		echo ', ';
		echo common::Link('Admin_Theme_Content',$langmessage['content_arrangement']);
		echo '</p>';
		
		echo '</form>';
		echo '</div>';
	}
	
	
	
	
	/*
	 * 
	 * 
	 * 
	 * General Arrangement Functions
	 * 
	 * 
	 * 
	 * 
	 */
	
	
	function Restore(){
		global $config,$langmessage,$page;
		
		$theme =& $_GET['theme'];
		if( !isset( $config['theme_handlers'][$theme] )  ){
			message($langmessage['OOPS']);
			return;
		}
		
		$this->SaveHandlers(array(),$theme);
	}
	
	function Drag(){
		global $config,$page,$gpOutConf,$langmessage;
		
		
		if( !$this->GetValues($_GET['dragging'],$from_container,$from_gpOutKey) ){
			message($langmessage['OOPS'].' (0)');
			return;
		}
		if( !$this->GetValues($_GET['to'],$to_container,$to_gpOutKey) ){
			message($langmessage['OOPS'].'(1)');
			return;
		}
		
		$handlers = $this->GetHandlersArray();
		
		
		//if it's not set, then use defaults
		if( !isset($handlers[$from_container]) || !is_array($handlers[$from_container]) ){
			$handlers[$from_container] = $this->GetDefaultList($from_container,$from_gpOutKey);
		}
		if( !isset($handlers[$to_container]) || !is_array($handlers[$to_container]) ){
			$handlers[$to_container] = $this->GetDefaultList($to_container,$to_gpOutKey);
		}
		
		
		//remove from from_container
		if( !isset($handlers[$from_container]) || !is_array($handlers[$from_container]) ){
			message($langmessage['OOPS'].' (2)');
			return;
		}
		$where = array_search($from_gpOutKey,$handlers[$from_container]);
		if( ($where === null) || ($where === false) ){
			message($langmessage['OOPS']. '(3)');
			return;
		}
		array_splice($handlers[$from_container],$where,1);
		
		
		//add to to_container in front of $to_gpOutKey
		if( !isset($handlers[$to_container]) || !is_array($handlers[$to_container]) ){
			message($langmessage['OOPS'].' (4)');
			return;
		}
		
		
		//if empty
		if( count($handlers[$to_container]) == 0 ){
			$handlers[$to_container][] = $from_gpOutKey;
			
		}else{
			
			$where = array_search($to_gpOutKey,$handlers[$to_container]);
			if( ($where === null) || ($where === false) ){
				message($langmessage['OOPS'].' (6)');
				return;
			}
			array_splice($handlers[$to_container],$where,0,$from_gpOutKey);
		}
		
		
		$this->SaveHandlers($handlers);
		
	}
	
	function SaveHandlers($handlers,$theme=false){
		global $config,$page,$langmessage;
		
		if( $theme === false ){
			$theme = $page->theme_name;
		}
		
		
		$oldHandlers = $config['theme_handlers'][$theme];
		if( count($handlers) === 0 ){
			unset($config['theme_handlers'][$theme]);
		}else{
			$config['theme_handlers'][$theme] = $handlers;
		}
		
		if( admin_tools::SaveConfig() ){
			
			message($langmessage['SAVED']);
			
		}else{
			$config['theme_handlers'][$theme] = $oldHandlers;
			message($langmessage['OOPS'].' (s1)');
		}
		
		$this->ReturnHeader();

	}
	
	function ReturnHeader(){
		
		if( empty($_POST['return']) ){
			return;
		}
		
		$return = $_POST['return'];
		//$return = str_replace('cmd=','x=',$return); //some dynamic plugins rely on cmd to show specific pages.
		
		if( strpos($return,'http') == 0 ){
			header('Location: '.$return);
			die();
		}
			
		header('Location: '.common::getUrl($_POST['return'],false));
		die();
	}
	
	
	function ParseHandlerInfo($str,&$info){
		global $config,$gpOutConf;
		
		if( substr_count($str,'|') !== 1 ){
			return false;
		}
		
		
		list($container,$fullKey) = explode('|',$str);
		
		$arg = '';
		$pos = strpos($fullKey,':');
		$key = $fullKey;
		if( $pos > 0 ){
			$arg = substr($fullKey,$pos+1);
			$key = substr($fullKey,0,$pos);
		}
		
		if( !isset($gpOutConf[$key]) && !isset($config['gadgets'][$key]) ){
			return false;
		}
		
		$info = array();
		$info['gpOutKey'] = $key;
		$info['container'] = $container;
		$info['arg'] = $arg;
		$info['key:arg'] = $fullKey;
		
		return true;
		
	}
	
	
	function GetHandlersArray(){
		global $page,$config;
		
		if( !isset($config['theme_handlers'][$page->theme_name]) ){
			$config['theme_handlers'][$page->theme_name] = array();
		}
		
		$handlers = $config['theme_handlers'][$page->theme_name];
		if( !is_array($handlers) || count($handlers) < 1 ){
			$handlers = array();
		}
		return $handlers;
	}
	
	
	function GetDefaultList($container,$gpOutkey){
		global $config;

		if( $container !== 'GetAllGadgets' ){
			return array($gpOutkey);
		}
		
		$result = array();
		if( isset($config['gadgets']) && is_array($config['gadgets']) ){
			foreach($config['gadgets'] as $gadget => $info){
				$result[] = $gadget;
			}
		}
		return $result;
	}
	
	function GetValues($a,&$container,&$gpOutKey){
		if( substr_count($a,'|') !== 1 ){
			return false;
		}
		
		list($container,$gpOutKey) = explode('|',$a);
		return true;
	}
}
