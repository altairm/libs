<?php
defined("is_running") or die("Not an entry point...");

require_once($GLOBALS['rootDir'].'/include/admin/admin_users.php');


class admin_password extends admin_users{
	
	function admin_password(){
		$this->GetUsers();
		$cmd = common::GetCommand();
		
		switch($cmd){
			case 'changepass':
				$this->DoChange();
			break;
		}
	
		$this->PasswordForm();
		
	}
	
	function DoChange(){
		global $langmessage, $gpAdmin;
		
		//see also admin_users for password checking
		if( !$this->CheckPasswords() ){
			return false;
		}

		
		$username = $gpAdmin['username'];
		$userinfo =& $this->users[$username];
		if( empty($userinfo) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$oldpass = sha1(trim($_POST['oldpassword']));
		if( $userinfo['password'] != $oldpass ){
			message($langmessage['couldnt_reset_pass']);
			return false;
		}
		
		$this->users[$username]['password'] = sha1(trim($_POST['password']));
		$this->SaveUserFile();
	}
	
	function PasswordForm(){
		global $langmessage, $gpAdmin;
		
		
		echo '<form action="'.common::getUrl('Admin_Password').'" method="post">';
		echo '<table class="bordered">';
		echo '<tr>';
			echo '<th colspan="2">';
			echo $langmessage['change_password'];
			echo ' - ';
			echo $gpAdmin['username'];
			echo '</th>';
			echo '</tr>';
		echo '<tr>';
			echo '<td>';
			echo $langmessage['old_password'];
			echo '</td>';
			echo '<td>';
			echo '<input type="password" name="oldpassword" value="" />';
			echo '</td>';
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
			echo '<input type="hidden" name="cmd" value="changepass" />';
			echo ' <input type="submit" name="aaa" value="'.$langmessage['continue'].'" />';
			echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '</form>';		
	}
	
}

