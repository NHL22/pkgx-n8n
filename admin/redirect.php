<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/**
 * Module này sử dụng cache tĩnh. ko Lưu vào CSDL
 * @var string
 */
$data_name = 'redirect_data';

/*------------------------------------------------------ */
//-- 办事处列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',      'Danh Sách');
    $smarty->assign('action_link',  array('text' => 'Thêm mới', 'href' => 'redirect.php?act=add'));
    $smarty->assign('full_page',    1);

    $redirect = read_static_data($data_name);

    $smarty->assign('redirect_list',  $redirect);

    $smarty->display('redirect_list.htm');
}

elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('shop_config');

    $redirect = read_static_data($data_name);

    $smarty->assign('redirect_list',    $redirect);
    $smarty->assign('filter',          $redirect['filter']);
    $smarty->assign('record_count',    $redirect['record_count']);
    $smarty->assign('page_count',      $redirect['page_count']);

    $sort_flag  = sort_flag($redirect['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('redirect_list.htm'), '',
        array('filter' => $redirect['filter'], 'page_count' => $redirect['page_count']));
}


/*------------------------------------------------------ */
//-- 删除办事处
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('shop_config');

    $redirect = read_static_data($data_name);
    $id = intval($_GET['id']);
    unset($redirect[$id]);

    /* Ghi lại file */
    write_static_data($data_name, $redirect);
    $url = 'redirect.php?act=list';
    ecs_header("Location: $url\n");
    exit;
}

elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    admin_priv('shop_config');

    $is_add = $_REQUEST['act'] == 'add';
    $smarty->assign('form_action', $is_add ? 'insert' : 'update');

    $redirect_data = read_static_data($data_name);

    if ($is_add)
    {
        $redirect = array(
            'id'           => count($redirect_data)+1,
            'redirect_url'  => '',
            'redirect_dest' => '',
            'redirect_type'  => 301
        );

    }else{

        if (!isset($_GET['id']) || $_GET['id'] == '')
        {
            sys_msg('invalid param');
        }

        $id = $_GET['id'];

        $redirect = $redirect_data[$id];
        $redirect['id'] = $id;
    }


    $smarty->assign('redirect', $redirect);


    /* 显示模板 */
    if ($is_add)
    {
        $smarty->assign('ur_here', 'Thêm mới');
    }
    else
    {
        $smarty->assign('ur_here', 'Chỉnh sửa');
    }
    if ($is_add)
    {
        $href = 'redirect.php?act=list';
    }
    else
    {
        $href = 'redirect.php?act=list&' . list_link_postfix();
    }
    $smarty->assign('action_link', array('href' => $href, 'text' => 'Danh sách'));

    $smarty->display('redirect_info.htm');
}

/*------------------------------------------------------ */
//-- 提交添加、编辑办事处
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    admin_priv('shop_config');

    $is_add = $_REQUEST['act'] == 'insert';

    $redirect = read_static_data($data_name);

    if ($is_add)
    {
        $redirect[] = array(
            'redirect_url'    => isset($_POST['redirect_url']) ? trim($_POST['redirect_url']) : '',
            'redirect_dest'   => isset($_POST['redirect_dest']) ? trim($_POST['redirect_dest']) : '',
            'redirect_type'   => isset($_POST['redirect_type']) ? intval($_POST['redirect_type']) : 301
        );
    }
    else
    {
        $redirect[$_POST['id']] = array(
            'redirect_url'    => isset($_POST['redirect_url']) ? trim($_POST['redirect_url']) : '',
            'redirect_dest'   => isset($_POST['redirect_dest']) ? trim($_POST['redirect_dest']) : '',
            'redirect_type'   => isset($_POST['redirect_type']) ? intval($_POST['redirect_type']) : 301
        );

    }

    /* Ghi lại file */
    write_static_data($data_name, $redirect);


    /* 提示信息 */
    if ($is_add)
    {
        $links = array(
            array('href' => 'redirect.php?act=add', 'text' => 'Tiếp tục thêm'),
            array('href' => 'redirect.php?act=list', 'text' => 'Trở lại danh sách')
        );
        sys_msg('Thêm thành công !', 0, $links);
    }
    else
    {
        $links = array(
            array('href' => 'redirect.php?act=list&' . list_link_postfix(), 'text' => 'Trở lại danh sách')
        );
        sys_msg('Sửa thành công !', 0, $links);
    }
}


?>