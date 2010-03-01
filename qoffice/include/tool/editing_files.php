<?php
defined("is_running") or die("Not an entry point...");


class file_editing{
	var $buffer = false;
	
	function file_editing(){
		global $page;
		$cmd = common::GetCommand();
		
		ob_start();
		switch($cmd){
			case 'edit':
				$this->edit();
			break;
			case 'save';
				$this->save();
			break;
			
			case 'continue_rename':
				if( $this->RenameContinue() ){
					break;
				}
			case 'rename':
				$this->rename();
			break;			
			
		}
		$page->contentBuffer = common::get_clean();
	}
	
	
	function RenameContinue(){
		global $langmessage, $gpmenu, $gptitles, $dataDir, $page;

		
		$new_title = gpFiles::CleanTitle($_POST['new_title']);
		if( isset($gptitles[$new_title]) ){
			message($langmessage['TITLE_EXISTS']);
			return false;
		}
		
		$oldTitle = $page->title;
		$oldTitles = $gptitles[$oldTitle];
		
		if( !isset($oldTitles['type']) ){
			$file_type = 'page';
		}else{
			$file_type = $oldTitles['type'];
		}
		
		
		//insert after.. then delete old
		admin_tools::MenuInsert($new_title,$oldTitle,$gpmenu[$oldTitle]);
		admin_tools::TitlesAdd($new_title,$file_type);
		unset($gpmenu[$oldTitle]);
		unset($gptitles[$oldTitle]);
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (N2)');
			return false;
		}		
		
		//rename the file
		$new_file = $dataDir.'/data/_pages/'.$new_title.'.php';
		$old_file = $page->file;
		
		if( !rename($old_file,$new_file) ){
			message($langmessage['OOPS'].' (N2)');
			return false;
		}
		
		
		
		$page->file = $new_file;
		$page->title = $new_title;
		$page->label = str_replace('_',' ',$new_title);
		message($langmessage['RENAMED']);
		return true;
	}
	
	
	function rename(){
		global $langmessage,$config,$page;
		
		$_POST += array('new_title'=>$page->label);
		
		echo '<div id="admincontent">';
		echo '<form class="renameform" action="'.common::getUrl($page->title).'" method="post">';
		echo '<h2>'.$langmessage['rename'].'</h2>';
		echo '<table>';
		echo '<tr>';
			echo '<th>';
			echo $langmessage['from'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['to'];
			echo '</th>';
			echo '</tr>';

		echo '<tr>';
			echo '<td>';
			echo $page->label;
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="new_title" value="'.htmlspecialchars($_POST['new_title']).'" />';
			echo ' <input type="hidden" name="cmd" value="continue_rename" />';
			echo '<input type="submit" name="aaa" value="'.$langmessage['rename'].'" />';
			echo '</td>';
			echo '</tr>';
			
		echo '</table>';
		
		echo '</form>';
		echo '</div>';
	}
	
	
	function save(){
		global $langmessage,$page,$gptitles;

		$text =& $_POST['gpcontent'];
		gpFiles::rmPHP($text);
		admin_tools::tidyFix($text);
		
		
/*		Get most comment words to add to page keywords.. 
		there's an issue of sifting out words like "the" in multiple languages
		if( function_exists('str_word_count') ){
			$words = strip_tags($text);
			$words = str_word_count($words,1);
			$words = array_count_values($words);
			arsort($words);
			foreach($words as $word => $count){
				if( strlen($word) < 4 ){
					continue;
				}
				message('maybe: '.$word);
			}
			message(showArray($words));
		}
*/
		
		
		
		if( gpFiles::SaveTitle($page->title,$text,$page->fileType) ){
			message($langmessage['SAVED']);
			return;
		}
		
		message($langmessage['OOPS']);
		$this->edit();
	}
		
	function edit(){
		global $langmessage,$page,$gptitles;
		
		if( $page->fileType == 'gallery' ){
			includeFile('/tool/editing_gallery.php');
			new editing_gallery();
		}else{
			$this->edit_page();
		}
		
		echo '<h2 style="margin-top:2em">'.$langmessage['options'].'</h2>';
		echo '<ul>';
			echo '<li>';
			echo common::Link($page->title,$langmessage['rename'],'cmd=rename');
			echo '</li>';
			echo '</ul>';
	}
	
	function edit_page(){
		global $page,$langmessage;
		
		echo '<form action="'.common::getUrl($page->title).'" method="post">';
		echo '<input type="hidden" name="cmd" value="save" />';
		
		ob_start();
		include($page->file);
		$contents = common::get_clean();
		common::UseFCK( $contents );
		
		echo '<input type="submit" name="" value="'.$langmessage['save'].'" />';
		echo '<input type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		echo '</form>';
	}
	
	
}
