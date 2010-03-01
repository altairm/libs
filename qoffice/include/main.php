<?php

$start_time = microtime();
clearstatcache();
define('is_running',true);
require_once('common.php');
ob_start( 'ob_gzhandler' ); //available since 4.0.4
SetGlobalPaths(0);

//check if installed
global $dataDir;
if( !file_exists($dataDir.'/data/_site/config.php') ){
	includeFile('install/install.php');
	die();
}

/*
 *	Flow Control
 */

common::GetConfig();


//links from example.com are not the same as example.com/index.php/
if( isset($_SERVER['REQUEST_URI']) && (strpos($_SERVER['REQUEST_URI'],'index.php/') === false) ){
	$redir = common::GetUrl($config['homepath']); //Google doesn't like it when pages are duplicated, so we send all empty request to the homepath
	header('Location: '.$redir);
	die();
}

common::sessions();
includeFile('tool/gpOutput.php');


$title = common::WhichPage();
$type = common::PageType($title);
switch($type){
	
	case 'special':
		include_once($rootDir.'/include/special.php');
		$page = new special_display($title,$type);
	break;
	
	case 'admin':
		include_once($rootDir.'/include/admin.php');
		$page = new admin_display($title,$type);
	break;
	
	default:
		$page = new display($title,$type);
	break;
}

$page->RunScript();


//decide how to send the content
$gpreq =& $_REQUEST['gpreq'];
switch($_REQUEST['gpreq']){
	
	case 'flush':
		gpOutput::Flush();
	break;
	
	case 'body':
		gpOutput::BodyAsHTML();
	break;
	
	case 'json':
		includeFile('tool/ajax.php');
		gpAjax::Response();
	break;
	
	case 'content':
		gpOutput::Content();
	break;
	
	default:
		gpOutput::Template();
	break;
}
	


/*
function microtime_diff($a, $b, $eff = 3) {
	$a = array_sum(explode(" ", $a));
	$b = array_sum(explode(" ", $b));
	return sprintf('%0.'.$eff.'f', $b-$a);
}
echo '<h2>'.microtime_diff($start_time,microtime()).'</h2>';
*/




/*
 *	Display Class 
 */

class display{
	var $pagetype = 'display';
	var $title;
	var $theme_name;
	var $theme_css;
	var $label;
	var $file;
	var $contentBuffer;
	var $head = '';
	var $TitleInfo;
	var $fileType = '';
	var $jQueryCode = false;
	var $ajaxReplace = array();
	var $admin_js = false;
	var $admin_links = array();
	

	function display($title,$type){
		global $config, $gptitles, $dataDir, $langmessage;
		
		if( !isset($gptitles[$title]) ){
			
			if( !common::LoggedIn() ){
				message($langmessage['OOPS_TITLE']);
				
			}else{
			
				$link = common::GetUrl('Admin_New','title='.$title);
				$message = sprintf($langmessage['DOESNT_EXIST'],str_replace('_',' ',$title),$link);
				message($message);
				
			}
			$title = $config['homepath'];
		}
		
		$this->title = $title;
		$this->TitleInfo = $gptitles[$this->title];
		$this->label = $this->TitleInfo['label'];
		$this->SetTheme();
		$this->file = $dataDir.'/data/_pages/'.$this->title.'.php';
		$this->fileType = $type;
		

	}
	
	function RunScript(){
		global $langmessage;
		
		if( $this->fileType == 'gallery' ){
			$this->admin_js = true;
			common::AddColorBox();
		}		
		
		
		$cmd = common::GetCommand();
		if( !empty($cmd) && common::LoggedIn() ){
			include_once($GLOBALS['rootDir'].'/include/tool/editing_files.php');
			new file_editing();
		}
		
		//get the content
		if( empty($this->contentBuffer) ){
			ob_start();
			$admin = common::LoggedIn();
			if( $admin ){
				$cmd = common::GetCommand();
				$name = $this->title;
				echo '<div class="editable_area">'; // class="edit_area" added by javascript
				
				if( $cmd != 'edit' ){
					echo common::Link($this->title,$langmessage['edit'],'cmd=edit',' class="ExtraEditLink" title="'.$this->title.'" ');
				}
				
			}
		
			if( $this->fileType == 'gallery' ){
				$this->ShowGallery();
			}elseif( file_exists($this->file) ){
				require_once($this->file);
			}
			echo '<div style="clear:both;"></div>';
			if( $admin ){
				echo '</div>';
			}
			$this->contentBuffer = common::get_clean();
			
			$this->ReplaceContent($this->contentBuffer);
		}
	}
	function area_name($name){
		$name = base64_encode($this->title);
		return str_replace('=','',$name);
	}
	
