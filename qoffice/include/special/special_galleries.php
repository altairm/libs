<?php
defined('is_running') or die('Not an entry point...');


class special_galleries{
	var $galleries = array();
	
	function special_galleries(){
		
		$this->galleries = special_galleries::GetData();
		$this->GenerateOutput();
	}
	
	//get gallery index
	function GetData(){
		global $dataDir;
		
		$file = $dataDir.'/data/_site/galleries.php';
		if( file_exists($file) ){
			
			require($file);
			return $galleries;
		}else{
			return special_galleries::DataFromFiles();
		}
	}

	
	function GenerateOutput(){
		global $langmessage,$gptitles;
		
		
		echo '<h2>';
		gpOutput::GetText('galleries');
		echo '</h2>';


		echo '<ul class="gp_gallery">';
		foreach($this->galleries as $title => $info ){
			echo '<li style="clear:both">';
			
			$count = '';
			if( is_array($info) ){
				$icon = $info['icon'];
				if( $info['count'] == 1 ){
					$count = $info['count'].' '.gpOutput::ReturnText('image');
				}elseif( $info['count'] > 1 ){
					$count = $info['count'].' '.gpOutput::ReturnText('images');
				}
			}else{
				$icon = $info;
			}
			
			
			if( strpos($icon,'/thumbnails/') === false ){
				$thumbPath = common::getDir('/data/_uploaded/image/thumbnails'.$icon.'.jpg');
			}else{
				$thumbPath = common::getDir('/data/_uploaded'.$icon);
			}
			
			$label = ' <img src="'.$thumbPath.'" height="100" width="100"  alt=""/>';
			echo common::Link($title,$label);
			echo '</a>';
			echo '<div>';
			echo common::Link($title, str_replace('_',' ',$title));
			echo '</div>';
			echo $count;
			echo '</li>';
		}
		echo '</ul>';
		
		
	}
	
	/*
	
	Updating Functions
	
	*/
	
	function DataFromFiles(){
		global $gptitles;
		
		$galleries = array();
		foreach($gptitles as $title => $info){
			
			if( isset($info['type']) && ($info['type'] == 'gallery') ){
				$info = special_galleries::GetIcon($title);
				$galleries[$title] = $info;
			}
		}
		special_galleries::SaveIndex($galleries);
		return $galleries;
	}
	
	function SaveIndex($galleries){
		global $dataDir;
		
		includeFile('admin/admin_tools.php');

		$file = $dataDir.'/data/_site/galleries.php';
		gpFiles::SaveArray($file,'galleries',$galleries);
	}
	
	function RenameGallery($from,$to){
		global $dataDir;
		$newgalleries = array();
		$galleries = special_galleries::GetData();

		foreach($galleries as $gallery => $info){
			if( $gallery === $from ){
				$newgalleries[$to] = $info;
			}else{
				$newgalleries[$gallery] = $info;
			}
		}
		special_galleries::SaveIndex($newgalleries);
	}
	
	function GetIcon($title){
		global $dataDir;
		
		$array = array('icon'=>false,'count'=>0);
		
		$file = $dataDir.'/data/_pages/'.$title.'.php';
		if( !file_exists($file) ){
			return $array;
		}
		
		include_once($file);
		if( !isset($file_array) || !isset($file_array[0]) ){
			return $array;
		}
		
		return array('icon'=>$file_array[0],'count'=>count($file_array));
	}	
	
	
	
}
