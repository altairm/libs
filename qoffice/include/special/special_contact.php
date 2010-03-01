<?php
defined('is_running') or die('Not an entry point...');


class special_contact{
	var $sent = false;
	
	function special_contact(){
		global $page,$langmessage,$config;
		
		
		
		//check ini settings
/*
		$sendmail = ini_get('sendmail_from');
		if( empty($sendmail) ){
			ini_set('sendmail_from', 'AutomatedSender@'.$server);
			
			if( common::LoggedIn() ){
				message('<em>sendmail_from</em> is not set in your php.ini file. This may prevent the contact form from working properly.');
			}
		}
*/
		
/*
		$sendmail_path = ini_get('sendmail_path');
		if( empty($sendmail_path) ){
			if( common::LoggedIn() ){
				message('<em>sendmail_from</em> is not set in your php.ini file. This may prevent the contact form from working properly.');
			}
		}
*/
			
		
/*
		$smtp = ini_get('SMTP');
		if( empty($smtp) ){
			//ini_set('SMTP','smtp.example.com'); 
			
			if( common::LoggedIn() ){
				message('<em>SMTP</em> is not set in your php.ini file. This may prevent the contact form from working properly.');
			}
		}
*/
		
		
		if( empty($config['toemail']) ){
			
			if( common::LoggedIn() ){
				$url = common::GetUrl('Admin_Configuration');
				message($langmessage['enable_contact'],$url);
			}

			echo $langmessage['not_enabled'];
			return;
		}
		
		
		$cmd = common::GetCommand();
		switch($cmd){
			case 'send':
				if( $this->SendMessage() ){
					$this->sent = true;
					break;
				}
			default:
			break;
		}
		
		$this->ShowForm();
		
	}
	
	
	function CheckCaptcha(){
		global $page,$langmessage,$config,$rootDir;
		
		if( empty($config['recaptcha_public']) || empty($config['recaptcha_private']) ){
			return true;
		}
		
		require_once($rootDir.'/include/thirdparty/recaptchalib.php');
		$resp = recaptcha_check_answer($config['recaptcha_private'],
										$_SERVER['REMOTE_ADDR'],
										$_POST['recaptcha_challenge_field'],
										$_POST['recaptcha_response_field']);


		
		if (!$resp->is_valid) {
			message($langmessage['INCORRECT_CAPTCHA']);
			if( common::LoggedIn() ){
				message($langmessage['recaptcha_said'],$resp->error);
			}
			return false;
		}
			
		

		return true;
		
	}
	
	
	function SendMessage(){
		global $langmessage,$config;
		
		$headers = array();
		
		//captcha
		if( !$this->CheckCaptcha() ){
			return;
		}
		
		//subject
		$_POST += array('subject'=>'');
		$_POST['subject'] = strip_tags($_POST['subject']);
		
		//message
		$tags = '<p><div><span><font><b><i><tt><em><i><a><strong><blockquote>';
		$message = nl2br(strip_tags($_POST['message'],$tags));
		
		
		//reply name
		if( !empty($_POST['email']) ){
			
			//check format
			if( !eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $_POST['email']) ){
				message($langmessage['invalid_email']);
				return false;
			}
			
			$replyName = str_replace(array("\r","\n"),array(' '),$_POST['name']);
			$replyName = strip_tags($replyName);
			$headers[] = 'Reply-To: '.$replyName.'<'.$_POST['email'].'>';
		}

		includeFile('tool/email.php');
		if( gp_email::SendEmail($config['toemail'], $_POST['subject'], $message, $headers) ){
			message($langmessage['message_sent']);
			return true;
		}
		
		message($langmessage['OOPS']);
		return false;
	}
	
	function ShowForm(){
		global $page,$langmessage,$config,$rootDir;
		
		$attr = '';
		if( $this->sent ){
			$attr = ' readonly="readonly" ';
		}
			
		$_GET += array('name'=>'','email'=>'','subject'=>'','message'=>'');
		$_POST += array('name'=>$_GET['name'],'email'=>$_GET['email'],'subject'=>$_GET['subject'],'message'=>$_GET['message']);
		
		
		echo '<form class="contactform" action="'.common::getUrl('Special_Contact').'" method="post">';
		echo gpOutput::GetExtra('Contact');
		echo '<table>';
		echo '<tr>';
			echo '<td class="left">';
			gpOutput::GetText('your_name');
			//echo $langmessage['your_name'];
			echo '</td>';
			echo '<td>';
			echo '<input class="input" type="text" name="name" value="'.htmlspecialchars($_POST['name']).'" '.$attr.' />';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td class="left">';
			gpOutput::GetText('your_email');
			echo '</td>';
			echo '<td>';
			echo '<input class="input" type="text" name="email" value="'.htmlspecialchars($_POST['email']).'" '.$attr.'/>';
			echo '</td>';
			echo '</tr>';
			
		echo '<tr>';
			echo '<td class="left">';
			gpOutput::GetText('subject');
			echo '</td>';
			echo '<td>';
			echo '<input class="input" type="text" name="subject" value="'.htmlspecialchars($_POST['subject']).'" '.$attr.'/>';
			echo '</td>';
			echo '</tr>';
		echo '<tr>';
			echo '<td class="left">';
			gpOutput::GetText('extra');
			echo '</td>';
			echo '<td>';
			echo '<input class="input" type="text" name="extra" value="'.htmlspecialchars($_POST['extra']).'" '.$attr.'/>';
			echo '</td>';
			echo '</tr>';

		echo '<tr>';
			echo '<td class="left">';
			gpOutput::GetText('message');
			echo '</td>';
			echo '<td>';
			echo '<textarea name="message" '.$attr.'>';
			echo htmlspecialchars($_POST['message']);
			echo '</textarea>';
			echo '</td>';
			echo '</tr>';
			
		if( !empty($config['recaptcha_public']) && !empty($config['recaptcha_private']) ){
			echo '<tr>';
				echo '<td class="left">';
				gpOutput::GetText('captcha');
				echo '</td>';
				echo '<td>';
				require_once($rootDir.'/include/thirdparty/recaptchalib.php');
				echo recaptcha_get_html($config['recaptcha_public']);
				echo '</td>';
				echo '</tr>';
		}

			
		echo '<tr>';
			echo '<td class="left">';
			echo '</td>';
			echo '<td>';
			if( $this->sent ){
				gpOutput::GetText('message_sent');
			}else{
				echo '<input type="hidden" name="cmd" value="send" />';
				//echo '<input type="submit" name="aaa" value="'.$langmessage['send_message'].'" />';
				$html = '<input type="submit" name="aaa" value="%s" />';
				gpOutput::GetText('send_message',$html);
			}
			echo '</td>';
			echo '</tr>';
			
		echo '</table>';
		echo '</form>';
	}
	
}
