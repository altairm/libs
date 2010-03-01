<?php




class Install_Tools{
	
	function Form_UserDetails(){
		global $langmessage;
		
		$_POST += array('username'=>'');
		echo '<tr><td><b>'.$langmessage['Admin_Username'].'*</b></td><td><input type="text" name="username" value="'.htmlspecialchars($_POST['username']).'" /></td></tr>';
		echo '<tr><td><b>'.$langmessage['Admin_Password'].'*</b></td><td><input type="password" name="password" value="" /></td></tr>';
		echo '<tr><td><b>'.$langmessage['Repeat_Password'].'*</b></td><td><input type="password" name="password1" value="" /></td></tr>';
	}
	
	function Install_DataFiles( $destination = false, $language = 'en' ){
		global $config,$langmessage;
		
		$args =& $_POST;
		
		if( $destination === false ){
			$destination = $GLOBALS['dataDir'];
		}
		
		
		
		//Password
		echo '<li>';
			if( ($args['password']=="") || ($args['password'] !== $args['password1'])  ){
				echo '<span style="color:#FF0000">';
				echo $langmessage['invalid_password'];
				echo '</span>';
				echo '</li>';
				return false;
			}
			echo '<span style="color:#009900">';
			echo $langmessage['PASSWORDS_MATCHED'];
			echo '</span>';
			echo '</li>';
			
			
			
		//Username
		echo '<li>';
			$test = str_replace(array('.','_'),array(''),$args['username'] );
			if( empty($test) || !ctype_alnum($test) ){
				echo '<span style="color:#FF0000">';
				echo $langmessage['invalid_username'];
				echo '</span>';
				echo '</li>';
				return false;
			}
			echo '<span style="color:#009900">';
			echo $langmessage['Username_ok'];
			echo '</span>';
			echo '</li>';
		
		
		
		//set config variables
		//$config = array(); //because of ftp values
		$config['theme'] = 'One_Point_5/Blue';
		$config['title'] = 'gp|Easy CMS';
		$config['keywords'] = 'gpEasy, Easy, CMS, Content Management, PHP, Free CMS, Website builder, Open Source';
		$config['desc'] = 'gp|Easy CMS is a complete and easy to use Content Management System. Written in PHP, it\'s free, open source and easy to use from the start.';
		$config['timeoffset'] = '0';
		$config['indexfile'] = 'index.php';
		$config['language'] = $language;
		//$config['contact_message'] = '';
		$config['langeditor'] = 'en';
		$config['dateformat'] = '%m/%d/%y - %I:%M %p';
		$config['gpversion'] = $GLOBALS['gpversion'];
		$config['linkto'] = 'Powered by <a href="http://www.gpEasy.com" title="The Fast and Easy CMS">gpEasy CMS</a>';
		//$config['path_info'] = Install_Tools::GetPathInfo();
		
		//directories
		gpFiles::CheckDir($destination.'/data/_uploaded/image');
		gpFiles::CheckDir($destination.'/data/_uploaded/media');
		gpFiles::CheckDir($destination.'/data/_uploaded/file');
		gpFiles::CheckDir($destination.'/data/_uploaded/flash');
		gpFiles::CheckDir($destination.'/data/_sessions');
		
		
		//content
		$content = '<h1>Home</h1><p>Congratulations on successfully installing <a href="http://www.gpEasy.com" title="gpEasy.com">gp|Easy CMS</a>!</p>';
		$content .= '<p>This is the default start page of your website. ';
		$content .= ' Get started customizing your site by '.Install_Tools::Install_Link('Admin','logging in').' then ';
		$content .= Install_Tools::Install_Link('Home','editing this page','cmd=edit').'.';
		$content .= '</p>';
		$content .= ' <p>Check out '.Install_Tools::Install_Link('Admin','the admin pages').' for more administration options.</p>';
		gpFiles::SaveTitle('Home',$content);
			
		gpFiles::SaveTitle('Another_Page',"<h1>Another</h1><p>This is just another page.</p>");

		gpFiles::SaveTitle('Another_SubLink','<h1>Another Sub-submenu</h1><p>This was created as a subpage of your <em>Another</em> page. You can easily '.Install_Tools::Install_Link('Admin_Menu','change the arrangement').' of all your pages.</p>');
		
		gpFiles::SaveTitle('About','<h1>About gpEasy CMS</h1><p><a href="http://www.gpEasy.com" title="gpeasy.com">gp|Easy</a> is a complete <a href="http://www.gpEasy.com/index.php/CMS">Content Management System (CMS)</a> that can help you create rich and flexible web sites with a simple and easy to use interface.</p>
		<h2>gpEasy CMS How To</h2>
		<p>Learn how to <a href="http://docs.gpeasy.org/index.php/Main/File%20Manager">manage your files</a>,
		<a href="http://docs.gpeasy.org/index.php/Main/Creating%20Galleries">create galleries</a> and more in the 
		<a href="http://docs.gpeasy.org/index.php/">gpEasy Documentation</a>.
		</p>
		
		<h2>gpEasy CMS Features</h2>
		<ul>
		<li>WYSIWYG Editor (CKEditor)</li>
		<li>Galleries (ColorBox)</li>
		<li>SEO Friendly Links</li>
		<li>Free and Open Source (GPL)</li>
		<li>Runs on PHP</li>
		<li>File Upload Manager</li>
		<li>Editable Theme Content</li>
		<li>Deleted File Trash Can</li>
		<li>Multiple User Administration</li>
		<li>Works in Safe Mode with FTP Functions</li>
		<li>Flat File Storage</li>
		<li>Fast Page Loading</li>
		<li>Fast and Easy Installation</li>
		<li>reCaptcha for Contact Form</li>
		<li>HTML Tidy (when available)</li>
		</ul>');
		
		//Side_Menu
		$file = $destination.'/data/_extra/Side_Menu.php';
		gpFiles::SaveFile($file,'<p>The text in this area of your pages is '.Install_Tools::Install_Link('Admin','also editable','cmd=extra').'. Since this will be a part of all of your pages, use it for significant information like announcements, news or links.</p>');
		
		//Header
		$file = $destination.'/data/_extra/Header.php';
		$contents = '<h1>'.Install_Tools::Install_Link('',$config['title']).'</h1>';
		
		$contents .= '<h4>'.'The Fast and Easy CMS'.'</h4>';
		gpFiles::SaveFile($file,$contents);
		
		//Footer
		$file = $destination.'/data/_extra/Footer.php';
		gpFiles::SaveFile($file,'<p>The text of the footer is editable.</p>');
		
		//contact html
		$file = $destination.'/data/_extra/Contact.php';
		gpFiles::SaveFile($file,'<h2>Contact Us</h2><p>Use the form below to contact us, and be sure to enter a valid email address if you want to hear back from us.</p>');
		
		
		//	menu
		$gpmenu = array();
		$gpmenu['Home'] = 0;
		$gpmenu['Another_Page'] = 0;
		$gpmenu['Another_SubLink'] = 1;
		$gpmenu['About'] = 0;
		$gpmenu['Special_Contact'] = 1;
		
		//	links
		$gptitles = array();
		$gptitles['Home']['label'] = 'Home';
		$gptitles['Home']['type'] = 'page';
		
		$gptitles['Another_Page']['label'] = 'Another Page';
		$gptitles['Another_Page']['type'] = 'page';
		
		$gptitles['Another_SubLink']['label'] = 'Another SubLink';
		$gptitles['Another_SubLink']['type'] = 'page';
		
		$gptitles['About']['label'] = 'About';
		$gptitles['About']['type'] = 'page';
		
		$gptitles['Special_Site_Map']['type'] = 'special';
		$gptitles['Special_Site_Map']['lang_index'] = 'site_map';
		
		$gptitles['Special_Galleries']['type'] = 'special';
		$gptitles['Special_Galleries']['lang_index'] = 'galleries';
		
		$gptitles['Special_Contact']['type'] = 'special';
		$gptitles['Special_Contact']['lang_index'] = 'contact';		
		
		
		$pages = array();
		$pages['gpmenu'] = $gpmenu;
		$pages['gptitles'] = $gptitles;
		
		echo '<li>';
		if( !gpFiles::SaveArray($destination.'/data/_site/pages.php','pages',$pages) ){
			echo '<span style="color:#FF0000">';
			//echo 'Could not save pages.php';
			echo sprintf($langmessage['COULD_NOT_SAVE'],'pages.php');
			echo '</span>';
			echo '</li>';
			return false;
		}
		echo '<span style="color:#009900">';
		//echo 'Pages.php saved.';
		echo sprintf($langmessage['_SAVED'],'pages.php');
		echo '</span>';
		echo '</li>';	
		
		
		//users
		echo '<li>';
		$users = array();
		$users[$args['username']]['password'] = sha1(trim($args['password']));
		$users[$args['username']]['granted'] = 'all';
		if( !gpFiles::SaveArray($destination.'/data/_site/users.php','users',$users) ){
			echo '<span style="color:#FF0000">';
			echo sprintf($langmessage['COULD_NOT_SAVE'],'users.php');
			//echo 'Could not save users.php';
			echo '</span>';
			echo '</li>';
			return false;
		}
		echo '<span style="color:#009900">';
		echo sprintf($langmessage['_SAVED'],'users.php');
		//echo 'Users.php saved.';
		echo '</span>';
		echo '</li>';
		
		
		
		//save config
		echo '<li>';
		if( !admin_tools::SaveConfig() ){
			echo '<span style="color:#FF0000">';
			echo sprintf($langmessage['COULD_NOT_SAVE'],'config.php');
			//echo 'Could not save config.php';
			echo '</span>';
			echo '</li>';
			return false;
		}
		echo '<span style="color:#009900">';
		echo sprintf($langmessage['_SAVED'],'config.php');
		//echo 'Config.php saved.';
		echo '</span>';
		echo '</li>';
		

		return true;
	}
		
	function GetPathInfo(){
		$UsePathInfo =
			( strpos( php_sapi_name(), 'cgi' ) === false ) &&
			( strpos( php_sapi_name(), 'apache2filter' ) === false ) &&
			( strpos( php_sapi_name(), 'isapi' ) === false );
			
		return $UsePathInfo;
	}
	
	function Install_Link($href,$label,$query='',$attr=''){
		$text = '<';
		$text .= '?php';
		$text .= ' echo common::Link(\''.$href.'\',\''.$label.'\',\''.$query.'\',\''.$attr.'\'); ';
		$text .= '?';
		$text .= '>';
		return $text;
	}

	
	
}








/* 
 * Functions from skybluecanvas
 * 
 * 
 */

class FileSystem{
	
