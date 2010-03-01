<?php
defined("is_running") or die("Not an entry point...");
includeFile('tool/parse_ini.php');


class admin_addons_tool{
	var $scriptUrl = 'Admin_Addons';
	var $addonHistory = array();
	var $addonReviews = array();
	
	
	
	//
	// Remote Browsing
	//
	
	function RemoteBrowse($script='plugins'){
		global $page;
		
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::getDir('/include/js/addons.js').'"></script>';
		
		
		//
		//javascript method... this breaks the history..back/forward/bookmarking/reload
		//
		
		
		//
		//	iframe method... 
		//

		/*
		$server = 'http://gpeasy.com/index.php/Special_Addon_Plugins';
		$server = 'http://gpeasy.loc/glacier/index.php/Special_Addon_Plugins?show=remote';
		$server = 'http://burlington.loc';
		*/
		
		//$_SERVER['HTTP_HOST'] or $_SERVER['SERVER_NAME']
		
		//$installPath = $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		
		
		// I think the best way to do this is with javascript
		//echo '<iframe src="'.$server.'" style="width:760px;height:900px;overflow:hidden;border:0 none;" id="addon_iframe" name="addon_iframe">';
		echo '<iframe style="width:760px;height:900px;overflow:hidden;border:0 none;" id="addon_iframe" name="addon_iframe" rel="'.$script.'">';
		echo '</iframe>';
		
	}

	
	
	
	
	
	//
	// Rating
	//
	
	function InitRating(){
		global $page;
		
		$page->head .= '<script type="text/javascript" language="javascript" src="'.common::getDir('/include/js/rate.js').'"></script>';
		
		//clear the data file ...  	
		//$this->SaveAddonData();
		$this->GetAddonData();
	}
	
	function GetAddonData(){
		global $dataDir;
		//review data
		$this->dataFile = $dataDir.'/data/_site/addonData.php';
		
		if( file_exists($this->dataFile) ){
			require($this->dataFile);
			$this->addonHistory = $addonData['history'];
			$this->addonReviews = $addonData['reviews'];
		}
		
	}
	
	function SaveAddonData(){
		
		$addonData = array();
		
		while( count($this->addonHistory) > 30 ){
			array_shift($this->addonHistory);
		}
		
		$addonData['history'] = $this->addonHistory;
		$addonData['reviews'] = $this->addonReviews;
		return gpFiles::SaveArray($this->dataFile,'addonData',$addonData);
	}	
	
	
	
	function RatingFunctions($cmd,&$rate_info){
		
		$arg =& $_REQUEST['arg'];
		
		if( !$this->CanRate($arg,$rate_info) ){
			message($rate_info);
			return false;
		}
			
		switch($cmd){
			case 'Update Review';
			case 'Send Review':
			if( $this->SendRating($rate_info) ){
				return false;
			}
				
			case 'rate':
			return $this->Rate($rate_info);
		}
		
		return false;
	}
	
