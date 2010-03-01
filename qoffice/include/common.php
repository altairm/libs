<?php
defined('is_running') or die('Not an entry point...');

ini_set( 'session.use_only_cookies', '1' );
ini_set( 'default_charset', 'utf-8' );

error_reporting(E_ALL);
set_error_handler('showError');
if( defined('gpdebug') ){
	error_reporting(E_ALL);
}else{
	error_reporting(0);
}



//error_reporting(E_ERROR | E_WARNING | E_PARSE);


$gpversion = '1.6RC2';
$addonDataFolder = false;//deprecated
$addonCodeFolder = false;//deprecated
$addonPathData = false;
$addonPathCode = false;



if( !defined('E_STRICT')){
	define('E_STRICT',2048);
}

/* from wordpress */
// Fix for IIS, which doesn't set REQUEST_URI
if ( empty( $_SERVER['REQUEST_URI'] ) ) {

	// IIS Mod-Rewrite
	if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
	}
	// IIS Isapi_Rewrite
	else if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
	}
	else
	{
		// Use ORIG_PATH_INFO if there is no PATH_INFO
		if ( !isset($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO']) )
			$_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];

		// Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
		if ( isset($_SERVER['PATH_INFO']) ) {
			if ( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'] )
				$_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
			else
				$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
		}

		// Append the query string if it exists and isn't null
		if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
			$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
		}
	}
}


function showError($errno, $errmsg, $filename, $linenum, $vars){
	
	if( !defined('gpdebug') ){
		return;
	}
	
	// for "Undefined variable"
	if( $errno === 2048 ){
		return;
	}
	
	// for functions prepended with @ symbol to suppress errors
	if($errno === 0){
		return;
	}	

	
	 $errortype = array (
				E_ERROR				=> "Error",
				E_WARNING			=> "Warning",
				E_PARSE				=> "Parsing Error",
				E_NOTICE 			=> "Notice",
				E_CORE_ERROR		=> "Core Error",
				E_CORE_WARNING 		=> "Core Warning",
				E_COMPILE_ERROR		=> "Compile Error",
				E_COMPILE_WARNING 	=> "Compile Warning",
				E_USER_ERROR		=> "User Error",
				E_USER_WARNING 		=> "User Warning",
				E_USER_NOTICE		=> "User Notice",
				E_STRICT			=> "Runtime Notice"
			 );

	$mess = '';
	$mess .= '<fieldset style="padding:1em">';
	$mess .= '<legend>'.$errortype[$errno].' ('.$errno.')</legend> '.$errmsg;
	$mess .= '<br/> &nbsp; &nbsp; <b>in:</b> '.$filename;
	$mess .= '<br/> &nbsp; &nbsp; <b>on line:</b> '.$linenum;
	
	if( mysql_errno() ){
		$mess .= '<br/> &nbsp; &nbsp; Mysql Error ('.mysql_errno().')'. mysql_error();
	}
	if( ($errno !== E_NOTICE) &&($errno != E_STRICT)){
		$mess .= '<div><a href="javascript:void(0)" onclick="this.nextSibling.style.display=\'block\';;return false;">Show Backtrace</a>';
		$mess .= '<div style="display:none">';
		
		$temp = debug_backtrace();
		@array_shift($temp); //showError2()
		@array_shift($temp); //showError()
		$mess .= showArray($temp);
		
		$mess .= '</div>';
		$mess .= '</div>';
	}
	$mess .= '</p>';
	$mess .= '</fieldset>';
	
	echo $mess;
}




function SetGlobalPaths($DirectoriesAway){
	global $dataDir, $dirPrefix, $rootDir;
	
	$rootDir = str_replace('\\','/',dirname(dirname(__FILE__)));
	
	
	//dataDir
	if( isset($_SERVER['SCRIPT_FILENAME']) ){
		$dataDir = ReduceGlobalPath($_SERVER['SCRIPT_FILENAME'],$DirectoriesAway);
	}else{
		$dataDir = GETENV('SCRIPT_FILENAME');
		if( $dataDir !== false ){
			$dataDir = ReduceGlobalPath($dataDir,$DirectoriesAway);
		}else{
			$dataDir = $rootDir;
		}
	}
	
	
	//dirPrefix
	if( isset($_SERVER['SCRIPT_NAME']) ){
		$dirPrefix = $_SERVER['SCRIPT_NAME'];
	}else{
		$dirPrefix = GETENV($_SERVER['SCRIPT_NAME']);
	}
	$dirPrefix = ReduceGlobalPath($dirPrefix,$DirectoriesAway);
	
	if( $dirPrefix == '/' ){
		$dirPrefix = '';
	}
	
	
	
	// Not entirely secure: http://blog.php-security.org/archives/72-Open_basedir-confusion.html
	// Only allowed to tighten open_basedir in php 5.3+
	if( $dataDir !== $rootDir ){
		ini_set('open_basedir',$dataDir);
	}
	
}