	function ReplaceContent(&$content,$offset=0){
		global $dataDir,$gpTitles;
		static $includes = 0;
		
		//prevent too many inlcusions
		if( $includes >= 10 ){
			return;
		}
		
		$pos = strpos($content,'{{',$offset);
		if( $pos === false ){
			return;
		}
		$pos2 = strpos($content,'}}',$pos);
		if( $pos2 === false ){
			return;
		}
			
		$arg = substr($content,$pos+2,$pos2-$pos-2);
		$title = gpFiles::CleanTitle($arg);
		if( isset($gpTitles[$title]) ){
			$this->ReplaceContent($content,$pos2);
			return;
		}
		$type = common::PageType($title);
		$file = $dataDir.'/data/_pages/'.gpFiles::CleanTitle($title).'.php';
		if( !file_exists($file) ){
			$this->ReplaceContent($content,$pos2);
			return;
		}
		
		$includes++;
		switch($type){
			case 'gallery':
				$replacement = $this->GetGalleryHtml($file);
			break;
			default:
				ob_start();
				require($file);
				$replacement = common::get_clean();
			break;
		}
		
		$content = substr_replace($content,$replacement,$pos,$pos2-$pos+2);
		$this->ReplaceContent($content,$pos);
	}
	
	
	function SetTheme(){
		$theme = display::OrConfig($this->title,'theme');
		$this->theme_name = dirname($theme);
		$this->theme_css = basename($theme);
	}

	
	//sets this title's info to config values, if a value doesn't exists specifically for the title
	function OrConfig($title,$var){
		global $config,$gptitles;
		
		
		if( !empty($gptitles[$title][$var]) ){
			return $gptitles[$title][$var];
		}
		
		if( display::ParentConfig($title,$var,$value) ){
			return $value;
		}
		
		return $config[$var];
	}
	
	function ParentConfig($checkTitle,$var,&$value){
		global $config,$gpmenu,$gptitles;
		
		//get configuration of parent titles
		if( !isset($gpmenu[$checkTitle]) ){
			return false;
		}
		
		$checkLevel = $gpmenu[$checkTitle]['level'];
		
		$menutitles = array_keys($gpmenu);
		$key = array_search($checkTitle,$menutitles);
		for($i = ($key-1); $i >= 0; $i--){
			$title = $menutitles[$i];
			
			//check the level
			$level = $gpmenu[$title];
			if( $level >= $checkLevel ){
				continue;
			}
			$checkLevel = $level;
			
			if( !empty($gptitles[$title][$var]) ){
				//die('hmm: '. $gptitles[$title][$var]);
				$value = $gptitles[$title][$var];
				return true;
			}
			
			//no need to go further
			if( $level == 0 ){
				return false;
			}
			
		}
		return false;
	}
	
	
	/*
	 * Get functions
	 * 
	 * Missing:
	 *		$#sitemap#$
	 * 		different menu output
	 * 
	 */	
	
	function GetSiteLabel(){
		global $config;
		echo $config['title'];
	}
	function GetSiteLabelLink(){
		global $config;
		echo common::Link('',$config['title']);
	}
	function GetPageLabel(){
		echo $this->label;
	}

	
	/* deprecated */
	function GetAllGadgets(){
		gpOutput::GetAllGadgets();
	}
	
	/* deprecated */
	function GetGadget(){}

	/* deprecated */
	function GetExpandMenu(){
		gpOutput::Get('ExpandMenu');
	}
	