	function ShowRating($arg,$rating){
		
		$width = 16*5;
		
		$pos = min($width,ceil($width*$rating/5));
		$pos2 = ($width-ceil($pos));
		echo '<span class="rating">';
		
			$label = '<img src="'.common::getDir('/include/imgs/blank.gif').'" alt="" border="0" height="16" width="16">';
			echo common::Link($this->scriptUrl,$label,'cmd=rate&rating=1&arg='.$arg,' rel="1" ');
			$label = '<img src="'.common::getDir('/include/imgs/blank.gif').'" alt="" border="0" height="16" width="16">';
			echo common::Link($this->scriptUrl,$label,'cmd=rate&rating=2&arg='.$arg,' rel="2" ');
			$label = '<img src="'.common::getDir('/include/imgs/blank.gif').'" alt="" border="0" height="16" width="16">';
			echo common::Link($this->scriptUrl,$label,'cmd=rate&rating=3&arg='.$arg,' rel="3" ');
			$label = '<img src="'.common::getDir('/include/imgs/blank.gif').'" alt="" border="0" height="16" width="16">';
			echo common::Link($this->scriptUrl,$label,'cmd=rate&rating=4&arg='.$arg,' rel="4" ');
			$label = '<img src="'.common::getDir('/include/imgs/blank.gif').'" alt="" border="0" height="16" width="16">';
			echo common::Link($this->scriptUrl,$label,'cmd=rate&rating=5&arg='.$arg,' rel="5" ');
			
			echo '<input type="hidden" name="rating" value="'.htmlspecialchars($rating).'" readonly="readonly"/>';
		echo '</span> ';
	}	
	
	
	function CanRate($arg,&$info){
		$messages = array();
		
		if( strpos($_SERVER['SERVER_ADDR'],'127') === 0 ){
			$messages[] = 'This installation of gpEasy is on a local server and is not accessible via the internet.';
		}
		
/*
		static $warn = false;
		if( $warn === false ){
			message('CanRate() not checking local servers.');
			$warn = true;
		}
*/
		
		$found = $this->GetAddonRateInfo($arg,$info,$message);
		if( !$found ){
			$messages[] = $message;
		}
		
		if( empty($_SERVER['HTTP_HOST']) ){
			$messages[] = 'PHP is not assigning a value to $_SERVER[\'HTTP_HOST\'].';
		}
		
		if( !function_exists('fsockopen') ){
			$messages[] = 'The <i>fsockopen()</i> function is not available.';
		}
		
		if( count($messages) == 0 ){
			return true;
		}
		
		
		$info = 'You are currently unable to rate this addon for the following reasons:';
		$info .= '<ul>';
		$info .= '<li>'.implode('</li><li>',$messages).'</li>';
		$info .='</ul>';
		
		return false;
	}
	
	function GetAddonRateInfo($arg,&$info,&$message){
		global $config;
			
		if( isset($config['addons'][$arg]) && isset($config['addons'][$arg]['id']) ){
			
			$info = array();
			$info['pass_arg'] = $config['addons'][$arg]['id'];
			$info['id'] = $config['addons'][$arg]['id'];
			$info['name'] = $config['addons'][$arg]['name'];
			$info['addonDir'] = $arg;
			return true;
			
		}
		
		if( !is_numeric($arg) ){
			$message = 'This add-on does not have an ID assigned to it. The developer must update the install configuration.';
			return false;
		}
			
			
		foreach($config['addons'] as $addonDir => $data){
			if( isset($data['id']) && ($data['id'] == $arg) ){
				
				$info = array();
				$info['id'] = $arg;
				$info['pass_arg'] = $arg;
				$info['name'] = $data['name'];
				$info['addonDir'] = $addonDir;
				return true;
			}
		}
			
		foreach($this->addonHistory as $time => $data ){
			if( isset($data['id']) && ($data['id'] == $arg) ){
				
				$info = array();
				$info['id'] = $arg;
				$info['pass_arg'] = $arg;
				$info['name'] = $data['name'];
				return true;
			}
		}
			
		$message = 'The supplied add-on ID is not in your add-on history.';
		return false;
	}
	
