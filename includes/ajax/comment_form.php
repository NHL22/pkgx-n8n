<?php
    /*------------------------------------------------------ */
    //-- Request Ajax for Comments - act=comment_form
    /*------------------------------------------------------ */

    require(ROOT_PATH . 'includes/cls_json.php');
    $json   = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');
    $cmt  = $json->decode($_REQUEST['data']);

    $cmt_id_value  = !empty($cmt->id_value)   ? intval($cmt->id_value)   : 0;
    $cmt_type      = !empty($cmt->type) ? intval($cmt->type) : 0;
    $cmt_parent_id = !empty($cmt->parent_id) ? intval($cmt->parent_id) : 0;

    /* Kiem tra du lieu dau vao */
    if($cmt_parent_id == 0 || $cmt_id_value == 0){
        $result['error'] = 1;
        $result['message'] = 'Thông tin không hợp lệ';
        die($json->encode($result));
    }

    $smarty->assign('comment_type', $cmt_type);
    $smarty->assign('id_value',     $cmt_id_value);
    $smarty->assign('parent_id',    $cmt_parent_id);

    if ((intval($_CFG['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0)
    {
        $smarty->assign('enabled_captcha', 1);
        $smarty->assign('rand', mt_rand());
    }
    $result['content'] = $smarty->fetch("library/comment_form.lbi");
    die($json->encode($result));

 ?>