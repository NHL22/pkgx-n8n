$('#menu_f').click(function(){
    $('#sub_menu_f').toggleClass('show');
		$('#_subnav2').toggleClass('show');
		$('#sub_menu_f2').removeClass('show');
		$('#_subnav3').removeClass('show');
		if(document.getElementById("img_f").src === "https://phukiengiaxuong.com.vn/cdn/upload/files/social/bullet.svg"){
			document.getElementById("img_f").src = "https://phukiengiaxuong.com.vn/cdn/upload/files/social/cancel.svg";
		}else{
			document.getElementById("img_f").src = "https://phukiengiaxuong.com.vn/cdn/upload/files/social/bullet.svg";
		}
});
$('#menu_f2').click(function(){
    $('#sub_menu_f2').toggleClass('show');
		$('#_subnav3').toggleClass('show');
		$('#sub_menu_f').removeClass('show');
		$('#_subnav2').removeClass('show');
		if(document.getElementById("img_f2").src === "https://phukiengiaxuong.com.vn/cdn/upload/files/social/messenger.svg"){
			document.getElementById("img_f2").src = "https://phukiengiaxuong.com.vn/cdn/upload/files/social/cancel.svg";
		}else{
			document.getElementById("img_f2").src = "https://phukiengiaxuong.com.vn/cdn/upload/files/social/messenger.svg";
		}
});

$('a.share').click(function(e){
e.preventDefault();
var $link   = $(this);
var href    = $link.attr('href');
var network = $link.attr('data-network');

var networks = {
    facebook : { width : 600, height : 300 },
    twitter  : { width : 600, height : 254 },
    linkedin : { width : 600, height : 473 },
    		pinterest : { width : 600, height : 473 }
};

var popup = function(network){
    var options = 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,';
    window.open(href, '', options+'height='+networks[network].height+',width='+networks[network].width);
}

popup(network);
});

$(window).scroll(function() {
    if ($(this).scrollTop()>300)
     {
        $('.menu_footer-1').show(10);
     }
    else
     {
      $('.menu_footer-1').hide(10);
     }
 });