	function Rate($rate_info){
		global $config,$langmessage,$page;
		
		
		//get appropriate variables
		$id = $rate_info['id'];
		
		if( isset($_REQUEST['rating']) ){
			$rating = $_REQUEST['rating'];
		}elseif( isset($this->addonReviews[$id]) ){
			$rating = $this->addonReviews[$id]['rating'];
		}else{
			$rating = 5;
		}
		
		if( isset($_REQUEST['review']) ){
			$review = $_REQUEST['review'];
		}elseif( isset($this->addonReviews[$id]) ){
			$review = $this->addonReviews[$id]['review'];
		}else{
			$review = '';
		}
		
		echo '<h2>';
		echo 'Rate: '.$rate_info['name'];
		if( isset($rate_info['addonDir']) ){
			echo ' ('.$rate_info['addonDir'].') ';
		}
		echo '</h2>';
		
		if( isset($this->addonReviews[$id]) ){
			echo 'You posted the following review on '.date('M j, Y',$this->addonReviews[$id]['time']);
		}

		
		$width = 16*5;
		$pos = min($width,ceil($width*$rating/5));
		$pos2 = ($width-ceil($pos));
		
		echo '<form action="'.common::getUrl($this->scriptUrl,'cmd=rate&arg='.$rate_info['pass_arg']).'" method="post">';
		
		
		echo '<table cellpadding="7">';
		
		
		echo '<tr>';
			echo '<td>';
			echo 'Rating';
			echo '</td>';
			echo '<td>';
			echo '<span class="rating">';
			
				$label = '<img src="'.common::getDir('/include/imgs/blank.gif').'" alt="" border="0" height="16" width="16">';
				echo '<a href="javascript:void(0);" rel="1">'.$label.'</a>';
				$label = '<img src="'.common::getDir('/include/imgs/blank.gif').'" alt="" border="0" height="16" width="16">';
				echo '<a href="javascript:void(0);" rel="2">'.$label.'</a>';
				$label = '<img src="'.common::getDir('/include/imgs/blank.gif').'" alt="" border="0" height="16" width="16">';
				echo '<a href="javascript:void(0);" rel="3">'.$label.'</a>';
				$label = '<img src="'.common::getDir('/include/imgs/blank.gif').'" alt="" border="0" height="16" width="16">';
				echo '<a href="javascript:void(0);" rel="4">'.$label.'</a>';
				$label = '<img src="'.common::getDir('/include/imgs/blank.gif').'" alt="" border="0" height="16" width="16">';
				echo '<a href="javascript:void(0);" rel="5">'.$label.'</a>';
			
			echo '<input type="hidden" name="rating" value="'.htmlspecialchars($rating).'" />';
			echo '</span> ';
			echo '</td>';
		echo '</tr>';
			
		echo '<tr>';
			echo '<td>';
			echo 'Review';
			echo '</td>';
			echo '<td>';
			echo '<textarea name="review" cols="50" rows="7">';
			echo htmlspecialchars($review);
			echo '</textarea>';
			echo '</td>';
		echo '</tr>';


			
		echo '<tr>';
			echo '<td>';
			echo 'From';
			echo '</td>';
			echo '<td>';
			$host = $_SERVER['HTTP_HOST'].$config['dirPrefix'];
			echo '<input type="text" name="host"  size="50" value="'.htmlspecialchars($host).'" readonly="readonly" />';
			echo '</td>';
		echo '</tr>';
		
		echo '<tr>';
			echo '<td>';
			echo '</td>';
			echo '<td>';
			
			if( isset($this->addonReviews[$id]) ){
				echo '<input type="submit" name="cmd" value="Update Review" />';
			}else{
				echo '<input type="submit" name="cmd" value="Send Review" />';
			}
			
			echo ' ';
			echo '<input type="submit" name="cmd" value="Cancel" />';
			echo '</td>';
		echo '</tr>';
		
		
		echo '</table>';
		echo '</form>';
		
		return true;
	}
	
	function SendRating($rate_info){
		global $langmessage,$config;
		$data = array();
		
		if( !is_numeric($_POST['rating']) || ($_POST['rating'] < 1) || ($_POST['rating'] > 5 ) ){
			message($langmessage['OOPS']);
			return false;
		}
		
		$id = $rate_info['id'];
		
		if( isset($this->addonReviews[$id]) ){
			$data['review_id'] = $this->addonReviews[$id]['review_id'];
			
			//if it hasn't changed..
			if( ($_POST['rating'] == $this->addonReviews[$id]['rating'])
				&& ($_POST['review'] == $this->addonReviews[$id]['review']) ){
					message('Your review has been saved.');
					return true;
			}
		}
		
		$data['addon_id'] = $id;
		$data['rating'] = $_POST['rating'];
		$data['review'] = $_POST['review'];
		$data['cmd'] = 'rate';
		$data['HTTP_HOST'] = $_SERVER['HTTP_HOST'];
		$data['dirPrefix'] = $config['dirPrefix'];
		$review_id = $this->PingRating($data);
		if( $review_id === false ){
			return false;
		}
		
		
		//save review information
		$this->addonReviews[$id] = array();
		$this->addonReviews[$id]['rating'] = $_POST['rating'];
		$this->addonReviews[$id]['review'] = $_POST['review'];
		$this->addonReviews[$id]['review_id'] = $review_id;
		$this->addonReviews[$id]['time'] = time();
		$this->SaveAddonData();
		
		message('Your review has been saved.');
		return true;
	}
	
		
	