function ReduceGlobalPath($path,$DirectoriesAway){
	$path = dirname($path);
	
	$i = 0;
	while($i < $DirectoriesAway){
		$path = dirname($path);
		$i++;
	}
	return str_replace('\\','/',$path);
}


//If Magic Quotes
if( get_magic_quotes_gpc() ){
	fix_magic_quotes( $_COOKIE );
	fix_magic_quotes( $_ENV );
	fix_magic_quotes( $_GET );
	fix_magic_quotes( $_POST );
	fix_magic_quotes( $_REQUEST );
	fix_magic_quotes( $_SERVER );
}

//If Register Globals
if( ini_get('register_globals') ){
	foreach($_REQUEST as $key => $value){
		$key = strtolower($key);
		if( ($key == 'globals') || $key == '_post'){
			die('Hack attempted.');
		}
	}
}


function fix_magic_quotes( &$arr ) {
	foreach( $arr as $key => $val ) {
		if( is_array( $val ) ) {
			fix_magic_quotes( $arr[$key] );
		} else {
			$arr[$key] = stripslashes( $val );
		}
	}
}

function message(){
	global $wbMessageBuffer;
	$wbMessageBuffer[] = func_get_args();
}
function includeFile( $file){
	global $rootDir;
	require_once( $rootDir.'/include/'.$file );
}
	


function showArray($array){
	if( is_object($array) ){
		$array = get_object_vars($array);
	}

	$text = array();
	$text[] = '<table cellspacing="0" cellpadding="7" class="tableRows" border="0">';
	if(is_array($array)){
		$odd = null;
		$odd2 = null;
		
		foreach($array as $key => $value){
			
			if($odd2==1){
				$odd = 'bgcolor="white"';
				//$odd = ' class="tableRowEven" ';
				$odd2 = 2;
			}else{
				$odd = 'bgcolor="#ddddee"';
				//$odd = ' class="tableRowOdd" ';
				$odd2 = 1;
			}
			$text[] = '<tr '.$odd.'><td>';	
 			$text[] = $key;
			$text[] = "</td><td>";
			if( !empty($value) ){
				if( is_object($value) || is_array($value) ){
					$text[] = showArray($value);
				}elseif(is_string($value)||is_numeric($value)){
					$text[] = htmlspecialchars($value);
				}elseif( is_bool($value) ){
					if($value){
						$text[]= '<tt>TRUE</tt>';
					}else{
						$text[] = '<tt>FALSE</tt>';
					}
				}else{
					$text[] = '<b>--unknown value--:</b> '.gettype($value);
				}
			}
			$text[] = "</td></tr>";
		}
	}else{
		$text[] = '<tr><td>'.$array.'</td></tr>';
	}
	$text[] = "</table>";

	return "\n".implode("\n",$text)."\n";
}



class common{
	
	
	function Link($href,$label,$query='',$attr=''){
		global $config;
		
		if( strpos($attr,'title="') === false){
			$attr .= ' title="'.htmlspecialchars(strip_tags($label)).'" ';
		}
		
		$href = str_replace('&','&amp;',$href);
		$label = str_replace('&','&amp;',$label);
		$query = str_replace('&','&amp;',$query);
		
		
		if( !empty($query) ){
			$query = '?'.$query;
		}
		
		return '<a href="'.$config['dirPrefix'].'/'.$config['indexfile'].'/'.$href.$query.'" '.$attr.'>'.$label.'</a>';
	}
	
