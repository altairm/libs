<?php
/**
 * get page by cUrl request
 * @param string $url
 * @param string $referer
 * @param string $agent
 * @param int $timeout
 * @param string $proxy [proxy IP]:[port]
 * @return array('EXE'=>, 'INF'=>, 'ERR'=>)  
 */
function getPage($url, $referer, $agent, $timeout = 5, $proxy = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    isset($proxy) ? curl_setopt($ch, CURLOPT_PROXY, $proxy) : '';
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
 
    $result['EXE'] = curl_exec($ch);
    $result['INF'] = curl_getinfo($ch);
    $result['ERR'] = curl_error($ch);
 
    curl_close($ch);
 
    return $result;
}
$keyword = 'buy+viagra';
$url = 'http://www.google.com/search?hl=en&as_q='.$keyword.'&as_epq=&as_oq=&as_eq=&lr=&as_filetype=&ft=i&as_sitesearch=&as_qdr=all&as_rights=&as_occt=any&cr=&as_nlo=&as_nhi=&safe=images&num=100';
$agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8';
$referer = 'http://www.google.com/';

$result = getPage($url, $referer, $agent);

if (empty($result['ERR'])) {
    // TODO: check there is no captcha
    // preg_match("/sorry.google.com/", $result['EXE']);
    preg_match_all('/<h3\s*class="r">\s*<a[^<>]*href="([^<>]*)"[^<>]*>(.*)<\/a>\s*<\/h3>/siU', $result['EXE'], $matches);
 
    for ($i = 0; $i < count($matches[2]); $i++) {
        $matches[2][$i] = strip_tags($matches[2][$i]);
    }
    // Job’s done!
    // $matches[1] array contains all URLs, and 
    // $matches[2] array contains all anchors
    var_dump($matches);
} else {
    echo 'WTF? Problems?';
}
?>
