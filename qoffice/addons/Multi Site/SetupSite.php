<?php

/*


		
bool symlink  ( string $target  , string $link  )
symlink() creates a symbolic link to the existing target with the specified name link . 


A Script Like this would most likely need to work with install.php to set up the data directories
	Using the Install_DataFiles() function
		- $_POST['username']
		- $_POST['password']


*/

//message('To Do<br/>* Use FTP (for file permission issues) => two step process <br/>* Ability to Delete ');

includeFile('tool/ftp.php');

class SetupSite{
	
	var $siteData = array();
	var $dataFile;
	var $checksum;
	var $useftp;
	
	function SetupSite(){
		global $rootDir, $dataDir;
		
		$_POST += array('destination'=>dirname($rootDir));
		
		
		//ftp setup
		$this->GetSiteData();
		
		$hide = false;
		$cmd = common::GetCommand();
		switch($cmd){
			
			case 'New Installation':
				$this->SiteForm();
				$hide=true;
			break;
			
			case 'Create Site':
				if( !$this->Create1() ){
					$this->SiteForm();
					$hide = true;
				}
			break;
			
			
			case 'Use FTP Functions':
				$hide = true;
				$this->UseFTP();
			break;
			case 'Save FTP Information':
				$hide = $this->SaveFTPInformation();
			break;
			case 'Cancel FTP Usage':
				$this->CancelFTP();
			break;
			
			
			case 'uninstall':
				$hide = true;
				$this->UninstallCheck();
			break;
			case 'Remove Site':
				$this->UninstallSite();
			break;
			
		}
		
		if( !$hide ){
			$this->ShowSites();
		}
		
		$this->SaveSiteData();
	}

	
	function ShowSites(){
		global $langmessage;
		
		echo '<h3>Managed Sites</h3>';
		if( count($this->siteData['sites']) > 0 ){
			echo '<table class="bordered">';
			echo '<tr>';
			echo '<th>';
			echo 'Directory';
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';
				
			foreach($this->siteData['sites'] as $site => $data){
				echo '<tr>';
				echo '<td>';
				echo $site;
				echo '</td>';
				echo '<td>';
				
				echo common::Link('Admin_Site_Setup',$langmessage['uninstall'],'cmd=uninstall&site='.$site);
				echo '</td>';
				echo '</tr>';
			}
		}
		
			
		echo '</table>';
		echo '<p>';
		echo '<form action="'.common::getUrl('Admin_Site_Setup').'" method="post">';
		echo '<input type="submit" name="cmd" value="New Installation" />';
		echo '</form>';
		echo '</p>';
		
		echo '<h3>About</h3>';
		
		echo 'This addon will allow you to easily add installations of gpEasy to your server. For efficiency and convenience, new installations will not get their own /include directories, but will rather use the code from this installation.';
		
	}
	
	function UninstallCheck(){
		global $langmessage;
		
		$site = $_REQUEST['site'];
		
		echo '<h3>'.$langmessage['uninstall'].': '.$site.'</h3>';
		echo '<form action="'.common::getUrl('Admin_Site_Setup').'" method="post">';
		echo '<p>';
		echo 'Are you sure you want to permenantly remove <em>'.$site.'</em>?'; 
		echo '</p>';
		echo '<p>';
		echo 'All of the files and folders in this directory will be permenantly deleted.';
		echo '</p>';
		echo '<input type="hidden" name="site" value="'.htmlspecialchars($site).'" />';
		echo '<input type="submit" name="cmd" value="Remove Site" />';
		echo ' <input type="submit" name="cmd" value="Cancel" />';
		echo '</form>';
		
	}
	
