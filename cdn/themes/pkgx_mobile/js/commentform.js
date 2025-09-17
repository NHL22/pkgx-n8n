function cmtaddreplyclick(id, type = 0, id_value, parent_id){
        var added = $("#comment_wrapper .cmextra form");
         /* Xóa form nếu nó tồn tại */
        added.remove();
        /* Them moi */
        var comment = {type: type, parent_id: parent_id, id_value: id_value };
        $.post(
            'ajax?act=comment_form',
            {data: $.toJSON(comment)},
            function(response){
                var res = $.evalJSON(response);
                $("#comment"+id+" .cmextra").append(res.content);
            },
            'text'
        );
    return false;
}

function submitReply(frm){
        var cmt = new Object();
        cmt.user_name       = frm.elements['reply_user_name'].value;
        cmt.email           = frm.elements['reply_user_email'].value;
        cmt.user_tel        = frm.elements['reply_user_tel'].value;
        cmt.content         = frm.elements['reply_content'].value;
        cmt.type            = frm.elements['reply_cmt_type'].value;
        cmt.id              = frm.elements['reply_id_value'].value;
        cmt.captcha         = frm.elements['reply_captcha'] ? frm.elements['reply_captcha'].value : '';
        cmt.rank            = 0;
        cmt.parent_id       =  frm.elements['reply_parent'].value;

        var validItem = $('#cr_email, #cr_content, #cr_captcha');
        validItem.tipsy({gravity: 's', fade: true, trigger: 'manual'}).valid8('').focusout(function(){validAndTip($(this));}).keyup(function(){validAndTip($(this));});
        if (cmt.email.length > 0) {
            if (!isValidEmail(cmt.email)) {
                $('#cr_email').attr('original-title', lang.error_email_format).tipsy('show');
                return false;
            }
        } else {
            $('#cr_email').attr('original-title', lang.error_email_required).tipsy('show');
            return false;
        }
        if (cmt.content.length == 0) {
            $('#cr_content').attr('original-title', lang.error_comment_content_required).tipsy('show');
            return false;
        }
        if ($('#cr_captcha').length > 0 && cmt.captcha.length == 0) {
            $('#cr_captcha').attr('original-title', lang.error_captcha_required).tipsy('show');
            return false;
        }

        $.post(
        'ajax?act=comment&method=post',
        {cmt: $.toJSON(cmt)},
        function(response){
            var res = $.evalJSON(response);
            if (res.message) {
                $.fn.colorbox({html:'<div class="message_box mb_info">' + res.message + '<p class="action"><a href="javascript:void(0);" class="button brighter_button" onclick="$.fn.colorbox.close(); return false;"><span>' + lang.confirm + '</span></a></p></div>'});
            }
            if (res.error == 0) {
                $('#comment_wrapper').html(res.content);
                $('.rank_star').rating({
                    focus: function(value, link){
                        var tip = $('#star_tip');
                        tip[0].data = tip[0].data || tip.html();
                        tip.html(link.title || 'value: '+value);
                    },
                    blur: function(value, link){
                        var tip = $('#star_tip');
                        $('#star_tip').html(tip[0].data || '');
                    }
                });
                $('.tip').tipsy({gravity: 's',fade: true,html: true});
            }
        },
        'text'
    );
    return false;
}