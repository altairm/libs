function gp_drop(a,b){alert(a);alert(b);a.before(b)}$(function(){window.setTimeout(function(){setup()},500);function setup(){var a=$('<div class="draggable_droparea" id="theme_content_drop"></div>').appendTo('body');$('.output_area').each(function(i,b){var c=$(b);var topleft=c.offset();var w=c.width();var h=c.height();if(h==0){var origin=c.offset();var maxy=0;var y=0;c.children().each(function(d,e){e=$(e);y=e.offset().top+e.height();if(y>maxy){maxy=y}});h=y-origin.top}$('<div style="border:2px dashed red;background:#ccc" class="draggable_element"></div>').css({'position':'absolute','z-index':'8000','top':topleft.top,'left':topleft.left}).height(h-3).width(w-3).append(b.firstChild).fadeTo('slow',.5).appendTo(a)})}});