	function UninstallSite(){
		global $langmessage;
		
		$site = $_POST['site'];
		if( !$this->RmDir($site) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		if( $this->siteData['useftp'] ){
			if( isset($this->siteData['sites'][$site]['ftp_destination']) ){
				$destination = $this->siteData['sites'][$site]['ftp_destination'];
				$conn_id = gpFiles::FTPConnect();
				ftp_rmdir($conn_id,$destination);
			}
			
		}else{
			rmdir($site);
		}
			
		
		message($langmessage['SAVED']);		
		
		unset($this->siteData['sites'][$site]);
	}
	
	function RmDir($dir){
		
		if( !file_exists($dir) ){
			return true;
		}
		
		
		if( is_link($dir) ){
			return unlink($dir);
		}
		
		$dh = @opendir($dir);
		if( !$dh ){
			return false;
		}
		
		$dh = @opendir($dir);
		if( !$dh ){
			return false;
		}
		$success = true;
		
		$subDirs = array();
		while( ($file = readdir($dh)) !== false){
			if( strpos($file,'.') === 0){
				continue;
			}
			
			$fullPath = $dir.'/'.$file;
			
			if( is_link($fullPath) ){
				if( !unlink($fullPath) ){
					$success = false;
				}
				continue;
			}
				
			
			if( is_dir($fullPath) ){
				$subDirs[] = $fullPath;
				continue;
			}
			if( !unlink($fullPath) ){
				$success = false;
			}
		}
		closedir($dh);
		
		foreach($subDirs as $subDir){
			if( !$this->RmDir($subDir) ){
				$success = false;
			}
			if( !gpFiles::RmDir($subDir) ){
				$success = false;
			}
			
		}
		
		return $success;
	}	
	
	
	
	
	function GetSiteData(){
		global $addonDataFolder;
		
		$this->dataFile = $addonDataFolder.'/data.php';
		if( !file_exists($this->dataFile) ){
			return;
		}
		require($this->dataFile);
		if( isset($siteData) ){
			$this->siteData = $siteData;
		}
		$this->checksum = $this->CheckSum($this->siteData);
		
		if( !isset($this->siteData['useftp']) ){
			$newData['sites'] = $this->siteData;
			$newData['useftp'] = false;
			$this->siteData = $newData;
		}
		
		
		if( isset($this->siteData['useftp']) && ($this->siteData['useftp'] === true) ){
			
		}else{
			$this->siteData['useftp'] = false;
		}
		$this->siteData += array('sites'=>array());
	}
	
	function SaveSiteData(){
		$check = $this->CheckSum($this->siteData);
		if( $check === $this->checksum ){
			return;
		}
		
		admin_tools::SaveArray($this->dataFile,'siteData',$this->siteData);
	}
	
	function CheckSum($array){
		return crc32( serialize($array) );
	}	
	
	
	function SiteForm(){
		global $langmessage;
		
		common::GetLangFile('install.php');
		includeFile('install/install_tools.php');
		
		
		echo '<form action="'.common::getUrl('Admin_Site_Setup').'" method="post">';


		echo '<h3>Destination Folder</h3>';
		echo '<table class="bordered">';
		echo '<tr>';
			echo '<td><b>Destination Path*</b></td>';
			echo '<td>';
			echo '<input type="text" name="destination" value="'.$_POST['destination'].'" size="40" />';
			echo '</td></tr>';
			
		echo '</table>';
		

		echo '<h3>Site Admin User</h3>';
		echo '<table class="bordered">';
		Install_Tools::Form_UserDetails();
		echo '</table>';		
		
		echo '<p>';
		echo '<input type="submit" name="cmd" value="Create Site" />';
		echo ' <input type="submit" name="cmd" value="Cancel" />';
		$this->ftpinput();
		echo '</p>';
		echo '</form>';
	}
	
	
	function Create1(){
		global $rootDir,$langmessage,$config;
		
		includeFile('install/install_tools.php');
		
		$destination = $_POST['destination'];
		if( file_exists($destination) ){
			message('Oops, <em>'.$destination.'</em> already exists, please try again.');
			return false;
		}
		
		$filename = basename($destination);
		$parentDir = dirname($destination);
		
		if( $this->siteData['useftp'] ){
			
			$conn_id = gpFiles::FTPConnect();
			if( $conn_id === false ){
				return false;
			}
			
			$ftp_parent = gpftp::GetFTPRoot($conn_id,$parentDir);
			$ftp_destination = $ftp_parent.'/'.$filename;
			
			if( !@ftp_chdir( $conn_id, $ftp_parent ) ){
				message('Oops, could not create <em>'.$destination.'</em> because the parent directory does not exist. Please make sure <em>'.$parentDir.'</em> exists before continuing.');
				return false;
			}
			
			if( !ftp_mkdir($conn_id,$ftp_destination) ){
				message('Oops, could not create <em>'.$destination.'</em>');
				return false;
			}
			
			ftp_site($conn_id, 'CHMOD 0777 '. $ftp_destination );
			
		}else{
		
			if( !file_exists($parentDir) ){
				message('Oops, could not create <em>'.$destination.'</em> because the parent directory does not exist. Please make sure <em>'.$parentDir.'</em> exists before continuing.');
				return false;
			}
			
			if( !is_writable($parentDir) ){
				message('Oops, could not create <em>'.$destination.'</em> because the parent directory is not writable. Please make sure <em>'.$parentDir.'</em> is writable before continuing.');
				return false;
			}

			if( !gpFiles::CheckDir($destination) ){
				message('Oops, could not create <em>'.$destination.'</em>');
				return false;
			}
		}
		
		
		$indexA = array();
		$indexA[] = '<'.'?'.'php';
		//$indexA[] = '$gpIndexRequest = true;'; //for saving dirPrefix
		//$indexA[] = '$dataDir = \''.$destination.'\';';
		//$indexA[] = '$dataDir = str_replace('\\','/',dirname(__FILE__));';
		$indexA[] = 'require_once(\'include/main.php\');';
		$index = implode("\n",$indexA);
		$indexFile = $destination.'/index.php';
		if( !gpFiles::Save($indexFile,$index) ){
			message('Failed to save the index.php file');
			return false;
		}
		
		
		$target = $rootDir.'/include';
		$name = $destination.'/include';
		symlink($target,$name);

		
		$target = $rootDir.'/themes';
		$name = $destination.'/themes';
		symlink($target,$name);
		
		
		//variable juggling
		global $dataDir, $config;
		$oldDir = $dataDir;
		$oldConfig = $config;
		$dataDir = $destination;
		$config = array();
		$config['linkto'] = 'Powered by <a href="http://gpEasy.com" title="The Fast and Easy CMS">gpEasy CMS</a>';
		echo '<ul>';
		Install_Tools::Install_DataFiles( $destination, $oldConfig['language'] );
		echo '</ul>';
		$dataDir = $oldDir;
		$config = $oldConfig;
		
		$this->siteData['sites'][$destination] = array();
		
		if( $this->siteData['useftp'] ){
			$this->siteData['sites'][$destination]['ftp_destination'] = $ftp_destination;
			ftp_site($conn_id, 'CHMOD 0777 '. $ftp_destination );
		}
		return true;
	}
	
	
	
	/* File Handling Functions */
	
		
	
	function UseFTP(){
		
		$_POST += array('ftp_server'=>gpftp::GetFTPServer(),'ftp_user'=>'','ftp_pass'=>'');

		
		echo '<form action="'.common::getUrl('Admin_Site_Setup').'" method="post">';
		
		echo '<h3>FTP Information</h3>';
		echo '<table class="bordered">';
			echo '<tr>';
				echo '<td><b>FTP Server*</b></td>';
				echo '<td>';
				echo '<input type="text" name="ftp_server" value="'.$_POST['ftp_server'].'" size="40" />';
				echo '</td></tr>';
			echo '<tr>';
				echo '<td><b>FTP Username*</b></td>';
				echo '<td>';
				echo '<input type="text" name="ftp_user" value="'.$_POST['ftp_user'].'" size="40" />';
				echo '</td></tr>';
			echo '<tr>';
				echo '<td><b>FTP Password*</b></td>';
				echo '<td>';
				echo '<input type="password" name="ftp_pass" value="'.$_POST['ftp_pass'].'" />';
				echo '</td></tr>';
			
		echo '</table>';
		echo '<p>';
		echo '<input type="submit" name="cmd" value="Save FTP Information" />';
		echo ' <input type="submit" name="cmd" value="Cancel" />';
		
		echo '</form>';
		
	}
	
	function CancelFTP(){
		global $config, $langmessage;
		
		$this->siteData['useftp'] = false;
		unset($config['ftp_root']);
		unset($config['ftp_user']);
		unset($config['ftp_server']);
		unset($config['ftp_pass']);
		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
			return false;
		}
		
		message($langmessage['SAVED']);		
	}

	
	function SaveFTPInformation(){
		global $config, $langmessage;
		
		$_POST += array('ftp_server'=>'','ftp_user'=>'','ftp_pass'=>'');
		$conn_id = $this->FTPConnect($_POST);
		if( $conn_id === false ){
			$this->UseFTP();
			return true;
		}
		
		
		$config['ftp_user'] = $_POST['ftp_user'];
		$config['ftp_server'] = $_POST['ftp_server'];
		$config['ftp_pass'] = $_POST['ftp_pass'];

		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
			return false;
		}
		
		message($langmessage['SAVED']);
		$this->siteData['useftp'] = true;
	}
	
	function ftpinput(){
		
		if( $this->siteData['useftp'] ){
			echo ' - ';
			echo ' <input type="submit" name="cmd" value="Cancel FTP Usage" />';
			return;
		}
		
		if( function_exists('ftp_connect') ){
			echo ' - ';
			echo ' <input type="submit" name="cmd" value="Use FTP Functions" />';
		}
	}
	
	function FTPConnect($array){
		static $conn_id = false;
		
		if( $conn_id ){
			return $conn_id;
		}
		
		
		$conn_id = @ftp_connect($array['ftp_server'],21,6);
		if( !$conn_id ){
			message('ftp_connect() failed for server : '.$array['ftp_server']);
			return false;
		}
		
		$login_result = @ftp_login($conn_id,$array['ftp_user'],$array['ftp_pass'] );
		if( !$login_result ){
			message('ftp_login() failed for server : '.$array['ftp_server'].' and user: '.$array['ftp_user']);
			return false;
		}
		register_shutdown_function(array('gpFiles','ftpClose'),$conn_id);
		return $conn_id;
	}
	
}
