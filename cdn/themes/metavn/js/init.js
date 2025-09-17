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

$('#search-keyword').delayKeyup(function(el){
    var keywords = el.val(), show = $('#search-site .search-suggest');
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