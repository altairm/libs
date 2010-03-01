<?php
defined('is_running') or die('Not an entry point...');


class admin_tools{
	
	
	function AdminScripts(){
		global $langmessage,$config;
		$scripts = array();

		$scripts['Admin_Menu']['script'] = '/include/admin/admin_menu.php';
		$scripts['Admin_Menu']['class'] = 'admin_menu';
		$scripts['Admin_Menu']['label'] = $langmessage['file_manager'];

		$scripts['Admin_Uploaded']['script'] = '/include/admin/admin_uploaded.php';
		$scripts['Admin_Uploaded']['class'] = 'admin_uploaded';
		$scripts['Admin_Uploaded']['label'] = $langmessage['uploaded_files'];
		
		$scripts['Admin_Extra']['script'] = '/include/admin/admin_extra.php';
		$scripts['Admin_Extra']['class'] = 'admin_extra';
		$scripts['Admin_Extra']['label'] = $langmessage['theme_content'];
		
		$scripts['Admin_Theme']['script'] = '/include/admin/admin_theme.php';
		$scripts['Admin_Theme']['class'] = 'admin_theme';
		$scripts['Admin_Theme']['label'] = $langmessage['theme_manager'];
		
		$scripts['Admin_Users']['script'] = '/include/admin/admin_users.php';
		$scripts['Admin_Users']['class'] = 'admin_users';
		$scripts['Admin_Users']['label'] = $langmessage['user_permissions'];
		
		$scripts['Admin_Configuration']['script'] = '/include/admin/admin_configuration.php';
		$scripts['Admin_Configuration']['class'] = 'admin_configuration';
		$scripts['Admin_Configuration']['label'] = $langmessage['configuration'];
		
		
		$scripts['Admin_Trash']['script'] = '/include/admin/admin_trash.php';
		$scripts['Admin_Trash']['class'] = 'admin_trash';
		$scripts['Admin_Trash']['label'] = $langmessage['trash'];
		
		
		if( isset($config['admin_links']) && is_array($config['admin_links']) ){
			$scripts += $config['admin_links'];
		}

		$scripts['Admin_Uninstall']['script'] = '/include/admin/admin_rm.php';
		$scripts['Admin_Uninstall']['class'] = 'admin_rm';
		$scripts['Admin_Uninstall']['label'] = $langmessage['uninstall_prep'];
		
		
		/*
		 * 	Unlisted
		 */


		$scripts['Admin_Addons']['script'] = '/include/admin/admin_addons.php';
		$scripts['Admin_Addons']['class'] = 'admin_addons';
		$scripts['Admin_Addons']['label'] = $langmessage['add-ons'];
		$scripts['Admin_Addons']['list'] = false;
		

		$scripts['Admin_New']['script'] = '/include/admin/admin_new.php';
		$scripts['Admin_New']['class'] = 'admin_new';
		$scripts['Admin_New']['label'] = $langmessage['new_file'];
		$scripts['Admin_New']['list'] = false;

		$scripts['Admin_Theme_Content']['script'] = '/include/admin/admin_theme_content.php';
		$scripts['Admin_Theme_Content']['class'] = 'admin_theme_content';
		$scripts['Admin_Theme_Content']['label'] = $langmessage['content_arrangement'];
		$scripts['Admin_Theme_Content']['list'] = false;
			
		

/*
		$scripts['Admin_Addon_Themes']['script'] = '/include/admin/admin_addon_themes.php';
		$scripts['Admin_Addon_Themes']['class'] = 'admin_addon_themes';
		$scripts['Admin_Addon_Themes']['label'] = $langmessage['addon_themes'];
		$scripts['Admin_Addon_Themes']['list'] = false;
*/



		return $scripts;
	}
	
	
	
	
	function GetInfo($script){
		
		$scripts = admin_tools::AdminScripts();
		if( !isset($scripts[$script]) ){
			return false;
		}
		return admin_tools::HasPermission($script,$scripts);
	}

	function HasPermission($script,&$scripts){
		global $gpAdmin;
		
		$scripts = admin_tools::AdminScripts();
					
		$gpAdmin += array('granted'=>'');
		if( $gpAdmin['granted'] == 'all' ){
			return $scripts[$script];
		}
		
		$granted = ','.$gpAdmin['granted'].',';
		if( strpos($granted,','.$script.',') !== false ){
			return $scripts[$script];
		}
		return false;
	}
	
	
	function HasScriptsPermission($script){
		$scripts = admin_tools::AdminScripts();
		return admin_tools::HasPermission($script,$scripts);
	}
		
