<?php
define('COOKIE_EXP_3DAYS', time() + 259200);
define('COOKIE_EXP_10YEARS', time() + 315360000);
define('COOKIE_EXPIRED', time() -1);

class Cookie {
    public static function setCookie($name, $value, $expire = '', $path = '', $domain = '') {
        $cookie = "Set-Cookie: $name=" . trim($value) . ';';
        $cookie .= (empty($expire)) ? "" : " expires=" . gmdate('D, d-M-Y H:i:s ', $expire) . ' GMT;';
        $cookie .= (empty($path)) ? "" : " path={$path};";
        $cookie .= (empty($domain)) ? "" : " domain={$domain}";

        header($cookie,false);
    }
    public static function setDomainCookie($name, $value, $expire = '') {
        Cookie::setCookie($name, $value, $expire, '/', $GLOBALS['cfg_cookie_domain']);
    }
}