	function PingRating($data){
		global $langmessage;

		$host = 'gpeasy.loc';
		$path = '/glacier/index.php/Special_Addons';
		
		$host = 'www.gpeasy.com';
		$path = '/index.php/Special_Addons';
		
		
		$str = '';
		foreach($data AS $k => $v){
			$str .= urlencode($k).'='.urlencode($v).'&';
		}
		$str = substr($str,0,-1); //get rid of last &
	
		//
		//	Build Request
		//
			
			$newLine = "\r\n";
			
			$request = 'POST '.$path.' HTTP/1.1'.$newLine;
			$request .= 'Host: '.$host.$newLine;
			$request .= 'Content-Type: application/x-www-form-urlencoded'.$newLine;
			$request .= 'Content-Length: '.strlen($str).$newLine;
			$request .= 'User-Agent: gpEasy_Rating'.$newLine;
			$request .= 'Connection: close'.$newLine;
			$request .= $newLine;
			$request .= $str;
			
			
		//
		//	Send
		//
		
			$errNum=null;
			$errString=null;
			$handle = @fsockopen( $host, 80 ,$errNum, $errString, 3 );
			if(!$handle){
				message('Could not connect to '.$host.'. Please try again.'); 
				// ... "Make sure your installation of gpEasy is connected to the internet...
				return false;
			}
			
			fwrite($handle, $request);
			
		//
		//	Check Response ??
		//		do we need to do this for pings?.. only if we're going to warn users about pings that don't work
		//
		
			$gotFirstLine = false;
			$gettingHeaders = true;
			$all = '';
			$contents = '';
			while (!feof($handle)) {
				$line = fgets($handle, 4096);
				$all .= $line;
				if (!$gotFirstLine) {
					// Check line for '200'
					if (strstr($line, '200') === false) {
						message('Unable to send the review.');
						fclose($handle);
						return false;
					}
					$gotFirstLine = true;
				}
				if (trim($line) == '') {
					$gettingHeaders = false;
				}
				if (!$gettingHeaders) {
					$contents .= trim($line)."\n";
				}
			}
			fclose($handle);

			//message(htmlspecialchars($all));
			//message($contents); //JUST FOR TESTING
			
		if( empty($contents) ){
			message($langmessage['OOPS']);
			return false;
		}	
		
		//!! these responses should be more detailed
		list($response,$detail) = explode(':',$contents);
		$response = trim($response);
		$detail = trim($detail);
		if( $response == 'successful_rating_request' ){
			return $detail;
		}
		
		//invalid_rating_request
		switch($detail){
			case 'no_addon';
				message('The supplied addon id was invalid.');
			break;
			
			default:
				message($langmessage['OOPS'].'('.$detail.')');
				//message($contents);
			break;
		}
		return false;
	}
		
	function GetAvailInstall($fullPath){
		
		$iniFile = $fullPath.'/Addon.ini';
		
		
		if( !file_exists($iniFile) ){
			return false;
		}
		$array = gp_ini::ParseFile($iniFile);

		if( !isset($array['Addon_Name']) ){
			return false;
		}		
		$array += array('Addon_Version'=>'');
		return $array;
	}
	
}

