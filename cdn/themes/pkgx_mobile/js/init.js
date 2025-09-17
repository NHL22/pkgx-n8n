$(document).ready(function() {
    $('body').append('<div id="loading_box">' + lang.process_request + '</div>');
    $('#loading_box').ajaxStart(function(){
        var loadingbox = $(this);
        var left = -(loadingbox.outerWidth() / 2);
        loadingbox.css({'marginRight': left + 'px'});
        loadingbox.delay(3000).fadeIn(400);
    });
    $('#loading_box').ajaxSuccess(function(){
        $(this).stop().stop().fadeOut(400);
    });
    if (typeof(ajax_cart_disabled) == 'undefined') loadCart();
    $('.tip').tipsy({gravity:'s',fade:true,html:true});
});

$(window).scroll(function(){
        if( $(window).scrollTop() == 0 ) {
            $('#back-top').stop(false,true).fadeOut(600);
        }else{
            $('#back-top').stop(false,true).fadeIn(600);
        }
    });
$('#back-top').click(function(){
    $('body,html').animate({scrollTop:0},300);
    return false;
});


$.fn.delayKeyup = function(callback, ms){
    var timer = 0;
    var el = $(this);
    $(this).keyup(function(){
    clearTimeout (timer);
    timer = setTimeout(function(){
        callback(el)
        }, ms);
    });
    return $(this);
};
$('#search-keyword').click(function() {
    var search = $('#search-site');
    search.find('.btn-reset').show();
    search.find('.btn-submit').show();
    search.find('.iconsearch').hide();
});
$('#search-site .btn-reset').click(function() {
    $('#suggestion').hide();
});
$('#search-keyword').delayKeyup(function(el){
    var keywords = el.val(), show = $('#suggestion');
    if(keywords.length >2){
        $.post(
            'tim-kiem?act=search_suggest',
            {keywords: keywords},
            function(response){
                var res = $.evalJSON(response);
                show.show();
                show.html(res.content);
            },
            'text'
        );
    }else{
        show.hide();
    }
},1000);

 $('.gototop').click(function(){
    $('body,html').animate({scrollTop:0},300);
});
$('#_menu').click(function(){
    $('#_subnav, div.over').toggleClass('show');
    $('#_menu').toggleClass('actmenu');
    $('body').toggleClass('fixbody');
});
$('#hide_nav').click(function(){
    $('#_subnav, div.over').removeClass('show');
    $('#_menu').removeClass('actmenu');
    $('body').removeClass('fixbody');
});
$('#_infoother').click(function(){
    $('#_subother').toggleClass('show');
});
$('#menu_f').click(function(){
    $('#sub_menu_f').toggleClass('show');
});

$('#navigation li.hassub>span').click(function(){
    $(this).parent('li').find('ul.sub_cat').toggleClass('hide');
});

function fixNav() {var $cache = $('header .header_bar'); if ($(window).scrollTop() > 100) $cache.css({'position': 'fixed','z-index': '12','top': '0','box-shadow': '0 5px 5px -5px #ddd'}); else $cache.css({'position': 'relative','z-index': 'auto','top': 'auto','box-shadow': 'none'}); }
$(window).scroll(fixNav);
fixNav();
