<?php
defined('is_running') or die('Not an entry point...');

class admin_extra{
	
	function admin_extra(){
		global $langmessage;
		
		$cmd = common::GetCommand();
		
		switch($cmd){
			
			case $langmessage['cancel']:
				$this->Redirect();
			break;
			
			case 'save':
				if( $this->SaveExtra() ){
					$this->EditExtras();
					break;
				}
			case 'edit':
				$this->EditExtra();
			break;
			
			default:
				$this->ShowExtras();
			break;
		}
	}
	function EditExtras(){
		global $dataDir,$langmessage;
		
		$extrasFolder = $dataDir.'/data/_extra';
		$files = gpFiles::ReadDir($extrasFolder);
		asort($files);
		
		echo '<h2>'.$langmessage['theme_content'].'</h2>';
		echo '<table class="bordered">';
		echo '<tr>';
			echo '<th>';
			echo 'Area';
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '<th>';
			echo 'Usage';
			echo '</th>';
			echo '</tr>';
		
		foreach($files as $file){
			$extraName = $file;
			echo '<tr>';
				echo '<td>';
				echo $extraName;
				echo '</td>';
				echo '<td>';
				echo common::Link('Admin_Extra',$langmessage['edit'],'cmd=edit&file='.$file);
				echo '</td>';
				echo '<td>';
				echo '&lt;?php $page->GetExtra( \''.$extraName.'\' ); ?&gt;';
				echo '</td>';
				echo '</tr>';
		}
		echo '</table>';
		
		
		echo '<h3>Adding More Areas</h3>';
		echo 'To add more editable theme areas to your template, all you have to do is add additional calls to <em>$page->GetExtra(...)</em> in your template.php file (located in the /themes directory of your server.';
		echo '<h4>Example</h4>';
		echo '&lt;?php $page->GetExtra( \'Side_Menu\' ) ?&gt;';
		
	}

	
	function ShowExtras(){
		global $dataDir,$langmessage;
		
		$extrasFolder = $dataDir.'/data/_extra';
		$files = gpFiles::ReadDir($extrasFolder);
		asort($files);
		
		echo '<h2>'.$langmessage['theme_content'].'</h2>';
		echo '<table class="bordered">';
		echo '<tr>';
			echo '<th>';
			echo 'Area';
			echo '</th>';
			echo '<th>';
			echo $langmessage['options'];
			echo '</th>';
			echo '<th>';
			echo 'Usage';
			echo '</th>';
			echo '</tr>';
		
		foreach($files as $file){
			$extraName = $file;
			echo '<tr>';
				echo '<td>';
				echo $extraName;
				echo '</td>';
				echo '<td>';
				echo common::Link('Admin_Extra',$langmessage['edit'],'cmd=edit&file='.$file);
				echo '</td>';
				echo '<td>';
				echo '&lt;?php gpOutput::Get(\'Extra\', \''.$extraName.'\' ); ?&gt;';
				echo '</td>';
				echo '</tr>';
		}
		echo '</table>';
		
		
		echo '<h3>Adding More Areas</h3>';
		echo 'To add more editable theme areas to your template, all you have to do is add additional calls to <em>gpOutput::Get(\'Extra\', ...)</em> in your template.php file (located in the /themes directory of your server.';
		echo '<h4>Example</h4>';
		echo '&lt;?php gpOutput::Get(\'Extra\', \'Side_Menu\' ) ?&gt;';
		
	}
	
		
	function EditExtra(){
		global $langmessage,$dataDir;
		
		$title = gpFiles::CleanTitle($_REQUEST['file']);
		$file = $dataDir.'/data/_extra/'.$title.'.php';
		$content = '';
		
		if( file_exists($file) ){
			ob_start();
			include($file);
			$content = common::get_clean();
		}
		
		
		echo '<form  action="'.common::getUrl('Admin_Extra','file='.$title).'" method="post">';
		echo '<h2>'.$langmessage['theme_content'].' &gt; '.$title.'</h2>';
		echo '<input type="hidden" name="cmd" value="save" />';
		if( !empty($_REQUEST['return']) ){
			echo '<input type="hidden" name="return" value="'.htmlspecialchars($_REQUEST['return']).'" />';
		}
			
		common::UseFCK($content);
		
		echo '<input type="submit" name="" value="'.$langmessage['save'].'" />';
		echo '<input type="submit" name="cmd" value="'.$langmessage['cancel'].'" />';
		echo '</form>';		
	}
	
	function SaveExtra(){
		global $langmessage, $dataDir, $gptitles;
		
			
		$title = gpFiles::CleanTitle($_REQUEST['file']);
		$file = $dataDir.'/data/_extra/'.$title.'.php';
		$text =& $_POST['gpcontent'];
		gpFiles::rmPHP($text);
		admin_tools::tidyFix($text);
		
		
		if( !gpFiles::SaveFile($file,$text) ){
			message($langmessage['OOPS']);
			$this->EditExtra();	
			return false;
		}
		
		$this->Redirect();

		message($langmessage['SAVED']);
		return true;
	}
	
	function Redirect(){
		if( !empty($_POST['return']) ){
			$return = $_POST['return'];
			$return = str_replace('cmd=','x=',$return);
			header('Location: '.common::getUrl($_POST['return'],false));
			die();
		}
	}
}
