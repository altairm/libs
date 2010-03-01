<?php
defined("is_running") or die("Not an entry point...");

class admin_menu{
	
	var $themeArray;
	
	
	function admin_menu(){
		global $langmessage,$page;
		
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::getDir('/include/js/dragdrop.js').'"></script>';
		$page->head .= '<link rel="stylesheet" type="text/css" href="'.common::getDir('/include/css/admin_themes.css').'" />';
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::getDir('/include/js/admin_menu.js').'"></script>';
		
		$img = '<img src="'.common::GetDir('/include/imgs/page_add.png').'" height="16" width="16" style="float:left" alt=""/>';
		

		$cmd = common::GetCommand();
		switch($cmd){
			
			case 'renameform':
				$this->RenameForm(); //will die()
			return;
			
			case 'new':
				$this->InsertFileForm(); //will die()
			return;
			
			case 'insert_at':
				$this->InsertAt();
			break;
			
			case 'insert_before':
			case 'insert_after':
				$this->InsertNewFile($cmd);
			break;
			
			case 'drag':
				$this->Drag();
			break;
			case 'dragadd':
				$this->DragAdd();
			break;
			
			case 'hide':
				$this->Hide();
			break;


			case 'rename':
				$this->RenameFile();
			break;
			
			case 'trash':
				$this->MoveToTrash();
			break;
			
			case 'theme':
				$this->SelectTheme();
			return;
			case 'settheme':
				$this->SetTheme();
			break;
			
			case 'restoretheme';
				$this->RestoreTheme();
			break;
			
		}
		
		echo '<h2>'.$langmessage['file_manager'].'</h2>';
		
		$this->ShowForm();
		
		if( isset($_REQUEST['gpreq']) && ($_REQUEST['gpreq'] == 'json') && isset($_GET['menus']) ){
			$this->PrepJSON();
		}
	}
	
	
	//we do the json here because we're replacing more than just the content
	function PrepJson(){
		global $page,$gpOutConf;
		
		
		
		foreach($_GET['menus'] as $id => $menu){
			if( !isset($gpOutConf[$menu]) ){
				continue;
			}
			if( !isset($gpOutConf[$menu]['link']) ){
				continue;
			}
				
			$array = array();
			$array[0] = 'replacemenu';
			$array[1] = '#'.$id;
			
			$method = $gpOutConf[$menu]['method'];

			ob_start();
			call_user_func($method);
			$array[2] = common::get_clean();
			
			$page->ajaxReplace[] = $array;
		}
	}

	
	function SelectTheme(){
		global $gptitles,$gpmenu,$langmessage;
		
		$title =& $_GET['title'];
		if( !isset($gptitles[$title]) ){
			echo $langmessage['OOPS'];
			return;
		}
		
		//the current theme will be either the configuration default, 
		$currentTheme = display::OrConfig($title,'theme');
		
		//show which files will be affected
		$temp = $gpmenu;
		$result = array();
		reset($temp);
		$i = 0;
		do{
			$menuTitle = key($temp);
			$level = current($temp);
			unset($temp[$menuTitle]);
			if( $title === $menuTitle ){
				$this->InheritingTheme($level+1,$temp,$result);
			}
			$i++;
		}while( (count($temp) > 0) );

		echo '<p>';
		echo '<h3>'.$langmessage['affected_files'].'</h3>';
		echo str_replace('_',' ',$title);
		foreach($result as $tempTitle => $level){
			echo ', &nbsp; '.str_replace('_',' ',$tempTitle);
		}
		echo '</p>';
		
		includeFile('admin/admin_theme.php');
		admin_theme::ShowThemes($currentTheme,'Admin_Menu','cmd=settheme&title='.$title,'gpajax'); //clicking from within the colorbox seems to mess this up
		
	}
	
	
	function InheritingTheme($searchLevel,&$menu,&$result){
		global $gptitles;
		
		$children = true;
		do{
			$menuTitle = key($menu);
			$level = current($menu);
			
			if( $level < $searchLevel ){
				return;
			}
			if( $level < $searchLevel ){
				return;
			}
			if( $level > $searchLevel ){
				if( $children ){
					$this->InheritingTheme($level,$menu,$result);
				}else{
					unset($menu[$menuTitle]);
				}
				continue;
			}
			
			unset($menu[$menuTitle]);
			if( !empty($gptitles[$menuTitle]['theme']) ){
				$children = false;
				continue;
			}
			$children = true;
			$result[$menuTitle] = $level;
		}while( count($menu) > 0 );
			
	}
	
	
	