	function AbsoluteLink($href,$label,$query='',$attr=''){
		global $config;
		
		$query = str_replace('&','&amp;',$query);
		$label = str_replace('&','&amp;',$label);
		$href = str_replace('&','&amp;',$href);
		
		if( strpos($attr,'title="') === false){
			$attr .= ' title="'.htmlspecialchars(strip_tags($label)).'" ';
		}		
		
		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}else{
			$server = $_SERVER['SERVER_NAME'];
		}		

		if( !empty($query) ){
			$query = '?'.$query;
		}
			
		return '<a href="http://'.$server.$config['dirPrefix'].'/'.$config['indexfile'].'/'.$href.$query.'" '.$attr.'>'.$label.'</a>';
		
	}
	
	function GetUrl($url,$query='',$ampersands=true){
		global $config;
		
		if( $ampersands ){
			$query = str_replace('&','&amp;',$query);
			$url = str_replace('&','&amp;',$url);
		}

		if( !empty($query) ){
			$query = '?'.$query;
		}			
		return $config['dirPrefix'].'/'.$config['indexfile'].'/'.$url.$query;
	}
	
	function escape(&$content){
		return str_replace(array('\\','"',"\n","\r"),array('\\\\','\"','\n','\r'),$content);
	}
	
	
	function GetDir($dir){
		global $dirPrefix;
		return $dirPrefix.$dir;
	}
	
	
	function GetLabel($title,$amp=true){
		global $gptitles,$langmessage,$config;
		
		$return = '';
		$info =& $gptitles[$title];
		if( !isset($gptitles[$title]) ){
			$return = $title;
			
		}elseif( isset($info['lang_index']) ){
			$return = $langmessage[$info['lang_index']];
			
		}else{
			$return = $info['label'];
		}
		if( $amp ){
			return str_replace('&','&amp;',$return);
		}else{
			return $return;
		}
	}
	
	/* deprecated */
	function UseFCK($contents,$name='gpcontent'){
		common::UseCK($contents,$name);
	}
	
	/* ckeditor 3.0 
		- Does not have a file browser
	*/
	function UseCK($contents,$name='gpcontent'){
		
		echo "\n\n";
		
		global $rootDir,$config;
		echo '<textarea name="'.$name.'" style="width:90%" rows="20" cols="100" class="CKEDITAREA">';
		echo htmlspecialchars($contents);
		echo '</textarea><br/>';
		echo '<script type="text/javascript" src="'. common::getDir('/include/thirdparty/ckeditor/ckeditor.js') .'"></script>';
		echo '<script type="text/javascript">';
		
		echo 'CKEDITOR.replaceAll( function(tarea,config){';
		echo 'config.filebrowserBrowseUrl = "'.common::GetDir('/include/admin/admin_browser.html').'";';
		echo 'config.filebrowserImageBrowseUrl = "'.common::GetDir('/include/admin/admin_browser.html?dir=%2Fimage').'";';
		echo 'config.filebrowserFlashBrowseUrl = "'.common::GetDir('/include/admin/admin_browser.html?dir=%2Fflash').'";';
		echo 'config.customConfig = "'.common::GetDir('/include/js/ckeditor_config.js').'";';
		echo 'return true;';
		echo '});';
		
		echo '</script>';
		
		echo "\n\n";
		
	}

	
	
	
	function AddColorBox(){
		global $page;
		static $init = false;
		
		if( $init ){
			return;
		}
		$init = true;
		
		$folder = 'colorbox135';
		$folder = 'colorbox136';
		$style = 'example1';
		$page->admin_js = true;
		
		$page->head .= '<link type="text/css" media="screen" rel="stylesheet" href="'.common::getDir('/include/thirdparty/'.$folder.'/'.$style.'/colorbox.css').'" />';
		$page->head .= '<script type="text/javascript" src="'.common::getDir('/include/thirdparty/'.$folder.'/colorbox/jquery.colorbox.js').'"></script>';
	}
	
	function GetConfig() {
		global $config, $langmessage, $rootDir, $gptitles, $gpmenu, $dataDir;
		
		//page information
		require($dataDir.'/data/_site/pages.php');
		$gptitles = $pages['gptitles'];
		$gpmenu = $pages['gpmenu'];
		
		
		//get config
		require($dataDir.'/data/_site/config.php');
		$config += array('theme_handlers'=>array());
		if($config['language']=='') $config['language']='en';
		if($config['langeditor']=='') $config['langeditor']='en';
		if( !isset($config['maximgarea']) ) $config['maximgarea'] = '691200' ;
		if( !isset($config['linkto']) ) $config['linkto'] = 'Powered by <a href="http://gpEasy.com" title="The Fast and Easy CMS">gpEasy CMS</a>';
		if( !isset($config['check_uploads']) ) $config['check_uploads'] = true;
		
		//$config['theme_text'] was created in 1.6RC1, decided against in 1.6RC2
		if( !isset($config['customlang']) ){
			$config['customlang'] = array();
		}
		if( isset($config['theme_text']) ){
			foreach($config['theme_text'] as $text){
				$config['customlang'] += $text;
			}
			unset($config['theme_text']);
		}
		//end $config['theme_text'] fix
			
		
				
		//set homepath
		reset($gptitles);
		reset($gpmenu);
		$config['homepath'] = key($gpmenu);//homepath is simply the first title in $gpmenu
		
		
		//$config['dirPrefix']
		$config['dirPrefix'] = $GLOBALS['dirPrefix'];
		
		
		//upgrade?
		if( !isset($config['gpversion']) ){
			require($rootDir.'/include/tool/upgrade.php');
			new gpupgrade();
		}
		
		//get language file
		common::GetLangFile('main.php');
		
	}
	
	
	function GetLangFile($file='main.php',$language=false){
		global $rootDir, $config, $langmessage;
		
		if( $language === false ){
			$language = $config['language'];
		}
		
		
		$fullPath = $rootDir.'/include/languages/'.$language.'/'.$file;
		if( file_exists($fullPath) ){
			include($fullPath);
			return;
		}
		
		//try to get the english file
		$fullPath = $rootDir.'/include/languages/en/'.$file;
		if( file_exists($fullPath) ){
			include($fullPath);
		}
		
	}
	
	function PageType($title){
		global $gptitles;
		
		$type = common::SpecialOrAdmin($title);
		if( $type !== false ){
			return $type;
		}
		
		if( !isset($gptitles[$title]) ){
			return 'page';
		}
		
		$titleInfo = $gptitles[$title];
		if( !isset($titleInfo['type']) ){
			return 'page';
		}
		
		return $titleInfo['type'];
	}
	
	function SpecialOrAdmin($title){
		if( substr($title,0,5) == 'Admin' ){
			return 'admin';
		}
		if( substr($title,0,7) == 'Special' ){
			return 'special';
		}
		return false;
	}
	
	function WhichPage(){
		global $config;
		
		
		//backwards support, redirect
		if( isset($_GET['r']) ){
			$path = $_GET['r'];
			$path = gpFiles::CleanTitle($path);
			header('Location: '.common::getUrl($path,false));
		}
		
		
		$path = $_SERVER['REQUEST_URI'];
		$pos = strpos($path,'/index.php');
		if( $pos !== false ){
			$path = substr($path,$pos+10);
		}
		$pos = strpos($path,'?');
		if( $pos !== false ){
			$path = substr($path,0,$pos);
		}
		$path = rawurldecode($path); //%20 ...
		//$path = trim($path,'/');
		$path = gpFiles::CleanTitle($path);
		if( empty($path) ){
			return $config['homepath'];
		}
		return $path;
		
	}
	
	function get_clean(){
		if( function_exists('ob_get_clean') ){
			return ob_get_clean();
		}
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}

	//only starts session tracking if needed
	function sessions(){
		global $config,$langmessage, $rootDir, $dataDir, $gpAdmin;
		
		$start = false;
		$cmd = common::GetCommand();
		if( $cmd ){
			$start = true;
		}elseif( isset($_COOKIE['gpEasy']) ){
			$start = true;
		}
		
		if( $start === false ){
			return;
		}
		
		includeFile('tool/sessions.php');
		includeFile('admin/admin_tools.php');
		
		$start = true;
		switch( $cmd ){
			case 'logout':
				gpsession::LogOut();
				$start = false;
			return;
			case 'login':
				gpsession::LogIn();
				$start = false;
			break;
		}
		
		if( $start && isset($_COOKIE['gpEasy']) ){
			gpsession::start();
		}
	}
	

	
	function LoggedIn(){
		global $config,$gpAdmin;
		static $loggedin;
		
		if( isset($loggedin) ){
			return $loggedin;
		}
		
		if( !isset($gpAdmin) || !isset($gpAdmin['adminuser']) ){
			//message('logged in false1 '.showArray($gpAdmin));
			$loggedin = false;
			return false;
		}
		if( $gpAdmin['adminuser'] != common::IP($_SERVER['REMOTE_ADDR']) ){
			$loggedin = false;
			return false;
		}
		
		$loggedin = true;
		return true;
	}
	
	function IP($ip){
		$level = 2;
		$temp = explode('.',$ip);
		
		$i = 0;
		while( $level > $i){
			array_pop($temp);
			$i++;
		}
		
		$checkIP = array_shift($temp); //don't pad with zero's for first part
		foreach($temp as $num){
			$checkIP .= str_pad($num,3,'0',STR_PAD_LEFT); 
		}

		return $checkIP;
	}		
	
	
	//Don't use $_REQUEST here because SetCookieArgs() uses $_GET
	function GetCommand($type='cmd'){
		common::SetCookieArgs();
		
		if( isset($_POST[$type]) ){
			return $_POST[$type];
		}
		
		if( isset($_GET[$type]) ){
			return $_GET[$type];
		}
		return false;
	}
	
	
	//used for receiving arguments from javascript without having to put variables in the $_GET request
	//nice for things that shouldn't be repeated!
	function SetCookieArgs(){
		static $done = false;
		
		if( $done ){
			return;
		}
		
		//get cookie arguments
		if( !isset($_COOKIE['cookie_cmd']) ){
			return;
		}
		$test = $_COOKIE['cookie_cmd'];
		if( $test{0} === '?' ){
			$test = substr($test,1);
		}
		parse_str($test,$_GET);
		$done = true;
	}	
	
	
	
	
	

	
	
	function OrganizeFrequentScripts($page){
		global $gpAdmin;
		
		if( !isset($gpAdmin['freq_scripts']) ){
			$gpAdmin['freq_scripts'] = array();
		}
		if( !isset($gpAdmin['freq_scripts'][$page]) ){
			$gpAdmin['freq_scripts'][$page] = 0;
		}else{
			$gpAdmin['freq_scripts'][$page]++;
			if( $gpAdmin['freq_scripts'][$page] >= 10 ){
				common::CleanFrequentScripts();
			}
		}

		arsort($gpAdmin['freq_scripts']);
	}
	
	function CleanFrequentScripts(){
		global $gpAdmin;
		
		//reduce to length of 5;
		$count = count($gpAdmin['freq_scripts']);
		if( $count > 3 ){
			for($i=0;$i < ($count - 5);$i++){
				array_pop($gpAdmin['freq_scripts']);
			}
		}
		
		//reduce the hit count on each of the top five
		$min_value = end($gpAdmin['freq_scripts']);
		foreach($gpAdmin['freq_scripts'] as $page => $hits){
			$gpAdmin['freq_scripts'][$page] = $hits - $min_value;
		}
	}
		
}

