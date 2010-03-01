<?php
defined("is_running") or die("Not an entry point...");


includeFile('admin/admin_uploaded.php');


class admin_browser extends admin_uploaded{
	
	function admin_browser(){
		$this->Init();
		$this->Standalone();

	}
	function Standalone(){
		global $page;
		$this->browseString = 'Admin_Browser';
		
		$_REQUEST['gpreq'] = 'body'; //force showing only the body as a complete html document
		$this->PrepHead();
		$this->AdminCommands();
		
		$this->ShowPanel();
		$this->ShowFolder();

	}
	
	function PrepHead(){
		global $config,$page;
		common::AddColorBox();
		
		$page->head .= '<script src="'.common::getDir('/include/js/browser.js').'" type="text/javascript"></script>';
		$page->head .= '<style>';
		$page->head .= 'html,body{padding:0;margin:0;background-color:#fff !important;}';
		$page->head .= '.browser_list{padding:20px;}';
		$page->head .= '</style>';
	}
	
	function Link_Img($file,$full_path){
		
		if( !$this->isThumbDir ){
			echo ' <img src="'.common::getDir('/data/_uploaded/image/thumbnails'.$this->subdir.'/'.$file.'.jpg').'" height="100" width="100" />';
		}else{
			echo ' <img src="'.$full_path.'" height="100" width="100" />';
		}
		
	}
	
	function Link_Select($fileUrl){
		echo '<a href="'.$fileUrl.'" class="select ck_select" style="display:none">';
		echo '<input type="hidden" name="fileUrl" value="'.htmlspecialchars($fileUrl).'" />';
		echo 'Select';
		echo '</a>';
	}
	
	function Link_View($fileUrl,$is_img){
		global $langmessage;
		if( $is_img ){
			
			echo '<a href="'.$fileUrl.'" name="gallery" rel="gallery_uploaded">';
			echo $langmessage['view_file'];
			echo '</a>';
			
		}else{
			
			echo '<a href="'.$fileUrl.'" target="_blank">';
			echo $langmessage['view_file'];
			echo '</a>';
		}
		
	}	
	
	
	
}