	function GetAdminPanel(){
		global $langmessage,$page,$gpAdmin;
		
		//don't send the panel when it's a gpreq=json request
		if( isset($_REQUEST['gpreq']) ){
			return;
		}
		
		echo '<div id="edit_area_overlay_top" class="edit_area_overlay"></div>';
		echo '<div id="edit_area_overlay_right" class="edit_area_overlay"></div>';
		echo '<div id="edit_area_overlay_bottom" class="edit_area_overlay"></div>';
		echo '<div id="edit_area_overlay_left" class="edit_area_overlay"></div>';
		
		echo '<div id="gpadminpanel" style="display:none">';
		
		echo '<div id="simplepanel">';
		echo '<div class="panelwrapper">';
		
			echo '<ul class="right">';
			
				//admin
				echo '<li class="expand_child simple_top">';
					$img = '<img src="'.common::GetDir('/include/imgs/page_white_gear.png').'" height="16" width="16" alt=""/>';
					echo common::Link('Admin',$img.$langmessage['admin']);
					admin_tools::GetAdminLinks();
				echo '</li>';
				
				//add-ons
				echo '<li class="expand_child simple_top">';
					$img = '<img src="'.common::GetDir('/include/imgs/plugin.png').'" height="16" width="16" alt=""/>';
					echo common::Link('Admin_Addons',$img.$langmessage['plugins'].' (beta)','','class="toplink"');
					admin_tools::GetAddonLinks();
				echo '</li>';

				
				//username
				echo '<li class="expand_child simple_top">';
					$img = '<img src="'.common::GetDir('/include/imgs/user.png').'" height="16" width="16"  alt=""/>';
					echo common::Link('Admin_Password',$img.$gpAdmin['username'],'','class="toplink"');
					echo '<ul>';
						echo '<li>';
						echo common::Link('Admin_Password',$langmessage['change_password']);
						echo '</li>';
						echo '<li>';
						echo common::Link($page->title,$langmessage['logout'],'cmd=logout');
						echo '</li>';
						echo '<li class="seperator">';
						//echo common::Link('Admin_About','About gpEasy','',' name="ajax_box" ');
						echo common::Link('Admin_About','About gpEasy');
						echo '</li>';
					echo '</ul>';
				echo '</li>';
					
			echo '</ul>';
			
			echo '<ul class="left">';
				
				
				//frequently used
				echo '<li class="expand_child simple_top">';
					$img = '<img src="'.common::GetDir('/include/imgs/page_white_text.png').'" height="16" width="16"  alt=""/>';
					echo '<a href="#" class="toplink">'.$img.$langmessage['frequently_used'].'</a>';
					echo '<ul>';
					$scripts = admin_tools::AdminScripts();
					$add_one = true;
					if( isset($gpAdmin['freq_scripts']) ){
						foreach($gpAdmin['freq_scripts'] as $link => $hits ){
							if( isset($scripts[$link]) ){
								echo '<li>';
								echo common::Link($link,$scripts[$link]['label']);
								echo '</li>';
								if( $link === 'Admin_Menu' ){
									$add_one = false;
								}
							}
						}
						if( $add_one && count($gpAdmin['freq_scripts']) >= 5 ){
							$add_one = false;
						}
					}
					if( $add_one ){
						echo '<li>';
						echo common::Link('Admin_Menu',$scripts['Admin_Menu']['label']);
						echo '</li>';
					}
					echo '</ul>';
				echo '</li>';
								
				//editable areas
				echo '<li class="expand_child simple_top" id="edit_list_new">';
					$img = '<img src="'.common::GetDir('/include/imgs/page_edit.png').'" height="16" width="16"  alt=""/> ';
					echo '<a href="#" class="toplink">'.$img;
					echo $langmessage['editable_area'];
					echo '</a>';
					
					echo '<ul>';
					
					if( admin_tools::HasPermission('Admin_Theme_Content',$scripts) ){
						//echo '<li class="seperator">';
						echo '<li>';
						echo common::Link('Admin_Theme_Content',$langmessage['arrange_content']);
						echo '</li>';
					}
					echo '</ul>';

				echo '</li>';

				
			
			echo '</ul>';
			
		echo '</div>';
		echo '</div>'; //end simplepanel
		

		//simplesubpanel
		if( count($page->admin_links) > 0 ){
			echo ' <div id="simplesubpanel">';
			echo '<div class="panelwrapper">';
			echo '<ul class="left">';
			foreach($page->admin_links as $label => $link){
				echo '<li class="simple_top">';
					if( is_numeric($label) ){
						echo $link;
						
					/* the following two options are deprecated */
					}elseif( empty($link) ){
						echo '<span>';
						echo $label;
						echo '</span>';
						$link = '#';
						
					}else{
						echo '<a href="'.$link.'">';
						echo $label;
						echo '</a>';
					}
				echo '</li>';
			}
			
			echo '</ul> ';
			echo '<div style="clear:both"></div>';
			echo '</div> ';
			echo '</div> ';
		}

			
		echo '</div>'; //end adminpanel

		
		echo '<div id="loading" style="display:none">';
			echo '<div>';
			echo '<img src="'.common::GetDir('/include/imgs/loader64.gif').'" alt="'.$langmessage['loading'].'" />';
			echo '<br/>';
			echo $langmessage['loading'];
			echo '</div>';
		echo '</div>';

		
		
	}

	
	