class gpFiles{

	
	
	//$filetype		1=directories,'php'='.php' files
	function ReadDir($dir,$filetype='php'){
		$files = array();
		if( !file_exists($dir) ){
			return $files;
		}
		$dh = @opendir($dir);
		if( !$dh ){
			return $files;
		}
		
		while( ($file = readdir($dh)) !== false){
			if( strpos($file,'.') === 0){
				continue;
			}
			
			//get all
			if( $filetype=== false ){
				$files[$file] = $file;
				continue;
			}
			
			//get directories
			if( $filetype === 1 ){
				$fullpath = $dir.'/'.$file;
				if( is_dir($fullpath) ){
					$files[$file] = $file;
				}
				continue;
			}
			
			
			$dot = strrpos($file,'.');
			if( $dot === false ){
				continue;
			}
			
			$type = substr($file,$dot+1);
			if( $type == $filetype ){
				$file = substr($file,0,$dot);
			}else{
				continue;
			}
			
			$files[$file] = $file;
		}
		closedir($dh);
		
		return $files;
		
	}
	
	function ReadFolderAndFiles($dir){
		$dh = @opendir($dir);
		if( !$dh ){
			return $files;
		}		
		
		$folders = array();
		$files = array();
		while( ($file = readdir($dh)) !== false){
			if( strpos($file,'.') === 0){
				continue;
			}
			
			$fullPath = $this->currentDir.'/'.$file;
			if( is_dir($fullPath) ){
				$folders[] = $file;
			}else{
				$files[] = $file;
			}
		}
		asort($folders);
		asort($files);
		return array($folders,$files);
	}
	