    function getExpectedPerms($file){
    
		if( !function_exists('posix_geteuid') ){
            return '777';
		}
    
		//if user id's match
		$puid = posix_geteuid();
		$suid = FileSystem::file_uid($file);
		if( ($suid !== false) && ($puid == $suid) ){
			return '755';
		}
		
		//if group id's match
		$pgid = posix_getegid();
		$sgid = FileSystem::file_group($file);
		if( ($sgid !== false) && ($pgid == $sgid) ){
			return '775';
		}
		
		//if user is a member of group
		$snam = FileSystem::file_owner($file);
		$pmem = FileSystem::process_members();
		if (in_array($suid, $pmem) || in_array($snam, $pmem)) {
			return '775';
		}
		
		return '777';
    }
	
	/*
	 * Compare Permissions
	 */
    function perm_compare($perm1, $perm2) {
		
		if( !FileSystem::ValidPermission($perm1) ){
			return false;
		}
		if( !FileSystem::ValidPermission($perm2) ){
			return false;
		}
		
/*
        if (strlen($perm1) != 3) return false;
        if (strlen($perm2) != 3) return false;
*/
		
        if (intval($perm1{0}) > intval($perm2{0})) {
            return false;
        }
        if (intval($perm1{1}) > intval($perm2{1})) {
            return false;
        }
        if (intval($perm1{2}) > intval($perm2{2})) {
            return false;
        }
        return true;
    }
	
