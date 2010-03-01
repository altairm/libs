<?php
defined("is_running") or die("Not an entry point...");

class admin_trash{

	function admin_trash(){
		
		
		$cmd = common::GetCommand();
		switch($cmd){
			
			/* trash */
			case 'delete':
				$this->DeleteFromTrash();
				$this->Trash();
			break;
			
		
			case 'continue_restore':
				if( $this->RestoreTitleContinue() ){
					break;
				}
			case 'restore';
				$this->RestoreTitle();
			break;
			
			default:
				$this->Trash();
			break;
				
		}
	}
	
	
	function Trash(){
		global $dataDir,$langmessage;
		
		
		echo '<h2>'.$langmessage['trash'].'</h2>';
		
		$trash_dir = $dataDir.'/data/_trash';
		$trashtitles = gpFiles::ReadDir($trash_dir);
		asort($trashtitles);
		
		if( count($trashtitles) == 0 ){
			echo '<ul><li>'.$langmessage['TRASH_IS_EMPTY'].'</li></ul>';
			return false;
		}
		
		echo '<table class="bordered">';
		echo '<tr>';
		echo '<th>'.$langmessage['title'].'</th>';
		echo '<th>'.$langmessage['options'].'</th>';
		echo '</tr>';
		
		foreach($trashtitles as $title){
			echo '<tr>';
			echo '<td>';
			echo str_replace('_',' ',$title);
			echo '</td>';
			echo '<td>';
			echo common::Link('Admin_Trash',$langmessage['restore'],'cmd=restore&title='.urlencode($title));
			echo ' - ';
			echo common::Link('Admin_Trash',$langmessage['delete'],'cmd=delete&title='.urlencode($title));
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}	
	

	
	function DeleteFromTrash(){
		global $dataDir,$langmessage;
		
		$title = gpFiles::CleanTitle($_GET['title']);

		$trash_file = $dataDir.'/data/_trash/'.$title.'.php';
		if( !file_exists($trash_file) ){
			message($langmessage['OOPS'].' (D1)');
			return;
		}
		
		if( !unlink($trash_file) ){
			message($langmessage['OOPS'].' (D2)');
			return;
		}
		
		message($langmessage['file_deleted']);
	}
	
	
	function RestoreTitle(){
		global $dataDir,$langmessage,$gptitles;
		
		if( isset($_POST['restore_title']) ){
			$title = gpFiles::CleanTitle($_POST['restore_title']);
			$values = $_POST;
		}else{
			$title = gpFiles::CleanTitle($_GET['title']);
			$values = array('insert_after'=>'','menu_level'=>'');
		}
		

		$trash_file = $dataDir.'/data/_trash/'.$title.'.php';
		if( !file_exists($trash_file) ){
			message($langmessage['OOPS'].' (R1)');
			return;
		}
		
		$num = 1;
		$origTitle = $title;
		$label = $origLabel = str_replace('_',' ',$title);
		while( isset($gptitles[$title]) ){
			$title = $origTitle . ' '.$num;
			$label = $origLabel . ' '.$num;
			$num++;
		}
		
		
		//show form
		echo '<form class="newpageform" action="'.common::getUrl('Admin_Trash').'" method="post">';
		echo '<input type="hidden" name="restore_title" value="'.gpFiles::CleanTitle($origTitle).'" />';
		echo '<h2>'.$langmessage['restore'].' > '.$origLabel.'</h2>';
		echo '<table class="bordered">';
		
			
		echo '<tr>';
			echo '<td class="formlabel">'.$langmessage['restore_as'].'</td>';
			echo '<td>';
			echo '<input type="text" name="title" maxlength="80" value="'.htmlspecialchars($label).'" />';
			echo '</td>';
			echo '</tr>';	
			
		new Menu_Position($values);

			
		echo '<tr>';
			echo '<td colspan="2" class="formlabel">';
			echo '<input type="hidden" name="cmd" value="continue_restore" />';
			echo '<input type="submit" name="aaa" value="'.$langmessage['continue'].'" /> ';
			echo '<input type="submit" name="cmd" value="'.$langmessage['cancel'].'" /> ';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</form>';		
	}

		
	function RestoreTitleContinue(){
		global $langmessage,$dataDir;
		
		$title = admin_tools::CheckPostedNewPage();
		if( $title == false ){
			return false;
		}
		
		$restore_title = gpFiles::CleanTitle($_POST['restore_title']); //just in case

		$trash_file = $dataDir.'/data/_trash/'.$restore_title.'.php';
		if( !file_exists($trash_file) ){
			message($langmessage['OOPS'].' (R2)');
			return false;
		}
		
		//get file_type from file contents
		$file_type = $this->GetFileType($trash_file);
		
		//move the file from the trash
		if( !$this->MoveFromTrash($restore_title,$title) ){
			message($langmessage['OOPS'].' (R3)');
			return false;
		}
		
		//	Place title in Menu
		Menu_Position::PutTitle($title,$_POST['to']);
		
		//	Add to titles
		admin_tools::TitlesAdd($title,$file_type);
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS'].' (R4)');
			return false;
		}
		
		header('Location: '.common::getUrl($title,false));
		die();
	}
	
	function GetFileType($file){
		ob_start();
		include($file);
		$contents = common::get_clean();
		
		if( !isset($file_type) ){
			return 'page';
		}
		return $file_type;
	}
		
		
	
	function MoveFromTrash($trash_title,$new_title){
		global $dataDir;
		
		$trash_file = $dataDir.'/data/_trash/'.$trash_title.'.php';
		$new_file = $dataDir.'/data/_pages/'.$new_title.'.php';
		
		if( !file_exists($trash_file) ){
			return false;
		}
		
		if( !rename($trash_file,$new_file) ){
			return false;
		}
		return true;
	}	
}