	function CleanTitle($title){
		//$title = str_replace(array('"','\'','?','&','#'),array(''),$title); // something like "Mission & Principles" should be ok
		$title = str_replace(array('"','\'','?','#','*',':'),array(''),$title);
		$title = str_replace(array(' ','<','>','/','\\','|'),array('_'),$title);
		$title = trim($title);
		
		
		// Remove control characters
		if ( version_compare( '4.2.3', phpversion(), '>=' ) ) {
			return preg_replace( '#[[:cntrl:]]#u', '', $title ) ; // 	[\x00-\x1F\x7F]
		}else{
			return preg_replace( '#[[:cntrl:]]#', '', $title ) ; // 	[\x00-\x1F\x7F]
		}
	}
	
	function CleanArg($path){
		
		//all forward slashes
		$path = str_replace('\\','/',$path);
		
		//remove directory style changes
		$path = str_replace(array('../','./','..'),array('','',''),$path);
		
		//change other characters to underscore
		//$pattern = '#\\.|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]#';
		$pattern = '#\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]#';
		if ( version_compare( '4.2.3', phpversion(), '>=' ) ) {
			$pattern .= 'u';
		}
		$path = preg_replace( $pattern, '_', $path ) ;
		
		//reduce multiple slashes to single
		$pattern = '#\/+#';
		$path = preg_replace( $pattern, '/', $path ) ;
		
		return $path;
	}
	