	function GetAdminLinks(){
		global $langmessage;
		
		$scripts = admin_tools::AdminScripts();
		

		$show = array();
		$count = 0;
		$addon = false;
		echo '<ul>';
		foreach($scripts as $script => $info){
			if( isset($info['list']) && ($info['list'] === false) ){
				continue;
			}
			if( admin_tools::HasPermission($script,$scripts) ){
				$class = '';
				if( isset($info['addon']) ){
					if( $addon == false ){
						$class = ' class="seperator" ';
					}
					$addon = true;
				}elseif( $addon ){
					$class = ' class="seperator" ';
				}
				
				echo '<li '.$class.'>';
				echo common::Link($script,$info['label']);
				echo '</li>';
				$count++;
			}
		}
		
		if( $count < 1 ){
			echo '<li>';
			echo common::Link('Admin_Password',$langmessage['change_password']);
			echo '</li>';
		}
		echo '</ul>';
	}
	

	
	function CheckPostedNewPage($title=false){
		global $langmessage,$gptitles;
		
		if( $title === false ){
			$title = $_POST['title'];
		}
		
		
		$title = gpFiles::CleanTitle($title);
		if( isset($gptitles[$title]) ){
			message($langmessage['TITLE_EXISTS']);
			return false;
		}
		if( empty($title) ){
			message($langmessage['TITLE_REQUIRED']);
			return false;
		}
		
		$type =  common::SpecialOrAdmin($title);
		if( $type !== false ){
			message($langmessage['TITLE_EXISTS']);
			return false;
		}
		
		
		if( strlen($title) > 80 ){
			message($langmessage['LONG_TITLE']);
			return false;
		}
		return $title;
	}		
					

	
	
	/* deprecated */
	function PHPVariable($name,$value){
		return gpFiles::PHPVariable($name,$value);
	}
	
	/* deprecated */
	function ArrayToPHP($varname,&$array){
		return gpFiles::ArrayToPHP($varname,$array);
	}
	
	/* deprecated */
	function SaveArray($file,$varname,&$array){
		return gpFiles::SaveArray($file,$varname,$array);
	}
	
	
	//
	//	functions for gpmenu, gptitles
	//
	function SavePagesPHP(){
		global $gpmenu, $gptitles, $dataDir;
		
		$pages = array();
		$pages['gpmenu'] = $gpmenu;
		$pages['gptitles'] = $gptitles;
		
		if( !gpFiles::SaveArray($dataDir.'/data/_site/pages.php','pages',$pages) ){
			return false;
		}
		return true;
	}
	
	function SaveConfig(){
		global $config,$dataDir;
		return gpFiles::SaveArray($dataDir.'/data/_site/config.php','config',$config);
	}
	
	function MenuInsert($new_title,$after,$new_level){
		global $gpmenu;
		$new_menu = array();
		foreach($gpmenu as $gpmenu_title => $gpmenu_level){
			$new_menu[$gpmenu_title] = $gpmenu_level;
			if( $gpmenu_title == $after ){
				$new_menu[$new_title] = $new_level;
			}
		}
		$gpmenu = $new_menu;
	}
	
	function TitlesAdd($title,$type,$new=false){
		global $gptitles,$langmessage;
		
		$label = str_replace('_',' ',$title);
		
		$gptitles[$title]['label'] = $label;
		$gptitles[$title]['type'] = $type;
		
		if( $new ){
			//Put some default content in the pages directory
			$defaultContent = '<h2>'.$label.'</h2>';
			$defaultContent .= '<p>'.$langmessage['NEW_PAGE'].'</p>';
			
			if( !gpFiles::SaveTitle($title,$defaultContent,$type) ){
				message($langmessage['OOPS']);
				return false;
			}
		}
		return true;
	}
	
