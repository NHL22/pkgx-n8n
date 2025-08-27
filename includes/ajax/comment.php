<?php

/*------------------------------------------------------ */
//-- Request Ajax for Comments - act=comment
/*------------------------------------------------------ */

    require(ROOT_PATH . 'includes/cls_json.php');
    $json   = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');

    $_REQUEST['cmt'] = isset($_REQUEST['cmt']) ? json_str_iconv($_REQUEST['cmt']) : '';
    $_REQUEST['method'] = isset($_REQUEST['method']) ? json_str_iconv($_REQUEST['method']) : 'get';
    /* Comment Post */
    if ($_REQUEST['method'] == 'post')
    {
        /*
         * act 参数为空
         */
        $cmt  = $json->decode($_REQUEST['cmt']);
        $cmt->page = 1;
        $cmt->id   = !empty($cmt->id)   ? intval($cmt->id) : 0;
        $cmt->type = !empty($cmt->type) ? intval($cmt->type) : 0;
        $cmt->parent_id = !empty($cmt->parent_id) ? intval($cmt->parent_id) : 0;

        if (empty($cmt) || !isset($cmt->type) || !isset($cmt->id))
        {
            $result['error']   = 1;
            $result['message'] = $_LANG['invalid_comments'];
        }
        elseif (!is_email($cmt->email))
        {
            $result['error']   = 1;
            $result['message'] = $_LANG['error_email'];
        }
        else
        {
            if ((intval($_CFG['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0)
            {
                /* 检查验证码 */
                include_once('includes/cls_captcha.php');

                $validator = new captcha();
                if (!$validator->check_word($cmt->captcha))
                {
                    $result['error']   = 1;
                    $result['message'] = $_LANG['invalid_captcha'];
                }
                else
                {
                    $factor = intval($_CFG['comment_factor']);
                    if ($cmt->type == 0 && $factor > 0)
                    {
                        /* 只有商品才检查评论条件 */
                        switch ($factor)
                        {
                            case COMMENT_LOGIN :
                                if ($_SESSION['user_id'] == 0)
                                {
                                    $result['error']   = 1;
                                    $result['message'] = $_LANG['comment_login'];
                                }
                                break;

                            case COMMENT_CUSTOM :
                                if ($_SESSION['user_id'] > 0)
                                {
                                    $sql = "SELECT o.order_id FROM " . $ecs->table('order_info') . " AS o ".
                                           " WHERE user_id = '" . $_SESSION['user_id'] . "'".
                                           " AND (o.order_status = '" . OS_CONFIRMED . "' or o.order_status = '" . OS_SPLITED . "') ".
                                           " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') ".
                                           " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') ".
                                           " LIMIT 1";


                                     $tmp = $db->getOne($sql);
                                     if (empty($tmp))
                                     {
                                        $result['error']   = 1;
                                        $result['message'] = $_LANG['comment_custom'];
                                     }
                                }
                                else
                                {
                                    $result['error'] = 1;
                                    $result['message'] = $_LANG['comment_custom'];
                                }
                                break;
                            case COMMENT_BOUGHT :
                                if ($_SESSION['user_id'] > 0)
                                {
                                    $sql = "SELECT o.order_id".
                                           " FROM " . $ecs->table('order_info'). " AS o, ".
                                           $ecs->table('order_goods') . " AS og ".
                                           " WHERE o.order_id = og.order_id".
                                           " AND o.user_id = '" . $_SESSION['user_id'] . "'".
                                           " AND og.goods_id = '" . $cmt->id . "'".
                                           " AND (o.order_status = '" . OS_CONFIRMED . "' or o.order_status = '" . OS_SPLITED . "') ".
                                           " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') ".
                                           " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') ".
                                           " LIMIT 1";
                                     $tmp = $db->getOne($sql);
                                     if (empty($tmp))
                                     {
                                        $result['error']   = 1;
                                        $result['message'] = $_LANG['comment_brought'];
                                     }
                                }
                                else
                                {
                                    $result['error']   = 1;
                                    $result['message'] = $_LANG['comment_brought'];
                                }
                        }
                    }

                    /* 无错误就保存留言 */
                    if (empty($result['error']))
                    {
                        add_comment($cmt);
                    }
                }
            }
            else
            {
                /* 没有验证码时，用时间来限制机器人发帖或恶意发评论 */
                if (!isset($_SESSION['send_time']))
                {
                    $_SESSION['send_time'] = 0;
                }

                $cur_time = gmtime();
                if (($cur_time - $_SESSION['send_time']) < 30) // 小于30秒禁止发评论
                {
                    $result['error']   = 1;
                    $result['message'] = $_LANG['cmt_spam_warning'];
                }
                else
                {
                    $factor = intval($_CFG['comment_factor']);
                    if ($cmt->type == 0 && $factor > 0)
                    {
                        /* 只有商品才检查评论条件 */
                        switch ($factor)
                        {
                            case COMMENT_LOGIN :
                                if ($_SESSION['user_id'] == 0)
                                {
                                    $result['error']   = 1;
                                    $result['message'] = $_LANG['comment_login'];
                                }
                                break;

                            case COMMENT_CUSTOM :
                                if ($_SESSION['user_id'] > 0)
                                {
                                    $sql = "SELECT o.order_id FROM " . $ecs->table('order_info') . " AS o ".
                                           " WHERE user_id = '" . $_SESSION['user_id'] . "'".
                                           " AND (o.order_status = '" . OS_CONFIRMED . "' or o.order_status = '" . OS_SPLITED . "') ".
                                           " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') ".
                                           " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') ".
                                           " LIMIT 1";


                                     $tmp = $db->getOne($sql);
                                     if (empty($tmp))
                                     {
                                        $result['error']   = 1;
                                        $result['message'] = $_LANG['comment_custom'];
                                     }
                                }
                                else
                                {
                                    $result['error'] = 1;
                                    $result['message'] = $_LANG['comment_custom'];
                                }
                                break;

                            case COMMENT_BOUGHT :
                                if ($_SESSION['user_id'] > 0)
                                {
                                    $sql = "SELECT o.order_id".
                                           " FROM " . $ecs->table('order_info'). " AS o, ".
                                           $ecs->table('order_goods') . " AS og ".
                                           " WHERE o.order_id = og.order_id".
                                           " AND o.user_id = '" . $_SESSION['user_id'] . "'".
                                           " AND og.goods_id = '" . $cmt->id . "'".
                                           " AND (o.order_status = '" . OS_CONFIRMED . "' or o.order_status = '" . OS_SPLITED . "') ".
                                           " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') ".
                                           " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') ".
                                           " LIMIT 1";
                                     $tmp = $db->getOne($sql);
                                     if (empty($tmp))
                                     {
                                        $result['error']   = 1;
                                        $result['message'] = $_LANG['comment_brought'];
                                     }
                                }
                                else
                                {
                                    $result['error']   = 1;
                                    $result['message'] = $_LANG['comment_brought'];
                                }
                        }
                    }
                    /* 无错误就保存留言 */
                    if (empty($result['error']))
                    {
                        add_comment($cmt);
                        $_SESSION['send_time'] = $cur_time;
                    }
                }
            }
        }
    }
    /* Comment Get */
    else
    {
        /*
         * act 参数不为空
         * 默认为评论内容列表
         * 根据 _GET 创建一个静态对象
         */
        $cmt = new stdClass();
        $cmt->id   = !empty($_GET['id'])   ? intval($_GET['id'])   : 0;
        $cmt->type = !empty($_GET['type']) ? intval($_GET['type']) : 0;
        $cmt->page = isset($_GET['page'])   && intval($_GET['page'])  > 0 ? intval($_GET['page'])  : 1;
    }

    if ($result['error'] == 0)
    {
        $comments = assign_comment($cmt->id, $cmt->type, $cmt->page);

        $smarty->assign('comment_type', $cmt->type);
        $smarty->assign('id',           $cmt->id);
        $smarty->assign('username',     $_SESSION['user_name']);
        $smarty->assign('email',        $_SESSION['email']);
        $smarty->assign('comments',     $comments['comments']);
        $smarty->assign('pager',        $comments['pager']);

        /* 验证码相关设置 */
        if ((intval($_CFG['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0)
        {
            $smarty->assign('enabled_captcha', 1);
            $smarty->assign('rand', mt_rand());
        }

        $result['message'] = $_CFG['comment_check'] ? $_LANG['cmt_submit_wait'] : '';
        $result['content'] = $smarty->fetch("library/comments_list.lbi");
    }

    die($json->encode($result));



/*------------------------------------------------------ */
//-- Private Function for request ajax
/*------------------------------------------------------ */

/**
 * add_comment
 *
 * @access  public
 * @param   object  $cmt
 * @return  void
 */
function add_comment($cmt)
{
    /* 评论是否需要审核 */
    $status = 1 - $GLOBALS['_CFG']['comment_check'];

    $user_id = empty($_SESSION['user_id']) ? 0 : $_SESSION['user_id'];
    $email = empty($cmt->email) ? $_SESSION['email'] : trim($cmt->email);
    $user_name = empty($cmt->username) ? $_SESSION['user_name'] : '';
    $user_tel = empty($cmt->user_tel) ? htmlspecialchars($cmt->user_tel) : '';
    $user_name = empty($cmt->user_name) ? $_SESSION['user_name'] : trim($cmt->user_name);

    $email = htmlspecialchars($email);
    $user_name = htmlspecialchars($user_name);
    $content = htmlspecialchars($cmt->content);

    $user_tel = strip_tags($user_tel);
    $user_name = strip_tags($user_name);
    $content = strip_tags($content);

    /* Edit Nobj thêm parent_id, user_tel */
    $sql = "INSERT INTO " .$GLOBALS['ecs']->table('comment') .
           "(comment_type, id_value, email, user_tel, user_name, content, comment_rank, add_time, ip_address, status, parent_id, user_id) VALUES " .
           "('" .$cmt->type. "', '" .$cmt->id. "', '$email', '$user_tel', '$user_name', '" .$cmt->content."', '".$cmt->rank."', ".time().", '".real_ip()."', '$status', '".$cmt->parent_id."', '$user_id')";

    $result = $GLOBALS['db']->query($sql);
    clear_cache_files('comments_list.lbi');

    return $result;
}
 ?>