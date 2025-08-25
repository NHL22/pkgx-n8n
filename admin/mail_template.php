<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

$exc = new exchange($ecs->table('mail_templates'), $db, 'template_id', 'template_code');

/*------------------------------------------------------ */
//-- 办事处列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $smarty->assign('ur_here',      $_LANG['mail_template_manage']);
    $smarty->assign('action_link',  array('text' => $_LANG['add_mail_template'], 'href' => 'mail_template.php?act=add'));
    $smarty->assign('full_page',    1);

    $mail_template_list = get_mail_templatelist();
    $smarty->assign('mail_template_list',  $mail_template_list['mail_template']);
    $smarty->assign('filter',       $mail_template_list['filter']);
    $smarty->assign('record_count', $mail_template_list['record_count']);
    $smarty->assign('page_count',   $mail_template_list['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($mail_template_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('mail_template_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $mail_template_list = get_mail_templatelist();
    $smarty->assign('mail_template_list',  $mail_template_list['mail_template']);
    $smarty->assign('filter',       $mail_template_list['filter']);
    $smarty->assign('record_count', $mail_template_list['record_count']);
    $smarty->assign('page_count',   $mail_template_list['page_count']);

    /* 排序标记 */
    $sort_flag  = sort_flag($mail_template_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('mail_template_list.htm'), '',
        array('filter' => $mail_template_list['filter'], 'page_count' => $mail_template_list['page_count']));
}


/*------------------------------------------------------ */
//-- 删除办事处
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('mail_template');

    $id = intval($_GET['id']);

    $allow_delete = $db->getOne("SELECT allow_delete FROM ".$ecs->table('mail_templates')." WHERE template_id = $id");
    if($allow_delete == 1){
        $exc->drop($id);
    }else{
        sys_msg('Can not remove this template');
    }

    /* 清除缓存 */
    clear_cache_files();

    $url = 'mail_template.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 添加、编辑办事处
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    admin_priv('mail_template');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'add';
    $smarty->assign('form_action', $is_add ? 'insert' : 'update');

    /* 初始化、取得办事处信息 */
    if ($is_add)
    {
       $mail_template = array(
        'template_id'     => 0,
        'template_code'   => '',
        'template_subject'   =>'',
        'template_content'   => '',
        'allow_delete'   => 1
    );
    }
    else
    {
        if (empty($_GET['id']))
        {
            sys_msg('invalid param');
        }

        $id = $_GET['id'];
        $sql = "SELECT * FROM " . $ecs->table('mail_templates') . " WHERE template_id = '$id' AND type = 'template'";
        $mail_template = $db->getRow($sql);


        if (empty($mail_template))
        {
            sys_msg('mail_template does not exist');
        }


    }

     CKeditor('content', $mail_template['template_content']);
    $smarty->assign('template', $mail_template);

    /* 显示模板 */
    if ($is_add)
    {
        $smarty->assign('ur_here', $_LANG['add_mail_template']);
    }
    else
    {
        $smarty->assign('ur_here', $_LANG['edit_mail_template']);
    }
    if ($is_add)
    {
        $href = 'mail_template.php?act=list';
    }
    else
    {
        $href = 'mail_template.php?act=list&' . list_link_postfix();
    }
    $smarty->assign('action_link', array('href' => $href, 'text' => $_LANG['back_mail_template_list']));
    assign_query_info();
    $smarty->assign('full_page',    1);
    $smarty->display('mail_template.htm');
}

/*------------------------------------------------------ */
//-- 提交添加、编辑办事处
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    /* 检查权限 */
    admin_priv('mail_template');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'insert';

    /* 提交值 */
    $mail_template = array(
        'template_id'     => intval($_POST['id']),
        'template_code'   => empty($_POST['template_code']) ? build_slug($_POST['template_code']) : $_POST['template_code'],
        'template_subject'   => strip_tags($_POST['subject']),
        'template_content'   => $_POST['content'],
        'last_modify' => time()
    );

    /* 判断名称是否重复 */
    if (!$exc->is_only('template_code', $mail_template['template_code'], $mail_template['template_id']))
    {
        sys_msg($_LANG['mail_template_exist']);
    }

    /* 保存办事处信息 */
    if ($is_add)
    {
        $mail_template['type'] = 'template';
        $db->autoExecute($ecs->table('mail_templates'), $mail_template, 'INSERT');
        $mail_template['template_id'] = $db->insert_id();
    }
    else
    {
        $db->autoExecute($ecs->table('mail_templates'), $mail_template, 'UPDATE', "template_id = '$mail_template[template_id]' AND type='template'");
    }



    /* 清除缓存 */
    clear_cache_files();

    /* 提示信息 */
    if ($is_add)
    {
        $links = array(
            array('href' => 'mail_template.php?act=add', 'text' => $_LANG['continue_add_mail_template']),
            array('href' => 'mail_template.php?act=list', 'text' => $_LANG['back_mail_template_list'])
        );
        sys_msg($_LANG['update_success'], 0, $links);
    }
    else
    {
        $links = array(
            array('href' => 'mail_template.php?act=list&' . list_link_postfix(), 'text' => $_LANG['back_mail_template_list'])
        );
        sys_msg($_LANG['update_success'], 0, $links);
    }
}

/**
 * 取得办事处列表
 * @return  array
 */
function get_mail_templatelist()
{
    $result = get_filter();
    if ($result === false)
    {
        /* 初始化分页参数 */
        $filter = array();

        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'template_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
        {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND template_subject LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }

        /* 查询记录总数，计算分页数 */
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('mail_templates')." WHERE type = 'template' $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter = page_and_size($filter);

        /* 查询记录 */
        $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('mail_templates') . " WHERE type = 'template' $where ORDER BY $filter[sort_by] $filter[sort_order]";

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
        $rows['last_modify'] = date('d-m-Y H:i:s', $rows['last_modify']);
        $arr[] = $rows;
    }

    return array('mail_template' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

?>