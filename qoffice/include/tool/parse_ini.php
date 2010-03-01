<?php
defined("is_running") or die("Not an entry point...");


class gp_ini{

	function ParseFile($file){
		
		$fp = @fopen($file,'rb');
		if( !$fp ){
			return array();
		}
		
		$contents = '';
		while(!feof($fp)) {
			$contents .= fread($fp, 8192);
		}
		fclose($fp);
		
		return gp_ini::ParseString($contents);
	}
	
	function ParseString($string){
		
		$aResult  =
		$aMatches = array();

		$a = &$aResult;
		$s = '\s*([[:alnum:]_\- \*:]+?)\s*';

		preg_match_all('#^\s*((\['.$s.'\])|(("?)'.$s.'\\5\s*=\s*("?)(.*?)\\7))\s*(;[^\n]*?)?$#ms', $string, $aMatches, PREG_SET_ORDER);

		foreach($aMatches as $aMatch){
			if (empty($aMatch[2])){
				$a [$aMatch[6]] = gp_ini::Value($aMatch[8]);
			}else{
				$a = &$aResult [$aMatch[3]];
			}
		}

		return $aResult;
	}
	function Value($val){
		if (preg_match('/^-?[0-9]$/i', $val)) { return intval($val); }
		else if (strtolower($val) === 'true') { return true; }
		else if (strtolower($val) === 'false') { return false; }
		else if (preg_match('/^"(.*)"$/i', $val, $m)) { return $m[1]; }
		else if (preg_match('/^\'(.*)\'$/i', $val, $m)) { return $m[1]; }
		return $val;
	}
}
