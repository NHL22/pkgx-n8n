<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

$exc = new exchange($ecs->table('sms'), $db, 'sms_id', 'phone_number');

/*------------------------------------------------------ */
//-- 办事处列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',      $_LANG['sms_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['add_sms'], 'href' => 'sms.php?act=add'));
    $smarty->assign('full_page',    1);

    $sms_list = get_smslist();
    $smarty->assign('sms_list',  $sms_list['sms']);
    $smarty->assign('filter',       $sms_list['filter']);
    $smarty->assign('record_count', $sms_list['record_count']);
    $smarty->assign('page_count',   $sms_list['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($sms_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('sms_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $sms_list = get_smslist();
    $smarty->assign('sms_list',  $sms_list['sms']);
    $smarty->assign('filter',       $sms_list['filter']);
    $smarty->assign('record_count', $sms_list['record_count']);
    $smarty->assign('page_count',   $sms_list['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($sms_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('sms_list.htm'), '',
        array('filter' => $sms_list['filter'], 'page_count' => $sms_list['page_count']));
}


/*------------------------------------------------------ */
//-- 删除办事处
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('sms_remove');

    $id = intval($_GET['id']);
    $name = $exc->get_name($id);
    $exc->drop($id);


    admin_log($name, 'remove', 'sms');

    clear_cache_files();

    $url = 'sms.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch')
{
    /* 取得要操作的记录编号 */
    if (empty($_POST['checkboxes']))
    {
        sys_msg($_LANG['no_record_selected']);
    }
    else
    {
        admin_priv('sms_remove');

        $ids = $_POST['checkboxes'];

        if (isset($_POST['remove']))
        {
            $sql = "DELETE FROM " . $ecs->table('sms') .
                    " WHERE sms_id " . db_create_in($ids);
            $db->query($sql);

            admin_log('', 'batch_remove', 'sms');

            clear_cache_files();

            sys_msg($_LANG['batch_drop_ok']);
        }
    }
}

elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    admin_priv('sms_send');

    $is_add = $_REQUEST['act'] == 'add';
    $smarty->assign('form_action', $is_add ? 'insert' : 'update');

    if ($is_add)
    {
        $sms = array(
            'sms_id'        => 0,
            'receiver_name' => '',
            'phone_number'  => '',
            'content'       => '',
            'campaign_name' => '',
            'resend'        => 0
        );

    }
    else
    {
        if (empty($_GET['id']))
        {
            sys_msg('invalid param');
        }

        $id = intval($_GET['id']);
        $sql = "SELECT * FROM " . $ecs->table('sms') . " WHERE sms_id = $id";
        $sms = $db->getRow($sql);
        if (empty($sms))
        {
            sys_msg('sms does not exist');
        }

    }


    $smarty->assign('sms', $sms);

    if ($is_add)
    {
        $smarty->assign('ur_here', $_LANG['add_sms']);
    }
    else
    {
        $smarty->assign('ur_here', $_LANG['edit_sms']);
    }
    if ($is_add)
    {
        $href = 'sms.php?act=list';
    }
    else
    {
        $href = 'sms.php?act=list&' . list_link_postfix();
    }
    $smarty->assign('action_link', array('href' => $href, 'text' => $_LANG['sms_list']));
    assign_query_info();
    $smarty->display('sms_info.htm');
}

/*------------------------------------------------------ */
//-- thêm và sửa
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    admin_priv('sms_send');

    $is_add = $_REQUEST['act'] == 'insert';

    $sms = array(
        'sms_id'        => intval($_POST['id']),
        'receiver_name' => trim($_POST['receiver_name']),
        'phone_number'  => trim($_POST['phone_number']),
        'content'       => trim($_POST['content']),
        'campaign_name' => trim($_POST['campaign_name'])
    );

    /* Gửi lại SMS nếu muốn */
    if ($is_add)
    {
        $sms['send_time'] = time();
        $db->autoExecute($ecs->table('sms'), $sms, 'INSERT');
        $sms['sms_id'] = $db->insert_id();
        admin_log($sms['phone_number'], 'add', 'sms');
    }
    else
    {
        $db->autoExecute($ecs->table('sms'), $sms, 'UPDATE', "sms_id = '$sms[sms_id]'");
        admin_log($sms['phone_number'], 'edit', 'sms');
    }

    /* Gửi SMS lần đầu hoặc khi gửi lại */
    $resend = intval($_POST['resend']);
    if($is_add || $resend == 1){
        require_once ROOT_PATH.'includes/SpeedSMSAPI.php';
        $phones = getnumberarr($sms['phone_number']);
        $content_sms = $sms['content'];
        $_sms = new SpeedSMSAPI(SMS_TOKEN);
        $res_sms = $_sms->sendSMS($phones, $content_sms);

        /** cập nhật trạng thái nếu có lỗi */
        if($res_sms['code'] != '00'){
            $data_update = array(
                'status'  =>$res_sms['code'],
                'message' =>$res_sms['message'],
                'invalidhone'=> serialize($res_sms['invalidPhone'])
            );
            $db->autoExecute($ecs->table('sms'), $data_update, 'UPDATE', "sms_id = '$sms[sms_id]'");
        }

        var_dump($res_sms, $res_sms['code']);
    }


    clear_cache_files();

    if ($is_add)
    {
        $links = array(
            array('href' => 'sms.php?act=add', 'text' => $_LANG['continue_add_sms']),
            array('href' => 'sms.php?act=list', 'text' => $_LANG['back_sms_list'])
        );
        sys_msg($_LANG['add_sms_ok'], 0, $links);
    }
    else
    {
        $links = array(
            array('href' => 'sms.php?act=list&' . list_link_postfix(), 'text' => $_LANG['back_sms_list'])
        );
        sys_msg($_LANG['edit_sms_ok'], 0, $links);
    }
}

/**
 * chuyển đổi số từ chuổi thành mảng
 */

function getnumberarr($number_str){
    $arr = array();
    /* remove muti spaces */
    $number_str = preg_replace('/\s+/', '',$number_str);
    /* remove , end string */
    $number_str = trim($number_str, ",");
    if(strpos($number_str, ',')){
        $arr =  explode(',',$number_str);
    }else{
       $arr[] = $number_str;
    }
    return $arr;

}
/**
 * lấy danh sách SMS đã gửi
 * @return  array
 */
function get_smslist()
{
    $result = get_filter();
    if ($result === false)
    {
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'sms_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND phone_number LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('sms').' WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter = page_and_size($filter);

        /* 查询记录 */
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('sms') . "  WHERE 1 " .$where. " ORDER BY $filter[sort_by] $filter[sort_order]";

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    $arr = array();
    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        $rows['send_time'] = date('d-m-Y H:i:s', $rows['send_time']);
        $arr[] = $rows;
    }

    return array('sms' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>