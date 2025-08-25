<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

$exc = new exchange($ecs->table('slug'), $db, 'slug_id', 'slug');

/*------------------------------------------------------ */
//-- 办事处列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',      $_LANG['slug_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['add_slug'], 'href' => 'slug.php?act=add'));
    $smarty->assign('full_page',    1);

    $slug_list = get_sluglist();
    $smarty->assign('slug_list',  $slug_list['slug']);
    $smarty->assign('filter',       $slug_list['filter']);
    $smarty->assign('record_count', $slug_list['record_count']);
    $smarty->assign('page_count',   $slug_list['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($slug_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('slug_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $slug_list = get_sluglist();
    $smarty->assign('slug_list',  $slug_list['slug']);
    $smarty->assign('filter',       $slug_list['filter']);
    $smarty->assign('record_count', $slug_list['record_count']);
    $smarty->assign('page_count',   $slug_list['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($slug_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('slug_list.htm'), '',
        array('filter' => $slug_list['filter'], 'page_count' => $slug_list['page_count']));
}


/*------------------------------------------------------ */
//-- 删除办事处
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('shop_config');

    $id = intval($_GET['id']);
    $name = $exc->get_name($id);
    $exc->drop($id);


    /* 记日志 */
    admin_log($name, 'remove', 'slug');

    /* 清除缓存 */
    clear_cache_files();

    $url = 'slug.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

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
        /* 检查权限 */
        admin_priv('shop_config');

        $ids = $_POST['checkboxes'];

        if (isset($_POST['remove']))
        {
            /* 删除记录 */
            $sql = "DELETE FROM " . $ecs->table('slug') .
                    " WHERE slug_id " . db_create_in($ids);
            $db->query($sql);

            /* 记日志 */
            admin_log('', 'batch_remove', 'slug');

            /* 清除缓存 */
            clear_cache_files();

            sys_msg($_LANG['batch_drop_ok']);
        }
    }
}

/*------------------------------------------------------ */
//-- 添加、编辑办事处
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    admin_priv('shop_config');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'add';
    $smarty->assign('form_action', $is_add ? 'insert' : 'update');

    /* 初始化、取得办事处信息 */
    if ($is_add)
    {
        $slug = array(
            'slug_id'     => 0,
            'slug'   => '',
            'module'   => '',
            'id'   => 0
        );
    }
    else
    {
        if (empty($_GET['id']))
        {
            sys_msg('invalid param');
        }

        $id = $_GET['id'];
        $sql = "SELECT * FROM " . $ecs->table('slug') . " WHERE slug_id = '$id'";
        $slug = $db->getRow($sql);
        if (empty($slug))
        {
            sys_msg('slug does not exist');
        }

    }


    $smarty->assign('slug', $slug);

    /* 显示模板 */
    if ($is_add)
    {
        $smarty->assign('ur_here', $_LANG['add_slug']);
    }
    else
    {
        $smarty->assign('ur_here', $_LANG['edit_slug']);
    }
    if ($is_add)
    {
        $href = 'slug.php?act=list';
    }
    else
    {
        $href = 'slug.php?act=list&' . list_link_postfix();
    }
    $smarty->assign('action_link', array('href' => $href, 'text' => $_LANG['slug_list']));
    assign_query_info();
    $smarty->display('slug_info.htm');
}

/*------------------------------------------------------ */
//-- 提交添加、编辑办事处
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    /* 检查权限 */
    admin_priv('shop_config');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'insert';

    /* 提交值 */
    $slug = array(
        'slug_id'     => intval($_POST['id']),
        'slug'   => trim($_POST['slug']),
        'module'   => trim($_POST['module']),
        'id'   => intval($_POST['linkid'])
    );

    /* 判断名称是否重复 */
    if (!$exc->is_only('slug', $slug['slug'], $slug['slug_id']))
    {
        sys_msg($_LANG['slug_exist']);
    }

    /* 保存办事处信息 */
    if ($is_add)
    {
        $db->autoExecute($ecs->table('slug'), $slug, 'INSERT');
        $slug['slug_id'] = $db->insert_id();
    }
    else
    {
        $db->autoExecute($ecs->table('slug'), $slug, 'UPDATE', "slug_id = '$slug[slug_id]'");
    }


    /* 记日志 */
    if ($is_add)
    {
        admin_log($slug['slug'], 'add', 'slug');
    }
    else
    {
        admin_log($slug['slug'], 'edit', 'slug');
    }

    /* 清除缓存 */
    clear_cache_files();

    /* 提示信息 */
    if ($is_add)
    {
        $links = array(
            array('href' => 'slug.php?act=add', 'text' => $_LANG['continue_add_slug']),
            array('href' => 'slug.php?act=list', 'text' => $_LANG['back_slug_list'])
        );
        sys_msg($_LANG['add_slug_ok'], 0, $links);
    }
    else
    {
        $links = array(
            array('href' => 'slug.php?act=list&' . list_link_postfix(), 'text' => $_LANG['back_slug_list'])
        );
        sys_msg($_LANG['edit_slug_ok'], 0, $links);
    }
}

/**
 * 取得办事处列表
 * @return  array
 */
function get_sluglist()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 初始化分页参数 */
        $filter = array();
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'slug_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND slug LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 查询记录总数，计算分页数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('slug').' WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter = page_and_size($filter);

        /* 查询记录 */
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('slug') . "  WHERE 1 " .$where. " ORDER BY $filter[sort_by] $filter[sort_order]";

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
        $arr[] = $rows;
    }

    return array('slug' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>