	/* deprecated */
	function GetFullMenu(){
		gpOutput::Get('FullMenu');
	}
	/* deprecated */
	function GetMenu(){
		gpOutput::Get('Menu');
	}
	/* deprecated */
	function GetSubMenu(){
		gpOutput::Get('SubMenu');
	}
	/* deprecated */
	function GetExpandLastMenu(){
		gpOutput::Get('ExpandLastMenu');
	}
	/* deprecated */
	function GetTopTwoMenu(){
		gpOutput::Get('TopTwoMenu');
	}
	/* deprecated */
	function GetBottomTwoMenu(){
		gpOutput::Get('BottomTwoMenu');
	}
	
	/* deprecated */
	function GetFooter(){
		gpOutput::Get('Extra','Footer');
	}
	/* deprecated */
	function GetExtra($name='Side_Menu'){
		gpOutput::Get('Extra',$name);
	}
	/* deprecated */
	function GetAdminLink(){
		gpOutput::GetAdminLink();
	}

	/* deprecated */
	function GetHead() {
		gpOutput::GetHead();
	}
	
	/* deprecated */
	function GetLangText($key){
		gpOutput::Get('Text',$key);
	}
	
	function GetContent(){
		global $langmessage;
		
		echo '<div id="gpx_content">';
		$this->GetMessages();
		echo $this->contentBuffer;
		echo '</div>';
		
	}

	
	function ShowGallery(){
		echo '<h2>'.$this->label.'</h2>';
		echo $this->GetGalleryHtml($this->file);
	}
	
	
	function GetGalleryHtml($file){
		
		ob_start();
		
		ob_start();
		require($file);
		ob_end_clean();
		

		if( !isset($file_array) ){
			$file_array = array();
		}
		if( !isset($caption_array) ){
			$caption_array = array();
		}
		
		//version 1.0b4 update of gallery
		if( !isset($fileVersion) ){
			foreach($file_array as $i => $file){
				$file_array[$i] = '/image'.$file;
			}
		}
		
		echo '<ul class="gp_gallery">';
		foreach($file_array as $index => $file){
			echo '<li>';
			
			$caption = '';
			if( !empty($caption_array[$index]) ){
				$caption = $caption_array[$index];
			}
			
			if( strpos($file,'/thumbnails/') === false ){
				$imgPath = common::getDir('/data/_uploaded'.$file);
				$thumbPath = common::getDir('/data/_uploaded/image/thumbnails'.$file.'.jpg');
			}else{
				$imgPath = common::getDir('/data/_uploaded'.$file);
				$thumbPath = common::getDir('/data/_uploaded'.$file);
			}
			echo '<a href="'.$imgPath.'" name="gallery" rel="gallery_gallery" title="'.htmlspecialchars($caption).'">';
			echo ' <img src="'.$thumbPath.'" height="100" width="100"  alt=""/>';
			echo '</a>';
			echo '<div>';
			echo $caption;
			echo '</div>';
			echo '</li>';
		}
		echo '</ul>';
		echo '<div style="clear:both"></div>';
		
		return common::get_clean();
	}
	
	//returnMessages
	function GetMessages(){
		global $wbMessageBuffer;
		
		
		if( empty($wbMessageBuffer) ){
			return;
		}

		$result = '';
		foreach($wbMessageBuffer as $key2 => $args){
			if( !isset($args[0]) ){
				continue;
			}
			
			if( isset($args[1]) ){
				$result .= '<li>'.call_user_func_array('sprintf',$args).'</li>';
			}else{
				$result .= '<li>'.$args[0].'</li>';
			}
		}
		//$result = str_replace('%s',' ',$result);
		
		
		$wbMessageBuffer = array();
		echo '<div class="messages">';
		echo '<a style="float:right;text-decoration:none;line-height:0;font-weight:bold;margin:3px 0 0 2em;color:#666;font-size:larger;display:none;" href="" class="req_script" name="close_message">';
		echo 'x';
		echo '</a>';
		echo '<ul>'.$result.'</ul></div>';
	}

	
}
	
	