	//
	//		tidy
	//
	function tidyFix(&$text){
		
		
		if( !function_exists('tidy_parse_string') ){
			return;
		}
	
		$options = array();
		$options['wrap'] = 0;						//keeps tidy from wrapping... want the least amount of space changing as possible.. could get rid of spaces between words with the str_replaces below
		$options['doctype'] = 'omit';				//omit, auto, strict, transitional, user
		$options['drop-empty-paras'] = true;		//drop empty paragraphs
		$options['output-xhtml'] = true;			//need this so that <br> will be <br/> .. etc
		$options['show-body-only'] = true;
		
		
		//
		//	php4
		//
		if( function_exists('tidy_setopt') ){
			$options['char-encoding'] = 'utf8';
			admin_tools::tidyOptions($options);
			$tidy = tidy_parse_string($text);
			tidy_clean_repair();
			
			if( tidy_get_status() === 2){
				// 2 is magic number for fatal error
				// http://www.php.net/manual/en/function.tidy-get-status.php
				$tidyErrors[] = 'Tidy found serious XHTML errors: <br/>'.nl2br(wbHtmlspecialchars( tidy_get_error_buffer($tidy)));
				return;
			}
			$text = tidy_get_output();
		
		//	
		//	php5
		//
		}else{
			$tidy = tidy_parse_string($text,$options,'utf8');
			tidy_clean_repair($tidy);
			
			if( tidy_get_status($tidy) === 2){
				// 2 is magic number for fatal error
				// http://www.php.net/manual/en/function.tidy-get-status.php
				$tidyErrors[] = 'Tidy found serious XHTML errors: <br/>'.nl2br(wbHtmlspecialchars( tidy_get_error_buffer($tidy)));
				return;
			}
			$text = tidy_get_output($tidy);
		}
	}
	
	//for php4
	function tidyOptions($options){
		foreach($options as $key => $value){
			tidy_setopt($key,$value);
		}
	}
	
	
	
	//
	//	Add-Ons
	//
	
	
	function GetAddonLinks($installLink=true){
		global $langmessage, $config;
		
		echo '<ul>';
		
		
		$show =& $config['addons'];
		if( is_array($show) ){
			
			foreach($show as $addon => $info){
				
				//backwards compat
				if( is_string($info) ){
					$addonName = $info;
				}else{
					$addonName = $info['name'];
				}
				
				echo '<li class="expand_child">';
				
				$sublinks = admin_tools::GetAddonSubLinks_New('special',$addon);
				
				$sublinks .= admin_tools::GetAddonSubLinks('admin_links',$addon);
				$class = '';
				if( !empty($sublinks) ){
					$class = 'class="addonlinks"';
					$sublinks = '<ul>'.$sublinks.'</ul>';
				}
				
				echo common::Link('Admin_Addons',$addonName,'cmd=show&addon='.$addon,$class);
				echo $sublinks;
				
				echo '</li>';
			}
		}
		
		
		//Install Link
		if( $installLink ){
			echo '<li class="seperator">';
			echo common::Link('Admin_Addons',$langmessage['manage']);
			echo '</li>';
			
			if( admin_tools::CanBrowseAddons() ){
				echo '<li>';
				echo common::Link('Admin_Addons','Browse Addons','cmd=browse');
				echo '</li>';
			}
		}
		
		echo '</ul>';
		
	}
	
	function CanBrowseAddons(){
		static $bool;
		
		if( isset($bool) ){
			return $bool;
		}
		
		$bool = true;
		if( !defined('gptesting') ){
			$bool = false;
		}
		
		if( !ini_get('allow_url_fopen') ){
			$bool = false;
		}
		
		if( !function_exists('gzinflate') ){
			$bool = false;
		}
		
		if( defined('Browse_Addons') && Browse_Addons === false ){
			$bool = false;
		}
		
		return $bool;
	}
		
	
	/* this one only works for special links because it uses $gptitles
	 */
	function GetAddonSubLinks_New($type='special',$addon=false){
		global $gptitles;
		
		$count = 0;
		$result = '';
		foreach($gptitles as $linkName => $linkInfo){
			
			
			if( !isset($linkInfo['addon']) ){
				continue;
			}
			if( $linkInfo['type'] != $type ){
				continue;
			}
			
			if( $addon && ( $addon != $linkInfo['addon']) ){
				continue;
			}
			$result .= '<li>';
			$result .= common::Link($linkName,$linkInfo['label']);
			$result .= '</li>';
			$count++;
			
		}
		if( $count == 0 ){
			return '';
		}
		
		return $result;
		
	}
	
	
	/* this one only works for admin links because it uses $config
	 */
	function GetAddonSubLinks($type='admin_links',$addon=false){
		global $config;
		$links =& $config[$type];
		if( !is_array($links) ){
			return '';
		}
		
		$count = 0;
		$result = '';
		foreach($links as $linkName => $linkInfo){
			if( $addon && ( $addon != $linkInfo['addon']) ){
				continue;
			}
			$result .= '<li>';
			$result .= common::Link($linkName,$linkInfo['label']);
			$result .= '</li>';
			$count++;
		}
		if( $count == 0 ){
			return '';
		}
		
		return $result;
	}
	