	function rmPHP(&$text){
		$search = array('<?','<?php','?>');
		$replace = array('&lt;?','&lt;?php','?&gt;');
		$text = str_replace($search,$replace,$text);
	}

	function SaveTitle($title,$contents,$file_type='page'){
		global $dataDir;
		
		if( empty($title) ){
			return false;
		}
		
		$file = $dataDir.'/data/_pages/'.$title.'.php';
		$code = '$file_type = \''.$file_type.'\';';
		
		return gpFiles::SaveFile($file,$contents,$code);
	}
	
	function SaveFile($file,$contents,$code=false){
		global $gpversion;
		
		$codeA[] = '<'.'?'.'php';
		$codeA[] = 'defined(\'is_running\') or die(\'Not an entry point...\');';
		$codeA[] = '$fileVersion = \''.$gpversion.'\';';
		$codeA[] = '$fileModTime = \''.time().'\';';
		if( $code !== false ){
			$codeA[] = $code;
		}
		$codeA[] = '';
		$codeA[] = '?'.'>';
		
		
		$contents = implode("\n",$codeA).$contents;
		return gpFiles::Save($file,$contents);
	}
	
	function Save($file,$contents){
		$fp = gpFiles::fopen($file);
		if( !$fp ){
			return false;
		}
		if( !fwrite($fp,$contents) ){
			fclose($fp);
			return false;
		}
		
		fclose($fp);
		return true;
	}
	
	
	function SaveArray($file,$varname,&$array){
		
		$data = gpFiles::ArrayToPHP($varname,$array);
		
		$start = array();
		$start[] = '<'.'?'.'php';
		$start[] = 'defined(\'is_running\') or die(\'Not an entry point...\');';
		$start[] = '$fileModTime = \''.time().'\';';
		$start[] = '';
		$start[] = '';
		
		$start = implode("\n",$start);
		
		return gpFiles::Save($file,$start.$data);
	}
	