	function RestoreTheme(){
		global $gptitles,$langmessage;
		$title = $_GET['title'];
		if( !isset($gptitles[$title]) ){
			message($langmessage['OOPS']);
			return;
		}
		unset($gptitles[$title]['theme']);
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			return false;
		}
		message($langmessage['SAVED']);
	}	

	function SetTheme(){
		global $gptitles,$langmessage;
		
		includeFile('admin/admin_theme.php');
		
		$theme =& $_GET['theme'];
		if( !admin_theme::IsAvailable($_GET['theme']) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$title =& $_GET['title'];
		if( !isset($gptitles[$title]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		//unset, then reset if needed
		unset($gptitles[$title]['theme']);
		$currentTheme = display::OrConfig($title,'theme');
		if( $currentTheme != $theme ){
			$gptitles[$title]['theme'] = $theme;
		}
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			return false;
		}
		message($langmessage['SAVED']);
	}
	
	
	//move files to the trash
	//just hide special pages
	function MoveToTrash(){
		global $gpmenu,$gptitles,$langmessage;
		
		if( count($gpmenu) == 1 ){
			message($langmessage['OOPS'].' (M0)');
			return;
		}
		
		
		$title = $this->GetTitle();
		if( !$title ){
			return false;
		}
		$type = common::PageType($title);
		
		if( $type == 'special' ){
			$this->Hide();
			return;
		}
		
		
		unset($gpmenu[$title]);
		unset($gptitles[$title]);
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (M2)');
			return false;
		}
			
		if( !$this->MoveToTrash2($title) ){
			message($langmessage['OOPS'].' (M3)');
			return false;
		}
		
		message($langmessage['MOVED_TO_TRASH']);
		
		return true;
	}

	
	function MoveToTrash2($title){
		global $dataDir;
		$source_file = $dataDir.'/data/_pages/'.$title.'.php';
		$trash_dir = $dataDir.'/data/_trash';
		$trash_file = $trash_dir.'/'.$title.'.php';
		
		
		if( !file_exists($source_file) ){
			return false;
		}
		
		gpFiles::CheckDir($trash_dir);
		
		if( file_exists($trash_file) ){
			unlink($trash_file);
		}
		
		if( !rename($source_file,$trash_file) ){
			return false;
		}
		return true;
	}		
	
	
	function RenameFile(){
		global $langmessage, $gpmenu, $gptitles, $dataDir, $page;
		
		
		$new_title = admin_tools::CheckPostedNewPage($_POST['new_title']);
		if( $new_title === false ){
			return false;
		}
		
		$old_title = $this->GetTitle();
		if( $old_title === false ){
			//message($langmessage['OOPS']);
			return false;
		}
		
		if( isset($gptitles[$old_title]['type']) ){
			$file_type = $gptitles[$old_title]['type'];
		}else{
			$file_type = 'page';
		}

		
		//insert after.. then delete old
		admin_tools::MenuInsert($new_title,$old_title,$gpmenu[$old_title]);
		admin_tools::TitlesAdd($new_title,$file_type);
		unset($gpmenu[$old_title]);
		unset($gptitles[$old_title]);
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (N2)');
			return false;
		}		
		
		//rename the file
		$new_file = $dataDir.'/data/_pages/'.$new_title.'.php';
		$old_file = $dataDir.'/data/_pages/'.$old_title.'.php';
		
		if( !rename($old_file,$new_file) ){
			message($langmessage['OOPS'].' (N3)');
			return false;
		}
		
		
		//galleries
		if( $file_type == 'gallery'){
			includeFile('special/special_galleries.php');
			special_galleries::RenameGallery($old_title,$new_title);
		}		
		
		
		return true;
	}
	
	function InsertAt(){
		global $gpmenu,$langmessage;
		
		$title = admin_tools::CheckPostedNewPage();
		if( $title === false ){
			return false;
		}
		
		if( !admin_tools::TitlesAdd($title,$_POST['file_type'],true) ){
			return false;
		}
		
		$oldmenu = $gpmenu;
		
		Menu_Position::PutTitle($title,$_POST['insert_position']);
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$gpmenu = $oldmenu;
		}
		
	}
	
	function InsertNewFile($cmd){
		global $gpmenu,$langmessage;
		
		$title = admin_tools::CheckPostedNewPage();
		if( $title === false ){
			return false;
		}
		
		$insert_position = $_POST['insert_position'];
		if( !isset($gpmenu[$insert_position]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		//add to $gptitles and create a default file
		if( !admin_tools::TitlesAdd($title,$_POST['file_type'],true) ){
			return false;
		}
		
		
		//put the title in the menu
		$titles = array_keys($gpmenu);
		$insert_key = array_search($insert_position,$titles);
		$insert_level = $gpmenu[$insert_position];

		
		switch($cmd){
			
			case 'insert_before':
				array_splice($titles,$insert_key,0,$title);
			break;
			case 'insert_after':
				array_splice($titles,$insert_key+1,0,$title);
			break;
		}
		
		//rebuild gpmenu
		$oldmenu = $gpmenu;
		$newmenu = array();
		foreach($titles as $title_key){
			if( $title_key === $title ){
				$newmenu[$title_key] = $insert_level;
			}else{
				$newmenu[$title_key] = $gpmenu[$title_key];
			}
		}
		$gpmenu = $newmenu;
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$gpmenu = $oldmenu;
			return false;
		}
	}
	


	
	function DragAdd(){
		global $gpmenu,$langmessage;
		
		
		$title = $this->GetTitle();
		if( !$title ){
			return;
		}
		
		//old data in case the save doesn't work
		$oldmenu = $gpmenu;
		
		Menu_Position::PutTitle($title,$_GET['to']);
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$gpmenu = $oldmenu;
		}
		
	}
	
	
	function Drag(){
		global $gpmenu,$langmessage;
		
		$title = $this->GetTitle();
		if( !$title ){
			return;
		}
		reset($gpmenu);
		$titles = array_keys($gpmenu);
		
		//get from
		$from_key = $_GET['from'];
		if( !isset( $titles[$from_key]) || ($titles[$from_key] != $title) ){
			message($langmessage['OOPS']);
			return;
		}
		
		$oldmenu = $gpmenu;
		
		//get info
		list($to_key,$new_level) = explode(':',$_GET['to']);
		
		if( isset($titles[$to_key]) ){
			$holder_title = $titles[$to_key];
			$holder_level = $gpmenu[$holder_title];
			
			//adjust to_key 
			if( ($from_key !== $to_key) ){
				
				if( ($new_level > $holder_level) && ($from_key > $to_key) ){
					$to_key++;
					
				}elseif( ($new_level < $holder_level) && ($from_key < $to_key) ){
					$to_key--;
				}
				
			}
		}
		
		//only move if needed
		if( $from_key !== $to_key ){
			
			//remove at old spot
			array_splice($titles,$from_key,1);
			
			//put in new spot
			array_splice($titles,$to_key,0,$title);
			
			
			//rebuild
			$newmenu = array();
			foreach($titles as $title_key){
				$newmenu[$title_key] = $gpmenu[$title_key];
			}
			$gpmenu = $newmenu;			
			
		}
		
		//set the new level
		$gpmenu[$title] = $new_level;
		
		//message('<table><tr><td>'.showArray($gpmenu).'</td><td>'.showArray($newmenu).'</td></tr></table>');

		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$gpmenu = $oldmenu;
		}
	}
	
	function Hide(){
		global $gpmenu,$langmessage,$gptitles;
		
		$title = $_GET['title'];
		if( !isset($gpmenu[$title]) ){
			message($langmessage['OOPS']);
			return;
		}
		
		$oldmenu = $gpmenu;
		
		unset($gpmenu[$title]);
		
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$gpmenu = $oldmenu;
		}else{
			//message($langmessage['SAVED']);
		}		
		
		
	}
	
	
	function GetTitle(){
		global $gptitles,$langmessage;
		
		//not using request so that it's compat with creq
		if( isset($_POST['title']) ){
			$title = $_POST['title'];
		}elseif( isset($_GET['title']) ){
			$title = $_GET['title'];
		}else{
			message($langmessage['OOPS'].'(0)');
			return false;
		}
		
		$title = gpFiles::CleanTitle($title);
		
		if( !isset($gptitles[$title]) ){
			message($langmessage['OOPS'].'(1)');
			return false;
		}
		return $title;
	}
	
	function SetThemeArray(){
		global $gpmenu,$config,$gptitles;
		
		$titleThemes = array();
		$customThemes = array(0=>false,1=>false,2=>false);		
		$customThemeLevel = 0;
		foreach($gpmenu as $title => $level){
			
			//reset theme inheritance
			for( $i = $level; $i <= 2; $i++){
				$customThemes[$i] = false;
			}
			
			if( !empty($gptitles[$title]['theme']) ){
				$titleThemes[$title] = $gptitles[$title]['theme'];
				
				
			}elseif( isset($customThemes[($level-1)]) && ($customThemes[($level-1)] !== false) ){
				$titleThemes[$title] = $customThemes[($level-1)];
				
			}else{
				$titleThemes[$title] = $config['theme'];
			}
			$customThemes[$level] = $titleThemes[$title];
		}
		$this->ThemeArray = $titleThemes;
	}
	
	
	function ShowForm(){
		global $gpmenu, $gptitles, $langmessage;
		
		echo '<div id="menuconfig">';
		
		echo '<ul class="draggable_droparea">';
		
		$this->SetThemeArray();
		
		//get theme
		//$menutitles = array_keys($gpmenu);
		//message(showArray($menutitles));
		//message(showArray($gptitles));
		
		
		$i = 0;
		$prevlevel = 0;
		$currentTheme = false;
		foreach($gpmenu as $title => $level){
			
			$this->ShowLevel($level == 0,$title,$i,0);
			$this->ShowLevel($level == 1,$title,$i,1);
			$this->ShowLevel($level == 2,$title,$i,2);
			$i++;
			$prevlevel = $level;
		}
		
		$this->ShowLevel(false,false,$i,0);
		$this->ShowLevel(false,false,$i,1);
		$this->ShowLevel(false,false,$i,2);

		echo '<li style="clear:both" class="draggable_nodrop"></li>';
		echo '</ul>';
		
		$this->ShowHidden();
		echo '<div style="clear:both;"></div>';
		
		echo '</div>';
		
	}
	
	function RenameForm(){
		global $langmessage;
		
		$title =& $_REQUEST['title'];
		$new_title =& $_REQUEST['new_title'];
		
		if( empty($new_title) ){
			$new_title = $title;
		}
		$title = str_replace('_',' ',$title);
		$new_title = str_replace('_',' ',$new_title);
		

		echo '<div class="inline_box">';
		echo '<form class="newpageform" action="'.common::getUrl('Admin_Menu').'" method="post">';
		echo '<h2>'.$langmessage['rename'].'</h2>';
		echo '<table>';
			
		echo '<tr>';
			echo '<td class="formlabel">'.$langmessage['from'].'</td>';
			echo '<td>';
			echo '<input type="text" name="title" maxlength="80" value="'.htmlspecialchars($title).'" readonly="readonly" />';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td class="formlabel">'.$langmessage['to'].'</td>';
			echo '<td>';
			echo '<input type="text" name="new_title" maxlength="80" value="'.htmlspecialchars($new_title).'" />';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td></td>';
			echo '<td>';
			echo '<input type="hidden" name="cmd" value="rename" />';
			echo '<input type="submit" name="aaa" value="'.$langmessage['continue'].'" class="menupost"/> ';
			echo '</td>';
			echo '</tr>';			
			
		echo '</table>';
		echo '</form>';
		echo '</div>';
	}
	
	function InsertFileForm(){
		global $langmessage;
		
		
		$cmd =& $_REQUEST['relation'];
		$position =& $_REQUEST['insert_position'];
		$title =& $_REQUEST['title'];
		$title = str_replace('_',' ',$title);
		
		echo '<div class="inline_box">';
		echo '<form class="newpageform" action="'.common::getUrl('Admin_Menu').'" method="post">';
		echo '<h2>'.$langmessage['new_file'].'</h2>';
		echo '<table>';
		
		echo '<tr>';
			echo '<td class="formlabel">'.$langmessage['file_name'].'</td>';
			echo '<td>';
			echo '<input type="text" name="title" maxlength="80" value="'.gpFiles::CleanTitle($title).'" />';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td class="formlabel">'.$langmessage['file_type'].'</td>';
			echo '<td>';
			echo '<label>';
			echo '<input type="radio" name="file_type" value="page" checked="checked" />';
			echo 'Page';
			echo '</label>';
			echo '<label>';
			echo '<input type="radio" name="file_type" value="gallery" />';
			echo 'Gallery';
			echo '</label>';
			echo '</td>';
			echo '</tr>';
					
		echo '<tr>';
			echo '<td></td>';
			echo '<td>';
			echo '<input type="hidden" name="cmd" value="'.htmlspecialchars($cmd).'" />';
			echo '<input type="hidden" name="insert_position" value="'.htmlspecialchars($position).'" />';
			echo '<input type="submit" name="aaa" value="'.$langmessage['continue'].'" class="menupost"/> ';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</form>';
		echo '</div>';
	}
		

	function ShowLevel($display,$title,$position,$level){
		global $gpmenu, $langmessage,$gptitles;
		
		
		//place holders
		$class = 'level simple_top expand_child';

		if( $level == 0 ){
			$class .= ' clear ';
		}
		if( $title === false ){
			$title = '';
			$class .= ' last';
		}
		
		
		if( $display === false ){
			$class .= ' hidden_element';
			echo '<li class="'.$class.'">';
				echo common::Link('Admin_Menu',$position.':'.$level,'',' style="display:none" ');
				$this->NewFileLink($position.':'.$level);
			echo '</li>';
			return;
		}
		
			$isSpecialLink = common::SpecialOrAdmin($title);
			$class .= ' draggable_element ';
			echo '<li class="'.$class.'">';
				echo common::Link('Admin_Menu',$position.':'.$level,'title='.urlencode($title).'&cmd=drag&from='.$position.'&to=%s',' name="withmenu" style="display:none" ');
				

					//hidden options
					echo '<ul>';
					
						echo '<li>';
						$label = '<img src="'.common::GetDir('/include/imgs/page_white_text.png').'" height="16" width="16"  alt=""/>';
						echo common::Link($title,$label.$langmessage['view_file']);
						echo '</li>';
						
						//rename
						if( $isSpecialLink === false ){
							$label = '<img src="'.common::GetDir('/include/imgs/page_edit.png').'" alt="" height="16" width="16" />';
							echo '<li>';
							echo common::Link('Admin_Menu',$label.$langmessage['rename'],'cmd=renameform&title='.urlencode($title),' title="'.$langmessage['rename'].'" name="ajax_box" ');
							echo '</li>';
						}
				
						//insert above/below...
						echo '<li class="expand_child expand_right">';
						
								
							echo '<a href="javascript:void(0)">';
							echo '<img src="'.common::GetDir('/include/imgs/page_add.png').'" height="16" width="16"  alt=""/>';
							echo $langmessage['new_file'];
							echo '</a>';
						
							//expand right area
							echo '<ul>';
							
								//insert before
								echo '<li>';
								$label = '<img src="'.common::GetDir('/include/imgs/insert_before.png').'" alt="" height="15" width="16" /> ';
								echo common::Link('Admin_Menu',$label.$langmessage['insert_before'],'cmd=new&relation=insert_before&insert_position='.urlencode($title),' title="'.$langmessage['insert_before'].'" name="ajax_box" ');
								echo '</li>';
								

								//insert after
								echo '<li>';
								$label = '<img src="'.common::GetDir('/include/imgs/insert_after.png').'" alt="" height="16" width="16" /> ';
								echo common::Link('Admin_Menu',$label.$langmessage['insert_after'],'cmd=new&relation=insert_after&insert_position='.urlencode($title),' title="'.$langmessage['insert_after'].'" name="ajax_box" ');
								echo '</li>';
							
							echo '</ul>';
						echo '</li>';

						//options
						echo '<li class="expand_child expand_right">';
							
							echo '<a href="javascript:void(0)">';
							echo '<img src="'.common::GetDir('/include/imgs/page_white_gear.png').'" height="16" width="16"  alt=""/>';
							echo $langmessage['options'];
							echo '</a>';
							
							echo '<ul>';
							
							
								//edit file
								if( $isSpecialLink === false ){
									echo '<li>';
									$label = '<img src="'.common::GetDir('/include/imgs/page_edit.png').'" height="16" width="16"  alt=""/>';
									echo common::Link($title,$label.$langmessage['edit_file'],'cmd=edit');
									echo '</li>';
								}
						
						
								//hide
								$label = '<img src="'.common::GetDir('/include/imgs/cut_list.png').'" alt="" height="16" width="16" />';
								echo '<li>';
								echo common::Link('Admin_Menu',$label.$langmessage['hide'],'cmd=hide&title='.urlencode($title),' title="'.$langmessage['hide'].'" name="withmenu" ');
								echo '</li>';
								
								
									
								//Inherited Theme
								if( empty($gptitles[$title]['theme']) ){
									$label = '<img src="'.common::GetDir('/include/imgs/page.png').'" alt="" height="16" width="16" />';
									echo '<li>';
									echo common::Link('Admin_Menu',$label.$langmessage['select_theme'],'cmd=theme&title='.urlencode($title),' title="'.$langmessage['theme'].'" name="ajax_box"');
									echo '</li>';
								}
									
									
								//trash
								if( $isSpecialLink === false ){
									$label = '<img src="'.common::GetDir('/include/imgs/bin.png').'" alt="" height="16" width="16" />';
									echo '<li>';
									echo common::Link('Admin_Menu',$label.$langmessage['delete'],'cmd=trash&title='.urlencode($title),' title="'.$langmessage['delete'].'" name="withmenu" ');
									echo '</li>';
								}
								

								
								
								
							echo '</ul>';
						echo '</li>';
						
						//theme
						
						if( !empty($gptitles[$title]['theme']) ){
						
							echo '<li class="expand_child expand_right">';
								echo '<img src="'.common::GetDir('/include/imgs/page.png').'" alt="" height="16" width="16" />';
								echo $langmessage['current_theme'];
							
								echo '<ul>';
									echo '<li>';
									echo '<img src="'.common::GetDir('/include/imgs/accept.png').'" alt="" height="16" width="16" />';
									echo $gptitles[$title]['theme'];
									echo '</li>';
									echo '<li>';
									$label = '<img src="'.common::GetDir('/include/imgs/page.png').'" alt="" height="16" width="16" />';
									echo common::Link('Admin_Menu',$label.$langmessage['select_theme'],'cmd=theme&title='.urlencode($title),' title="'.$langmessage['theme'].'" name="ajax_box"');
									echo '</li>';
									echo '<li>';
									$label = '<img src="'.common::GetDir('/include/imgs/arrow_undo.png').'" alt="" height="16" width="16" />';
									echo common::Link('Admin_Menu',$label.$langmessage['restore_defaults'],'cmd=restoretheme&title='.urlencode($title),' title="'.$langmessage['restore_defaults'].'" name="gpajax"');
									echo '</li>';
								echo '</ul>';
							echo '</li>';
						}else{
							
						}
						
					//end hidden options
					echo '</ul>';
					

				//options
				echo '<span class="options">';
				echo '<img src="'.common::GetDir('/include/imgs/arrow_out.png').'" alt="" height="16" width="16" />';
				echo '</span>';
					
				//link
				echo '<span class="label">';
				echo common::GetLabel($title);
				echo '</span>';
				
							
			echo '</li>';		
	}
	
	function GetAvailable(){
		global $gptitles, $gpmenu,$config;
		
		$intitles = array_keys($gptitles);
		$inmenu = array_keys($gpmenu);
		$avail = array_diff($intitles,$inmenu);
		foreach($avail as $key => $link){
			$linkInfo = $gptitles[$link];
			
			//don't allow admin
			if( isset($linkInfo['type']) && $linkInfo['type'] == 'admin' ){
				unset($avail[$key]);
			}
		}
		return $avail;
	}
	
	function ShowHidden(){
		global $gpmenu,$langmessage;
		
		$avail = $this->GetAvailable();
		
		$titles = array();
		$special = array();
		foreach($avail as $title){
			$type = common::SpecialOrAdmin($title);
			if( $type == false ){
				$titles[] = $title;
			}else{
				$special[] = $title;
			}
		}
		
		
		echo '<h2 style="margin-top:2em;" class="clear">'.$langmessage['hidden_pages'].'</h2>';
		echo '<table cellpadding="7">';
		
/*
		echo '<tr>';
			echo '<td>';
			echo '<b>';
			echo 'Static Files';
			echo '</b>';
			echo '</td>';
			echo '<td>';
			echo '<b>';
			echo 'Dynamic Files';
			echo '</b>';
			echo '</td>';
			echo '</tr>';
*/
		
		echo '<tr>';
		echo '<td>';
			echo '<ul>';
			$this->ShowLinkArray($titles);
			
				//add hidden file
				echo '<li class="level simple_top expand_child clear">';
				$this->NewFileLink('hidden');
				echo '</li>';
				
				//space
				echo '<li style="clear:both" class="draggable_nodrop"></li>';
				
			echo '</ul>';
		echo '</td>';
		echo '<td>';
		
			//echo '<h2 style="margin-top:2em;" class="clear">'.$langmessage['special_pages'].'</h2>';
			echo '<ul>';
			$this->ShowLinkArray($special);
			echo '</ul>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		
	}
	
	function NewFileLink($position){
		global $langmessage;
		echo '<div class="hidden_options">';
			//insert at
			$label = '<img src="'.common::GetDir('/include/imgs/page_add.png').'" alt="" height="15" width="16" /> ';
			echo common::Link('Admin_Menu',$label.$langmessage['new_file'],'cmd=new&relation=insert_at&insert_position='.urlencode($position),' title="'.$langmessage['new_file'].'" name="ajax_box" ');
		echo '</div>';
	}
	
	
	function ShowLinkArray($array){
		global $langmessage;
		
		foreach($array as $title){
			
			$type = common::SpecialOrAdmin($title);

			$class = 'level simple_top clear draggable_element expand_child';
			
			
			echo '<li class="'.$class.'">';
				//echo common::Link('Admin_Menu','','title='.urlencode($title).'&cmd=dragadd&to=%s',' name="creq" style="display:none" ');
				echo common::Link('Admin_Menu','','title='.urlencode($title).'&cmd=dragadd&to=%s',' name="withmenu" style="display:none" ');
				
				
				echo '<ul>';

					//view file
					echo '<li>';
					echo '<img src="'.common::GetDir('/include/imgs/page_white_text.png').'" height="16" width="16"  alt=""/>';
					echo common::Link($title,$langmessage['view_file']);
					echo '</li>';
						
				if( $type === false ){
					
						//rename
						$label = '<img src="'.common::GetDir('/include/imgs/page_edit.png').'" alt="" height="16" width="16" />';
						echo '<li>';
						echo common::Link('Admin_Menu',$label.$langmessage['rename'],'cmd=renameform&title='.urlencode($title),' title="'.$langmessage['rename'].'" name="ajax_box" ');
						echo '</li>';
						
						//move to trash
						$label = '<img src="'.common::GetDir('/include/imgs/bin.png').'" alt="" height="16" width="16" />';
						echo '<li>';
						echo common::Link('Admin_Menu',$label.$langmessage['delete'],'cmd=trash&title='.urlencode($title),' title="'.$langmessage['delete'].'" name="gpajax" ');
						echo '</li>';
				}
				
				echo '</ul>';

				
				//add
				echo '<span class="options">';
				echo '<img src="'.common::GetDir('/include/imgs/arrow_out.png').'" alt="Drag to Move" height="16" width="16" />';	
				echo '</span>';

				
				echo '<span class="label">';
				echo common::GetLabel($title);
				echo '</span>';
			
			echo '</li>';
		}	
	}
	
	
	
}

