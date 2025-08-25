<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/*------------------------------------------------------ */
//-- 获取没有回复的评论列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    admin_priv('comment_priv');

    $smarty->assign('ur_here',      $_LANG['05_comment_manage']);
    $smarty->assign('full_page',    1);

    $list = get_comment_list();

    $smarty->assign('comment_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('comment_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页、搜索、排序
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'query')
{
    $list = get_comment_list();

    $smarty->assign('comment_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('comment_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}
/* chinh sua noi dung comment da tra loi */
if ($_REQUEST['act']=='edit')
{
    admin_priv('comment_priv');

    $id = intval($_GET['id']);
    $pid = intval($_GET['pid']);

    $sql = "SELECT content FROM " .$ecs->table('comment'). " WHERE comment_id = '$id'";
    $comment_info = $db->getOne($sql);

    $smarty->assign('content', $comment_info);
    $smarty->assign('comment_id',  $id);
    $smarty->assign('pid', $pid);

    $smarty->assign('ur_here',   'Sửa Nội dung');
    $smarty->assign('action_link',  array('text' => 'Trở lại',
    'href' => 'comment_manage.php?act=reply&id'.$pid));

    $smarty->display('comment_edit.htm');
}

if ($_REQUEST['act']=='update')
{
    admin_priv('comment_priv');

    $id = intval($_POST['comment_id']);
    $pid = intval($_POST['pid']);

    $content = strip_tags($_POST['content']);

    $sql = "UPDATE " .$ecs->table('comment'). " SET content = '$content' WHERE comment_id = '$id'";
    $db->query($sql);

    $link[] = array('href' => 'comment_manage.php?act=reply&id='.$pid, 'text' => 'Danh sách nội dung trả lời');
    sys_msg('Cập nhật thành công !', 0, $link);

}


/*------------------------------------------------------ */
//-- 回复用户评论(同时查看评论详情)
/*------------------------------------------------------ */
if ($_REQUEST['act']=='reply')
{
    /* 检查权限 */
    admin_priv('comment_priv');

    $comment_info = array();
    $reply_info   = array();
    $id_value     = array();

    /* 获取评论详细信息并进行字符处理 */
    $sql = "SELECT * FROM " .$ecs->table('comment'). " WHERE comment_id = '$_REQUEST[id]'";
    $comment_info = $db->getRow($sql);
    $comment_info['content']  = str_replace('\r\n', '<br />', htmlspecialchars($comment_info['content']));
    $comment_info['content']  = nl2br(str_replace('\n', '<br />', $comment_info['content']));
    $comment_info['add_time'] = local_date($_CFG['time_format'], $comment_info['add_time']);

    /* 获得评论回复内容 */
    $sql = "SELECT comment_id, user_name, ip_address, content, add_time FROM ".$ecs->table('comment'). " WHERE parent_id = $_REQUEST[id]";
    $rres = $db->getAll($sql);

    foreach ($rres as $key => $rows) {
        $reply_info[$key]['comment_id'] = $rows['comment_id'];
        $reply_info[$key]['user_name'] = htmlspecialchars($rows['user_name']);
        $reply_info[$key]['ip_address'] = $rows['ip_address'];
        $reply_info[$key]['content']  = nl2br(htmlspecialchars($rows['content']));
        $reply_info[$key]['add_time'] = local_date($_CFG['time_format'], $rows['add_time']);
    }

    /* 获取管理员的用户名和Email地址 */
    $sql = "SELECT user_name, email FROM ". $ecs->table('admin_user').
           " WHERE user_id = '$_SESSION[admin_id]'";
    $admin_info = $db->getRow($sql);

    /* 取得评论的对象(文章或者商品) */
    if ($comment_info['comment_type'] == 0)
    {
        $sql = "SELECT goods_name, goods_id FROM ".$ecs->table('goods').
               " WHERE goods_id = '$comment_info[id_value]'";
        $gres = $db->getRow($sql);
        $id_value = $gres['goods_name'];
        $id_url = build_uri('goods', array('gid' => $gres['goods_id']));
    }
    else
    {
        $sql = "SELECT title, article_id FROM ".$ecs->table('article').
               " WHERE article_id='$comment_info[id_value]'";
        $gres = $db->getRow($sql);
        $id_value = $gres['title'];
        $id_url = build_uri('article', array('aid' => $gres['goods_id']));
    }

    /* 模板赋值 */
    $smarty->assign('msg',          $comment_info); //评论信息
    $smarty->assign('admin_info',   $admin_info);   //管理员信息
    $smarty->assign('reply_info',   $reply_info);   //回复的内容
    $smarty->assign('id_value',     $id_value);  //评论的对象
    $smarty->assign('id_url',     $id_url);
    $smarty->assign('send_fail',   !empty($_REQUEST['send_ok']));

    $smarty->assign('ur_here',      $_LANG['comment_info']);
    $smarty->assign('action_link',  array('text' => $_LANG['05_comment_manage'],
    'href' => 'comment_manage.php?act=list'));

    /* 页面显示 */
    assign_query_info();
    $smarty->display('comment_info.htm');
}
/*------------------------------------------------------ */
//-- 处理 回复用户评论
/*------------------------------------------------------ */
if ($_REQUEST['act']=='action')
{
    admin_priv('comment_priv');

    $ip     = real_ip();

    /* chỉ cho chèn reply mới, chứ ko ghi đè */
    $sql = "INSERT INTO ".$ecs->table('comment')." (comment_type, id_value, email, user_name , ".
                    "content, add_time, ip_address, status, parent_id, is_admin) ".
               "VALUES('$_POST[comment_type]', '$_POST[id_value]','$_POST[email]', " .
                    "'$_SESSION[admin_name]','$_POST[content]','" . gmtime() . "', '$ip', '0', '$_POST[comment_id]', 1)";

    $db->query($sql);

    /* cap nhat trang thai */
    $sql = "UPDATE " .$ecs->table('comment'). " SET status = 1 WHERE comment_id = '$_POST[comment_id]'";
    $db->query($sql);

    /* gui mail */
    if (!empty($_POST['send_email_notice']))
    {
        //获取邮件中的必要内容
        $sql = 'SELECT user_name, email, content ' .
               'FROM ' .$ecs->table('comment') .
               " WHERE comment_id ='$_REQUEST[comment_id]'";
        $comment_info = $db->getRow($sql);

        /* 设置留言回复模板所需要的内容信息 */
        $template    = get_mail_template('recomment');

        $smarty->assign('user_name',   $comment_info['user_name']);
        $smarty->assign('recomment', $_POST['content']);
        $smarty->assign('comment', $comment_info['content']);
        $smarty->assign('shop_name',   "<a href='".$ecs->url()."'>" . $_CFG['shop_name'] . '</a>');
        $smarty->assign('send_date',   date('Y-m-d'));

        $content = $smarty->fetch('str:' . $template['template_content']);

        /* 发送邮件 */
        if (send_mail($comment_info['user_name'], $comment_info['email'], $template['template_subject'], $content, $template['is_html']))
        {
            $send_ok = 0;
        }
        else
        {
            $send_ok = 1;
        }
    }

    /* 清除缓存 */
    clear_cache_files();

    /* 记录管理员操作 */
    admin_log(addslashes($_LANG['reply']), 'edit', 'users_comment');

    ecs_header("Location: comment_manage.php?act=reply&id=$_REQUEST[comment_id]&send_ok=$send_ok\n");
    exit;
}
/*------------------------------------------------------ */
//-- display or no
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'check')
{
    if ($_REQUEST['check'] == 'allow')
    {
        /* 允许评论显示 */
        $sql = "UPDATE " .$ecs->table('comment'). " SET status = 1 WHERE comment_id = '$_REQUEST[id]'";
        $db->query($sql);

        //add_feed($_REQUEST['id'], COMMENT_GOODS);

        /* 清除缓存 */
        clear_cache_files();

        ecs_header("Location: comment_manage.php?act=reply&id=$_REQUEST[id]\n");
        exit;
    }
    else
    {
        /* 禁止评论显示 */
        $sql = "UPDATE " .$ecs->table('comment'). " SET status = 0 WHERE comment_id = '$_REQUEST[id]'";
        $db->query($sql);

        /* 清除缓存 */
        clear_cache_files();

        ecs_header("Location: comment_manage.php?act=reply&id=$_REQUEST[id]\n");
        exit;
    }
}

