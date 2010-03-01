<?php
defined("is_running") or die("Not an entry point...");

class admin_users{
	var $users;
	function admin_users(){
		
		$this->GetUsers();
		$cmd = common::GetCommand();
		switch($cmd){
			case 'newuser':
				$this->CreateNewUser();
			break;
			
			case 'rm':
				$this->RmUserStart();
			break;
			case 'rm_confirmed':
				$this->RmUserConfirmed();
			break;
			
			case 'resetpass':
				if( $this->ResetPass() ){
					break;
				}
			case 'changepass':
				$this->ChangePass();
			return;
			
			
			case 'ResetDetails':
				if( $this->ResetDetails() ){
					break;
				}
			case 'details':
				$this->ChangeDetails();
			return;
			
		}
	
		$this->ShowForm();
	}
	
	function ResetDetails(){
		global $langmessage;
		
		$username =& $_REQUEST['username'];
		if( !isset($this->users[$username]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$_POST += array('grant'=>'');

		if( !empty($_POST['email']) ){
			$this->users[$username]['email'] = $_POST['email'];
		}
		
		$this->users[$username]['granted'] = $this->GetGrantedValue($_POST['grant'],$username);
		return $this->SaveUserFile();
	}
	
	function ChangeDetails(){
		global $langmessage;
		
		$username =& $_REQUEST['username'];
		if( !isset($this->users[$username]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$userinfo = $this->users[$username];
		
		
		echo '<form action="'.common::getUrl('Admin_Users').'" method="post">';
		echo '<input type="hidden" name="cmd" value="ResetDetails" />';
		echo '<input type="hidden" name="username" value="'.htmlspecialchars($username).'" />';

		echo '<table class="bordered">';
		echo '<tr>';
			echo '<th colspan="2">';
			echo $langmessage['details'];
			echo ' - ';
			echo $username;
			echo '</th>';
			echo '</tr>';
			
		$this->DetailsForm($userinfo);
			
		echo '<tr>';
			echo '<td>';
			echo '</td>';
			echo '<td>';
			echo ' <input type="submit" name="aaa" value="'.$langmessage['continue'].'" />';
			echo ' <input type="reset" />';
			echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
			echo '</td>';
			echo '</tr>';
			
		echo '</table>';
		echo '</form>';
		
	}
	
	function RmUserConfirmed(){
		global $langmessage;
		$username = $this->CheckUser();
		
		if( $username == false ){
			return;
		}
		
		unset($this->users[$username]);
		return $this->SaveUserFile();
	}
	
	function RmUserStart(){
		global $langmessage;
		$username = $this->CheckUser();
		
		if( $username == false ){
			return;
		}
		
		$mess = '';
		$mess .= '<form action="'.common::getUrl('Admin_Users').'" method="post">';
		$mess .= sprintf($langmessage['delete_confirm'],$username);
		$mess .= '<input type="hidden" name="cmd" value="rm_confirmed" />';
		$mess .= '<input type="hidden" name="username" value="'.htmlspecialchars($username).'" />';
		$mess .= ' <input type="submit" name="aaa" value="'.$langmessage['delete'].'" />';
		$mess .= ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		$mess .= '</form>';
		message($mess);
	}
	
	function CheckUser(){
		global $langmessage,$gpAdmin;
		$username = $_REQUEST['username'];
		
		if( !isset($this->users[$username]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		if( $username == $gpAdmin['username'] ){
			message($langmessage['OOPS']);
			return false;
		}
		return $username;
	}
	
	
	
	function CreateNewUser(){
		global $langmessage;
		$_POST += array('grant'=>'');
		
		if( ($_POST['password']=="") || ($_POST['password'] !== $_POST['password1'])  ){
			message($langmessage['invalid_password']);
			return false;
		}
		
		
		$newname = $_POST['username'];
		$test = str_replace( array('.','_'), array(''), $newname );
		if( empty($test) || !ctype_alnum($test) ){
			message($langmessage['invalid_username']);
			return false;
		}
		
		if( isset($this->users[$newname]) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		
		if( !empty($_POST['email']) ){
			$this->users[$newname]['email'] = $_POST['email'];
		}
		
		$this->users[$newname]['password'] = sha1(trim($_POST['password']));
		$this->users[$newname]['granted'] = $this->GetGrantedValue($_POST['grant'],$newname);
		return $this->SaveUserFile();
	}
	
	function GetGrantedValue($array,$username){
		global $gpAdmin;
		$scripts = admin_tools::AdminScripts();
		
		if( $username == $gpAdmin['username'] ){
			$array = array_merge($array,array('Admin_Users'));
		}
		
		$scripts = array_keys($scripts);
		if( !is_array($array) ){
			return '';
		}
		
		$diff = array_diff($scripts,$array);
		if( count($diff) == 0 ){
			return 'all';
		}
		
		return implode(',',$array);
	}
		
		
	
	function SaveUserFile(){
		global $langmessage, $dataDir;
		if( !gpFiles::SaveArray($dataDir.'/data/_site/users.php','users',$this->users) ){
			message($langmessage['OOPS']);
			return false;
		}
		message($langmessage['SAVED']);
		return true;
	}
	
	
	function ShowForm(){
		global $langmessage;
		
		$_POST += array('username'=>'','email'=>'');
		
		echo '<h2>'.$langmessage['user_permissions'].'</h2>';
		echo '<table class="bordered">';
		echo '<tr>';
			echo '<th>';
			echo $langmessage['username'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['permissions'];
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '</tr>';
			
		foreach($this->users as $username => $userinfo){
			$userinfo += array('granted'=>'');
			
			echo '<tr>';
			echo '<td>';
			echo $username;
			echo '</td>';
			echo '<td>';
				if( $userinfo['granted'] == 'all' ){
					echo 'all';
				}else{
					echo $langmessage['edit'];
					if( !empty($userinfo['granted']) ){
						echo ', ';
					}
					echo str_replace(',',', ',$userinfo['granted']);
				}
			echo '</td>';
			echo '<td>';
/*
				if( $userinfo['granted'] != 'all' ){
					echo common::Link('Admin_Users',$langmessage['permissions'],'cmd=details&username='.$username);
					echo ' &nbsp; ';
				}
*/
				echo common::Link('Admin_Users',$langmessage['details'],'cmd=details&username='.$username);
				echo ' &nbsp; ';
				echo common::Link('Admin_Users',$langmessage['password'],'cmd=changepass&username='.$username);
				echo ' &nbsp; ';
				echo common::Link('Admin_Users',$langmessage['delete'],'cmd=rm&username='.$username);
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
		
		echo '<br/>';
		
		echo '<form action="'.common::getUrl('Admin_Users').'" method="post">';
		echo '<table class="bordered">';
		echo '<tr>';
			echo '<th colspan="2">';
			echo $langmessage['new_user'];
			echo '</th>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['username'];
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="username" value="'.htmlspecialchars($_POST['username']).'" />';
			echo '</td>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['password'];
			echo '</td>';
			echo '<td>';
			echo '<input type="password" name="password" value="" />';
			echo '</td>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['repeat_password'];
			echo '</td>';
			echo '<td>';
			echo '<input type="password" name="password1" value="" />';
			echo '</td>';
			echo '</tr>';
			
		$this->DetailsForm();
		
		echo '<tr>';
			echo '<td>';
			echo '</td>';
			echo '<td>';
			echo '<input type="hidden" name="cmd" value="newuser" />';
			echo ' <input type="submit" name="aaa" value="'.$langmessage['continue'].'" />';
			echo ' <input type="reset" />';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</form>';
		
	}
	
	function DetailsForm( $values=array() ){
		global $langmessage;
		
		$values += array('granted'=>'','email'=>'');
		
		
		echo '<tr>';
			echo '<td>';
			echo $langmessage['email_address'];
			echo '</td>';
			echo '<td>';
			echo '<input type="text" name="email" value="'.htmlspecialchars($values['email']).'" />';
			echo '</td>';
			echo '</tr>';
			
		
		echo '<tr>';
			echo '<td>';
			echo $langmessage['grant_usage'];
			echo '</td>';
			echo '<td>';
		
		
		$all = false;
		$current = $values['granted'];
		if( $current == 'all' ){
			$all = true;
		}else{
			$current = ','.$current.',';
		}
		
		$scripts = admin_tools::AdminScripts();
		echo '<select name="grant[]" multiple="multiple" size="'.(count($scripts)+1).'">';
/*
		Should introduce this when the switch is made to redefining the session_save_handler

		echo '<option value="edit" selected="selected">';
			echo $langmessage['edit'];
			echo '</option>';
*/
		
		foreach($scripts as $script => $info){
			if( $all ){
				echo '<option value="'.$script.'" selected="selected">';
				
			}elseif( strpos($current,','.$script.',') !== false ){
				
				echo '<option value="'.$script.'" selected="selected">';
				
			}else{
				echo '<option value="'.$script.'">';
				
			}
			echo $info['label'];
			echo '</option>';
			
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';
		

	}
	
	function ChangePass(){
		global $langmessage;
		
		$username =& $_REQUEST['username'];
		if( !isset($this->users[$username]) ){
			message($langmessage['OOPS']);
			return;
		}
		
		
		echo '<form action="'.common::getUrl('Admin_Users').'" method="post">';
		echo '<input type="hidden" name="cmd" value="resetpass" />';
		echo '<input type="hidden" name="username" value="'.htmlspecialchars($username).'" />';

		echo '<table class="bordered">';
		echo '<tr>';
			echo '<th colspan="2">';
			echo $langmessage['change_password'];
			echo ' - ';
			echo $username;
			echo '</th>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['new_password'];
			echo '</td>';
			echo '<td>';
			echo '<input type="password" name="password" value="" />';
			echo '</td>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['repeat_password'];
			echo '</td>';
			echo '<td>';
			echo '<input type="password" name="password1" value="" />';
			echo '</td>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo '</td>';
			echo '<td>';
			echo '<input type="submit" name="aaa" value="'.$langmessage['continue'].'" />';
			echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</form>';
	}
	
	function ResetPass(){
		global $langmessage;
		
		if( !$this->CheckPasswords() ){
			return false;
		}
		
		$username = $_POST['username'];
		if( !isset($this->users[$username]) ){
			message($langmessage['OOPS']);
			return false;
		}

		$this->users[$username]['password'] = sha1(trim($_POST['password']));
		return $this->SaveUserFile();
	}
	
	function CheckPasswords(){
		global $langmessage;
		
		//see also admin_users for password checking
		if( ($_POST['password']=="") || ($_POST['password'] !== $_POST['password1'])  ){
			message($langmessage['invalid_password']);
			return false;
		}
		return true;
	}
		
	function GetUsers(){
		global $dataDir;
		
		require($dataDir.'/data/_site/users.php');
		
		$this->users = $users;
	}
	
}



	

