$(function(){function getAddonData(){$('#gpeasy_addons').show().html('<p>gpEasy.com Addons</p>');a=jPrep(server);$.getJSON(a,ajaxResponse)}function addonResponse(data){alert(data)}function addons(){var tst,a,b,server,rtn;tst=window.frames['addon_iframe'].location.href;if(tst.indexOf('?')>0){return}a=$('#addon_iframe').attr('rel');b='Special_Addon_Plugins';if(a=='themes'){b='Special_Addon_Themes'}server='http://gpeasy.com/index.php/';server='http://gpeasy.loc/glacier/index.php/';server+=b+'?gpreq=body';server+='&in='+encodeURIComponent(window.location.href);window.frames['addon_iframe'].location=server;try{}catch(e){}}addons()});