elseif ($_REQUEST['act'] == 'del')
{
    admin_priv('comment_priv');

    $id = intval($_GET['id']);
    $pid = intval($_GET['pid']);

    $sql = "DELETE FROM " .$ecs->table('comment'). " WHERE comment_id = '$id'";
    $res = $db->query($sql);

    clear_cache_files();

    $link[] = array('href' => 'comment_manage.php?act=reply&id='.$pid, 'text' => 'Trở lại');
    sys_msg('Xóa thành công !', 0, $link);
}
/*------------------------------------------------------ */
//-- 删除某一条评论
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('comment_priv');

    $id = intval($_GET['id']);

    $sql = "DELETE FROM " .$ecs->table('comment'). " WHERE comment_id = '$id'";
    $res = $db->query($sql);
    if ($res)
    {
        $db->query("DELETE FROM " .$ecs->table('comment'). " WHERE parent_id = '$id'");
    }

    admin_log('', 'remove', 'ads');

    $url = 'comment_manage.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 批量删除用户评论
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'batch')
{
    admin_priv('comment_priv');
    $action = isset($_POST['sel_action']) ? trim($_POST['sel_action']) : 'deny';

    if (isset($_POST['checkboxes']))
    {
        switch ($action)
        {
            case 'remove':
                $db->query("DELETE FROM " . $ecs->table('comment') . " WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
                $db->query("DELETE FROM " . $ecs->table('comment') . " WHERE " . db_create_in($_POST['checkboxes'], 'parent_id'));
                break;

           case 'allow' :
               $db->query("UPDATE " . $ecs->table('comment') . " SET status = 1  WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
               break;

           case 'deny' :
               $db->query("UPDATE " . $ecs->table('comment') . " SET status = 0  WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
               break;

           default :
               break;
        }

        clear_cache_files();
        $action = ($action == 'remove') ? 'remove' : 'edit';
        admin_log('', $action, 'adminlog');

        $link[] = array('text' => $_LANG['back_list'], 'href' => 'comment_manage.php?act=list');
        sys_msg(sprintf($_LANG['batch_drop_success'], count($_POST['checkboxes'])), 0, $link);
    }
    else
    {
        /* 提示信息 */
        $link[] = array('text' => $_LANG['back_list'], 'href' => 'comment_manage.php?act=list');
        sys_msg($_LANG['no_select_comment'], 0, $link);
    }
}

/**
 * 获取评论列表
 * @access  public
 * @return  array
 */
function get_comment_list()
{
    /* 查询条件 */
    $filter['user_name']     = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
    $filter['keywords']     = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
    if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
    {
        $filter['keywords'] = json_str_iconv($filter['keywords']);
        $filter['user_name'] = json_str_iconv($filter['user_name']);
    }
    $filter['sort_by']      = empty($_REQUEST['sort_by']) ? 'add_time' : trim(htmlspecialchars($_REQUEST['sort_by']));
    $filter['sort_order']   = empty($_REQUEST['sort_order']) ? 'DESC' : trim(htmlspecialchars($_REQUEST['sort_order']));

    $where = (!empty($filter['keywords'])) ? " AND content LIKE '%" . mysql_like_quote($filter['keywords']) . "%' " : '';
    $where .= (!empty($filter['user_name'])) ? " AND user_name LIKE '%" . mysql_like_quote($filter['user_name']) . "%' " : '';

    $sql = "SELECT count(*) FROM " .$GLOBALS['ecs']->table('comment'). " WHERE parent_id = 0 $where";
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    /* 分页大小 */
    $filter = page_and_size($filter);

    /* 获取评论数据 */
    $arr = array();
    $sql  = "SELECT * FROM " .$GLOBALS['ecs']->table('comment'). " WHERE parent_id = 0 $where " .
            " ORDER BY $filter[sort_by] $filter[sort_order] ".
            " LIMIT ". $filter['start'] .", $filter[page_size]";
    $res  = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $sql = ($row['comment_type'] == 0) ?
            "SELECT goods_name FROM " .$GLOBALS['ecs']->table('goods'). " WHERE goods_id='$row[id_value]'" :
            "SELECT title FROM ".$GLOBALS['ecs']->table('article'). " WHERE article_id='$row[id_value]'";
        $row['title'] = $GLOBALS['db']->getOne($sql);

        if($row['comment_type'] == 0){
            $row['url'] = build_uri('goods', array('gid' => $row['id_value']), $row['title']);
        }else{
            $row['url']  = build_uri('article', array('aid' => $row['id_value']), $row['title']);
        }
        /* Check Reply */
       $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('comment'). " WHERE parent_id = '$row[comment_id]'";
       $row['is_reply'] =  ($GLOBALS['db']->getOne($sql) > 0) ?
           $GLOBALS['_LANG']['yes_reply'] : $GLOBALS['_LANG']['no_reply'];

        $row['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);

        $arr[] = $row;
    }
    $filter['keywords'] = stripslashes($filter['keywords']);
    $filter['user_name'] = stripslashes($filter['user_name']);
    $arr = array('item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

?>