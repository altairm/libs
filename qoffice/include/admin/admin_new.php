<?php
defined("is_running") or die("Not an entry point...");

class admin_new{
	
	function admin_new(){

		$cmd = common::GetCommand();
		
		switch($cmd){
			
			case 'continue_new';
				if( $this->NewPageContinue() ){
					break;
				}
				
			default;
			case 'newpage':
				$this->NewPageForm();
			break;
		}
	}


	function NewPageContinue(){
		global $langmessage,$gpmenu;
		
		//old data in case the save doesn't work
		$oldmenu = $gpmenu;

		$title = admin_tools::CheckPostedNewPage();
		if( $title === false ){
			return false;
		}


		if( !admin_tools::TitlesAdd($title,$_POST['file_type'],true) ){
			return false;
		}

		//place in menu
		Menu_Position::PutTitle($title,$_POST['to']);
		
		
		if( !admin_tools::SavePagesPHP() ){
			message($langmessage['OOPS']);
			$gpmenu = $oldmenu;
			return false;
		}
		
		header('Location: '.common::getUrl($title,'cmd=edit',false));

		return true;
	}
	
	
	function NewPageForm(){
		global $langmessage;
		
		$title = '';
		if( isset($_POST['title']) ){
			$title = $_POST['title'];
		}elseif( isset($_GET['title']) ){
			$title = str_replace('_',' ',$_GET['title']);
		}
		$_POST += array('title'=>$title,'insert_after'=>'','menu_level'=>'');
		
		echo '<form class="newpageform" action="'.common::getUrl('Admin_New').'" method="post">';
		echo '<h2>'.$langmessage['new_file'].'</h2>';
		echo '<table class="bordered">';
		
		echo '<tr>';
			echo '<td class="formlabel">'.$langmessage['file_name'].'</td>';
			echo '<td>';
			echo '<input type="text" name="title" maxlength="80" value="'.gpFiles::CleanTitle($_POST['title']).'" />';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td class="formlabel">'.$langmessage['file_type'].'</td>';
			echo '<td>';
			echo '<select name="file_type">';
			echo '<option value="page">Page</option>';
			echo '<option value="gallery">Gallery</option>';
			echo '</select>';
			echo '</td>';
			echo '</tr>';
					
		new Menu_Position($_POST);
			
		echo '<tr>';
			echo '<td></td>';
			echo '<td>';
			echo '<input type="hidden" name="cmd" value="continue_new" />';
			echo '<input type="submit" name="aaa" value="'.$langmessage['continue'].'" /> ';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</form>';
	}	


	
}