	function GetAddonComponents($from,$addon){
		if( !is_array($from) ){
			return;
		}
		
		$result = array();
		foreach($from as $name => $value){
			if( !is_array($value) ){
				return;
			}
			if( !isset($value['addon']) ){
				return;
			}
			if( $value['addon'] !== $addon ){
				return;
			}
			$result[$name] = $value;
		}
		return $result;
		
	}


	
}



class Menu_Position{
	var $selected = false;
	var $to_position;
	var $to_level;
	
	function Menu_Position($array){
		global $langmessage, $gpmenu,$page;
		
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::getDir('/include/js/page_position.js').'"></script>';

		if( empty($array['to']) || (strpos($array['to'],':') === false) ){
			$array['to'] = 'hidden:';
		}
		
		list($this->to_position,$this->to_level) = explode(':',$array['to']);


		echo '<tr>';
			echo '<td class="formlabel">'.$langmessage['insert_at_position'].'</td>';
			echo '<td>';
			
			echo '<div class="new_page_position">';
			
			echo '<input type="hidden" name="to" value="" />';
			
			echo '<span>&nbsp;</span>'; //this is for IE, so that the absolute positioned .container displays
			echo '<div class="container">';
			
			$i = 0;
			foreach($gpmenu as $title => $level){
				echo '<div class="row">';
				$this->NewPageSubForm_Level($level == 0,$title,$i,0);
				$this->NewPageSubForm_Level($level == 1,$title,$i,1);
				$this->NewPageSubForm_Level($level == 2,$title,$i,2);
				echo '</div>';
				$i++;
			}
			
			//empty row at the end
			echo '<div class="row">';
			$this->NewPageSubForm_Level(false,'',$i,0);
			$this->NewPageSubForm_Level(false,'',$i,1);
			$this->NewPageSubForm_Level(false,'',$i,2);
			echo '</div>';
			
			echo '<div class="row">';
			$this->NewPageSubForm_Level(true,'hidden','hidden',0,true);
			echo '</div>';
			
			
			echo '</div>'; //end of .container
			echo '</div>'; //end .new_page_position
			
			echo '</td>';
		
		echo '</tr>';
			
	}
		

	
	function NewPageSubForm_Level($display,$title,$position,$level,$selected = false){
		global $langmessage;
		
		$class = 'cell';
		
		if( ($position == $this->to_position) && ($level == $this->to_level) && !$this->selected ){
			$this->selected = true;
			$class .= ' selected';
		}elseif( $selected && !$this->selected ){
			$class .= ' selected';
		}
		if( $position === 'hidden' ){
			$class .= ' hidden';
		}
		
		echo '<a rel="'.$position.':'.$level.'" class="'.$class.'">';
		if( $display === false ){
			echo '&nbsp;';
		}else{
			echo common::GetLabel($title);
		}
		echo '</a>';
	}
	
	
	//for handling data from the menu_position form
	function PutTitle($title,$to){
		global $gpmenu;
		
		
		if( empty($to) || (strpos($to,':') === false) ){
			return;
		}
		
		list($to_key,$new_level) = explode(':',$to);
		if( $to_key === 'hidden' ){
			return;
		}
		
		$titles = array_keys($gpmenu);
		
		//adjust to_key
		if( isset($titles[$to_key]) ){
			$holder_title = $titles[$to_key];
			$holder_level = $gpmenu[$holder_title];
			if( $new_level > $holder_level ){
				$to_key++;
			}
		}

		//place in $titles
		array_splice($titles,$to_key,0,$title);
		
		//rebuild gpmenu
		$newmenu = array();
		foreach($titles as $title_key){
			if( $title_key == $title ){
				$newmenu[$title] = $new_level;
			}else{
				$newmenu[$title_key] = $gpmenu[$title_key];
			}
		}
		
		$gpmenu = $newmenu;	
	}	
	
		
}