	function ValidPermission(&$permission){
		if( strlen($permission) == 3 ){
			return true;
		}
		if( strlen($permission) == 4 ){
			if( intval($permission{0}) === 0 ){
				$permission = substr($permission,1);
				return true;
			}
		}
		return false;
	}
	
    /*
    * @description   Gets name of the file owner
    * @return string The name of the file owner
    */
	
	function file_owner($file) {
		$info = FileSystem::file_info($file);
		if (is_array($info)) {
			if (isset($info['name'])) {
				return $info['name'];
			}
			else if (isset($info['uid'])) {
				return $info['uid'];
			}
		}
		return false;
	}
	
		
    /*
    * @description  Gets Groups members of the PHP Engine
    * @return array The Group members of the PHP Engine
    */
	
	function process_members() {
		$info = FileSystem::process_info();
		if (isset($info['members'])) {
			return $info['members'];
		}
		return array();
	}	
	
	
    /*
    * @description Gets User ID of the file owner
    * @return int  The user ID of the file owner
    */
	
	function file_uid($file) {
		$info = FileSystem::file_info($file);
		if (is_array($info)) {
			if (isset($info['uid'])) {
				return $info['uid'];
			}
		}
		return false;
	}	
	
    /*
    * @description Gets Group ID of the file owner
    * @return int  The user Group of the file owner
    */
	
	function file_group($file) {
		$info = FileSystem::file_info($file);
		if (is_array($info) && isset($info['gid'])) {
			return $info['gid'];
		}
		return false;
	}
	
    /*
    * @description  Gets Info array of the file owner
    * @return array The Info array of the file owner
    */
	
	function file_info($file) {
		return posix_getpwuid(@fileowner($file));
	}

    /*
    * @description  Gets Group Info of the PHP Engine
    * @return array The Group Info of the PHP Engine
    */
	
	function process_info() {
		return posix_getgrgid(posix_getegid());
	}	
	
}