	//boolean, strings, and numbers
	function ArrayToPHP($varname,&$array){
		
		//this works too, but isn't as clean
		//return '$'.$varname.'=unserialize(\''.addcslashes(serialize($array),'\'').'\');';
		
		$data = array();
		
		if( count($array) == 0 ){
			$data[] = '$'.$varname.' = array();';
		}
		
		foreach($array as $name => $value){
			
			if( is_int($name) ){
				$name = $varname.'['.$name.']';
			}else{
				$name = $varname.'[\''.addcslashes($name,'\'').'\']';
			}
			if( is_array($value) ){
				$data[] = gpFiles::ArrayToPHP($name,$value);
				continue;
			}
			$data[] = gpFiles::PHPVariable('$'.$name,$value);
		}
		return implode("\n",$data);
	}
	function PHPVariable($name,$value){
		
		if( is_int($value) || is_float($value) ){
			return $name.' = '.$value.';';
		}elseif( is_bool($value) ){
			if( $value ){
				return $name.' = true;';
			}else{
				return $name.' = false;';
			}
		}
		return $name.' = \''.addcslashes($value,'\'').'\';';
	}
	
	
	function fopen($file){
		if( !file_exists($file) ){
			$dir = dirname($file);
			gpFiles::CheckDir($dir);
			$fp = fopen($file,'wb');
			//chmod($file,0644);
			chmod($file,0666);
		}
		return fopen($file,'wb');
	}
	
	function CheckDir($dir){
		global $config;
		
		if( !file_exists($dir) ){
			$parent = dirname($dir);
			gpFiles::CheckDir($parent);
			
			
			//ftp mkdir
			if( isset($config['useftp']) ){
				return gpFiles::FTP_CheckDir($dir);
			}
			
			return mkdir($dir,0755);
			
		}
		return true;
	}
	
	function RmDir($dir){
		global $config;
		
		//ftp
		if( isset($config['useftp']) ){
			return gpFiles::FTP_RmDir($dir);
		}
		return rmdir($dir);
	}
	
	
	
	/* FTP Function */
	
	function FTP_RmDir($dir){
		$conn_id = gpFiles::FTPConnect();
		$dir = gpFiles::ftpLocation($dir);
		
		return ftp_rmdir($conn_id,$dir);
	}
	
	function FTP_CheckDir($dir){
		$conn_id = gpFiles::FTPConnect();
		$dir = gpFiles::ftpLocation($dir);
		
		if( !ftp_mkdir($conn_id,$dir) ){
			return false;
		}
		return ftp_site($conn_id, 'CHMOD 0777 '. $dir );
	}
	
	function FTPConnect(){
		global $config;
		
		static $conn_id = false;
		
		if( $conn_id ){
			return $conn_id;
		}
		
		
		$conn_id = @ftp_connect($config['ftp_server'],21,6);
		if( !$conn_id ){
			trigger_error('ftp_connect() failed for server : '.$config['ftp_server']);
			return false;
		}
		
		$login_result = @ftp_login($conn_id,$config['ftp_user'],$config['ftp_pass'] );
		if( !$login_result ){
			trigger_error('ftp_login() failed for server : '.$config['ftp_server'].' and user: '.$config['ftp_user']);
			return false;
		}
		register_shutdown_function(array('gpFiles','ftpClose'),$conn_id);
		return $conn_id;
	}
	
	function ftpClose($connection=false){
		if( $connection !== false ){
			@ftp_quit($connection);
		}
	}
	
	function ftpLocation(&$location){
		global $config,$rootDir;
		
		$len = strlen($rootDir);
		$temp = substr($location,$len);
		return $config['ftp_root'].$temp;
	}	
}

class AddonTools{
	
	function SetDataFolder($name){
		global $dataDir;
		global $addonDataFolder,$addonCodeFolder; //deprecated
		global $addonRelativeCode,$addonRelativeData,$addonPathData,$addonPathCode,$addonFolderName;
		
		
		$addonFolderName = $name;
		$addonPathCode = $addonCodeFolder = $dataDir.'/data/_addoncode/'.$name;
		$addonPathData = $addonDataFolder = $dataDir.'/data/_addondata/'.$name;
		$addonRelativeCode = common::GetDir('/data/_addoncode/'.$name);
		$addonRelativeData = common::GetDir('/data/_addondata/'.$name);
	}
	
	function ClearDataFolder(){
		global $addonDataFolder,$addonCodeFolder; //deprecated
		global $addonRelativeCode,$addonRelativeData,$addonPathData,$addonPathCode;
		
		
		$addonDataFolder = $addonCodeFolder = false;
		$addonRelativeCode = $addonRelativeData = $addonPathData = $addonPathCode = false;
		
		
	}
	
}
	
	

