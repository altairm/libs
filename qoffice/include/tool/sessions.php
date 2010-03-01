<?php

/* 

Custom SESSIONS

*/


class gpsession{
	
	
	function LogIn(){
		global $dataDir,$langmessage,$rootDir;
		
		
		if( !isset($_COOKIE['g']) && !isset($_COOKIE['gpEasy']) ){
			message($langmessage['COOKIES_REQUIRED']);
			return false;
		}
		
		include($dataDir.'/data/_site/users.php');
		$username = $_POST['username'];
		
		if( !isset($users[$username]) ){
			message($langmessage['incorrect_login']);
			return false;
		}
		$users[$username] += array('attempts'=> 0,'granted'=>'');
		$userinfo =& $users[$username];
		
		//Check Attempts
		if( $userinfo['attempts'] >= 5 ){
			$timeDiff = (time() - $userinfo['lastattempt'])/60; //minutes
			if( $timeDiff < 10 ){
				message($langmessage['LOGIN_BLOCK'],ceil(10-$timeDiff));
				return false;
			}
		}

		
		$pass = sha1(trim($_POST['password']));
		
		
		//reset password
		if( isset($userinfo['newpass']) ){
			if( $userinfo['newpass'] == $pass ){
				$userinfo['password'] = $pass;
			}
		}
		
		//if passwords don't match
		if( $userinfo['password'] != $pass ){
			message($langmessage['incorrect_login']);
			$url = common::GetUrl('Admin?cmd=forgotten');
			message($langmessage['forgotten_password'],$url);
			gpsession::UpdateAttempts($users,$username);
			return false;
		}
		
		if( isset($userinfo['newpass']) ){
			unset($userinfo['newpass']); //will be saved in UpdateAttempts
		}
		
		if( !isset($userinfo['cookie_id']) ){
			$userinfo['cookie_id'] = md5($username.$pass); //will be saved in UPdateAttempts
		}
		gpsession::create($userinfo['cookie_id']);
		
		global $gpAdmin;
		
		//logged in!
		$gpAdmin['adminuser'] = common::IP($_SERVER['REMOTE_ADDR']);
		$gpAdmin['username'] = $username;
		$gpAdmin['granted'] = $userinfo['granted'];
		gpsession::UpdateAttempts($users,$username,true);
		message($langmessage['logged_in']);
		
		
		return true;
	}	
	
	function LogOut(){
		global $langmessage, $gpAdmin;
		
		gpsession::start();

		unset($gpAdmin['adminuser']);
		
		if( isset($_COOKIE['gpEasy']) ){
			gpsession::cookie('gpEasy','',time()-42000);
			message($langmessage['LOGGED_OUT']);
		}
	}
	
	function cookie($name,$value,$expires = false){
		global $config;
		
		$cookiePath = '/';
		if( !empty($config['dirPrefix']) ){
			$cookiePath = $config['dirPrefix'];
		}
		$cookiePath = str_replace(' ','%20',$cookiePath);
		
		
		if( $expires === false ){
			$expires = time()+2592000;
		}
		
		setcookie($name, $value, $expires, $cookiePath); //need to take care of spaces!
	}
	
	
	
	
	function UpdateAttempts($users,$username,$reset = false){
		global $dataDir;
		
		
		if( $reset ){
			$users[$username]['attempts'] = 0;
		}else{
			$users[$username]['attempts']++;
		}
		$users[$username]['lastattempt'] = time();
		gpFiles::SaveArray($dataDir.'/data/_site/users.php','users',$users);
	}	
	
	
	/* read/write handler functions */
	
	function getFile(){
		global $dataDir;
		
		if( !isset($_COOKIE['gpEasy']) ){
			return false;
		}
		return $dataDir.'/data/_sessions/gpsess_'.$_COOKIE['gpEasy'];
	}
	
	function create($id){
		gpsession::cookie('gpEasy',$id);
		$_COOKIE['gpEasy'] = $id;
		$file = gpsession::getFile();
		
		if( !file_exists($file) ){
			
			//make sure the directory exists
			$dir = dirname($file);
			gpFiles::CheckDir($dir);
		
			//open the file
			$fp = fopen($file,'wb');
			fclose($fp);
			//chmod($file,0644);
			chmod($file,0666);
		}
		gpsession::start();
	}
	
	function start(){
		//global $gpAdmin;
		global $langmessage;
		
		
		if( !isset($_COOKIE['g']) && !isset($_COOKIE['gpEasy']) ){
			message($langmessage['COOKIES_REQUIRED']);
			return false;
		}
		
		
		$file = gpsession::getFile();
		if( ($file === false) || !file_exists($file) ){
			gpsession::cookie('gpEasy','',time()-42000); //make sure the cookie is deleted
			return false;
		}
		
		ob_start();
		require($file);
		$gpAdminText = common::get_clean();
		if( isset($gpAdmin) && is_array($gpAdmin) ){
			$GLOBALS['gpAdmin'] = $gpAdmin;
		}else{
			$GLOBALS['gpAdmin'] = unserialize($gpAdminText);
		}

		$checksum = $GLOBALS['gpAdmin']['checksum'];
		$gpAdmin['temp'] = rand(0,100);
		
		register_shutdown_function(array('gpsession','close'),$file,$checksum);
	}
	
	function close($file,$checksum_read){
		global $gpAdmin;
		
		unset($gpAdmin['checksum']);
		$checksum = gpsession::checksum($gpAdmin);
		
		//nothing changes
		if( $checksum === $checksum_read ){
			return;
		}
		
		$gpAdmin['checksum'] = $checksum; //store the new checksum
		gpFiles::SaveArray($file,'gpAdmin',$gpAdmin);
	}
	
	function checksum($array){
		return crc32(serialize($array) );
	}
	
}



