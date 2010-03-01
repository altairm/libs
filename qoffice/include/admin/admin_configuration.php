<?php
defined('is_running') or die('Not an entry point...');


class admin_configuration{
	
	var $variables;
	var $defaultVals = array();
	
	function admin_configuration(){
		global $langmessage;
		
		
		$this->variables = array(
		
						// these values exist and are used, but not necessarily needed
		
						// these values aren't used
						//'author'=>'',
						//'timeoffset'=>'',
						//'fromname'=>'',
						//'fromemail'=>'',
						//'contact_message'=>'',
						//'dateformat'=>'',

						'title'=>'',
						'keywords'=>'',
						'desc'=>'',
						'toemail'=>'',
						'recaptcha_public'=>'',
						'recaptcha_private'=>'',
						'language'=>'',
						'langeditor'=>'',
						'maximgarea'=>'',
						'jquery'=>'',
						'hidegplink'=>'',
						);
						
		$cmd = common::GetCommand();
		switch($cmd){
			case 'save_config':
				$this->SaveConfig();
			break;
		}
		
		echo '<h2>'.$langmessage['configuration'].'</h2>';
		$this->showForm();
	}
	
	
	function SaveConfig(){
		global $config, $dataDir, $langmessage;
		
		$possible = $this->variables;
		
		if( !is_numeric($_POST['maximgarea']) ){
			unset($_POST['maximgarea']);
		}
	
		foreach($_POST as $key => $value ){
			if( isset($possible[$key]) ){
				$config[$key] = $value;
			}
			
		}
		
		if( !admin_tools::SaveConfig() ){
			message($langmessage['OOPS']);
			return false;
		}
		message($langmessage['SAVED']);
	}
	
	
	function getValues(){
		global $config;
		
		if( $_SERVER['REQUEST_METHOD'] != 'POST'){
			$show = $config;
		}else{
			$show = $_POST;
		}
		if( empty($show['jquery']) ){
			$show['jquery'] = 'local';
		}
	
		return $show;
	}
	
	function getPossible(){
		global $rootDir;
		
		$possible = $this->variables;
		
		//$langDir = $rootDir.'/include/thirdparty/fckeditor/editor/lang'; //fckeditor
		$langDir = $rootDir.'/include/thirdparty/ckeditor/lang'; //ckeditor
		
		$possible['langeditor'] = gpFiles::readDir($langDir,'js');
		unset($possible['langeditor']['_languages']);
		asort($possible['langeditor']);
		
		//website language
		$langDir = $rootDir.'/include/languages';
		$possible['language'] = gpFiles::readDir($langDir,1);
		asort($possible['language']);
		
		//jQuery
		$possible['jquery'] = array('local'=>'Local','google'=>'Google');
		
		//jQuery
		$possible['hidegplink'] = array(''=>'Show','hide'=>'Hide');
		
		return $possible;
	}
	
	function showForm(){
		global $langmessage;
		$possibleValues = $this->getPossible();
		
		
		$array = $this->getValues();
		
		echo '<form action="'.common::getUrl('Admin_Configuration').'" method="post">';
		echo '<table cellpadding="4" class="bordered">';
		
		//order by the possible values
		foreach($possibleValues as $key => $possibleValue){
			if( isset($array[$key]) ){
				$value = $array[$key];
			}else{
				$value = '';
			}
			
			echo "\n\n";
			echo '<tr><td>';
			echo '<b>';
			if( isset($langmessage[$key]) ){
				echo $langmessage[$key];
			}else{
				echo $key;
			}
			echo '</b>';
			echo '</td>';
			echo '<td>';
			
			if( $possibleValues[$key] === false ){
				echo 'unavailable';
			}elseif( is_array($possibleValues[$key]) ){
				$this->formSelect($key,$possibleValues[$key],$value);
			}else{
				$this->formInput($key,$value);
			}
			
			if( isset($this->defaultVals[$key]) ){
				echo '<br/> <span class="sm">';
				echo $this->defaultVals[$key];
				echo '</span>';
			}
			

/*
			echo '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td colspan="2">';

*/
			if( isset($langmessage['about_config'][$key]) ){
				echo '<br/>';
				echo $langmessage['about_config'][$key];
			}
			echo '</td></tr>';
			
			
		}
		
		echo '<tr>';
		echo '<td colspan="3">';
		echo '<div style="text-align:center;margin:1em">';
		echo '<input type="hidden" name="cmd" value="save_config" />';
		echo '<input value="'.$langmessage['save'].'" type="submit" name="aaa" accesskey="s" />';
		echo ' &nbsp; ';
 		echo '<input type="reset"  />';
 		echo '</div>';
 		echo '</td>';
 		echo '</tr>';
		
		echo '</table>';
		echo '</form>';
		
	}
	
	
	//
	//	Form Functions
	//

	function formInput($name,$value){
		global $langA;
		
		$len = (strlen($value)+20)/20;
		$len = round($len);
		$len = $len*20;
		
		$value = htmlspecialchars($value);
		
		static $textarea = '<textarea name="%s" cols="30" rows="%d">%s</textarea>';
		if($len > 100 && (strpos($value,' ') != false) ){
			$cols=40;
			$rows = ceil($len/$cols);
			echo sprintf($textarea,$name,$rows,$value);
			return;
		}
		
		$len = min(40,$len);
		static $text = '<input name="%s" size="%d" value="%s" type="text"/>';
		echo "\n".sprintf($text,$name,$len,$value);
	}
	
	function formSelect($name,$possible,$value=null){
		
		echo "\n".'<select name="'.$name.'">';
		if( !isset($possible[$value]) ){
			echo '<option value="" selected="selected"></option>';
		}
		foreach($possible as $optionKey => $optionValue){
			
			
			if( is_array($optionValue) ){
				echo '<optgroup label="'.$optionKey.'">';
				foreach( $optionValue as $subKey => $subValue){
				
					if($subKey == $value){
						$focus = ' selected="selected" ';
					}else{
						$focus = '';
					}
					echo '<option value="'.htmlspecialchars($subKey).'" '.$focus.'>'.$subValue.'</option>';
				}
				
				echo '</optgroup>';
				continue;
			}
			
			if($optionKey == $value){
				$focus = ' selected="selected" ';
			}else{
				$focus = '';
			}
			echo '<option value="'.htmlspecialchars($optionKey).'" '.$focus.'>'.$optionValue.'</option>';
		}
		echo '</select>';
	}	
	
}

