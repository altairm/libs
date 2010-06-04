/**
 * set cookie
 * @example setCookie('test', 1, '/', '.' + location.host, 3, false) - set cookie "test" = 1 for 3 days
 * @param name string
 * @param value string
 * @param path string
 * @param domain string
 * @param expires int days
 * @param esc boolean set to "true" for escaping value
 */
function setCookie (name, value, path, domain, expires, esc) {
    var expire = new Date();
    then = expire.getTime() + expires*1000*60*60*24;
    expire.setTime(then);
    exp = '; expires=' + expire.toGMTString();
    if(esc) {
        value = escape(value);
    }

    document.cookie = name + "=" + String(value) + '; path=' + path + exp + '; domain=' + domain;
}