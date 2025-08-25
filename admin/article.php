<?php

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/cls_image.php');

/*初始化数据交换对象 */
$exc   = new exchange($ecs->table("article"), $db, 'article_id', 'title');
//$image = new cls_image();

/* 允许上传的文件类型 */
$allow_file_types = '|GIF|JPG|PNG|BMP|SWF|DOC|XLS|PPT|MID|WAV|ZIP|RAR|PDF|CHM|RM|TXT|';
$allow_image_types = array("gif", "jpg", "jpeg", "png", "bmp");

/*------------------------------------------------------ */
//-- 文章列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 取得过滤条件 */
    $filter = array();
    $smarty->assign('cat_select',  article_cat_list(0));
    $smarty->assign('ur_here',      $_LANG['03_article_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['article_add'], 'href' => 'article.php?act=add'));
    $smarty->assign('full_page',    1);
    $smarty->assign('filter',       $filter);

    $article_list = get_articleslist();

    $smarty->assign('article_list',    $article_list['arr']);
    $smarty->assign('filter',          $article_list['filter']);
    $smarty->assign('record_count',    $article_list['record_count']);
    $smarty->assign('page_count',      $article_list['page_count']);

    $sort_flag  = sort_flag($article_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('article_list.htm');
}

/*------------------------------------------------------ */
//-- 翻页，排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    check_authz_json('article_manage');

    $article_list = get_articleslist();

    $smarty->assign('article_list',    $article_list['arr']);
    $smarty->assign('filter',          $article_list['filter']);
    $smarty->assign('record_count',    $article_list['record_count']);
    $smarty->assign('page_count',      $article_list['page_count']);

    $sort_flag  = sort_flag($article_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('article_list.htm'), '',
        array('filter' => $article_list['filter'], 'page_count' => $article_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加文章
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'add')
{
    /* 权限判断 */
    admin_priv('article_manage');

    /* 创建 html editor */
    CKeditor('content', '');

    /*初始化*/
    $article = array();
    $article['is_open'] = 1;
    $article['meta_robots'] = 'INDEX,FOLLOW';

    /* 取得分类、品牌 */
    $smarty->assign('goods_cat_list', cat_list());
    $smarty->assign('brand_list',     get_brand_list());

    /* 清理关联商品 */
    $sql = "DELETE FROM " . $ecs->table('goods_article') . " WHERE article_id = 0";
    $db->query($sql);

    if (isset($_GET['id']))
    {
        $smarty->assign('cur_id',  $_GET['id']);
    }
    $smarty->assign('article',     $article);
    $smarty->assign('cat_select',  article_cat_list(0));
    $smarty->assign('ur_here',     $_LANG['article_add']);
    $smarty->assign('action_link', array('text' => $_LANG['03_article_list'], 'href' => 'article.php?act=list'));
    $smarty->assign('form_action', 'insert');

    assign_query_info();
    $smarty->display('article_info.htm');
}

/*------------------------------------------------------ */
//-- 添加文章
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'insert')
{
    /* 权限判断 */
    admin_priv('article_manage');

    $title = isset($_POST['title']) ? filter_var($_POST['title'],FILTER_SANITIZE_STRING) : '';
    $article_cat = isset($_POST['article_cat']) ? intval($_POST['article_cat']) : 0;
    /*Check Exist title */
    $is_only = $exc->is_only('title', $title,0, " cat_id ='$article_cat'");

    if (!$is_only)
    {
        sys_msg(sprintf($_LANG['title_exist'], stripslashes($title)), 1);
    }
    /* edit by ecshopvietnam.com */
    $article_type = isset($_POST['article_type']) ? intval($_POST['article_type']) : 0;
    $author = isset($_POST['author']) ? filter_var($_POST['author'],FILTER_SANITIZE_STRING) : '';
    $author_email = isset($_POST['author_email']) ? filter_var($_POST['author_email'],FILTER_SANITIZE_EMAIL) : '';
    $keywords = isset($_POST['keywords']) ? filter_var($_POST['keywords'],FILTER_SANITIZE_STRING) : '';
    $description = isset($_POST['description']) ? filter_var($_POST['description'],FILTER_SANITIZE_STRING) : '';
    $link_url = isset($_POST['link_url']) ? filter_var($_POST['link_url'],FILTER_SANITIZE_URL) : '';
    $is_open = isset($_POST['is_open']) ? intval($_POST['is_open']) : 0;
    $open_type = 0;
    $add_time = time();
    $custom_title = isset($_POST['custom_title']) ? filter_var($_POST['custom_title'],FILTER_SANITIZE_STRING) : '';
    $meta_title = isset($_POST['meta_title']) ? filter_var($_POST['meta_title'],FILTER_SANITIZE_STRING) : '';
    $meta_desc = isset($_POST['meta_desc']) ? filter_var($_POST['meta_desc'],FILTER_SANITIZE_STRING) : '';
    $meta_robots = isset($_POST['meta_robots']) ? $_POST['meta_robots'] : 'INDEX,FOLLOW';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $template_file = isset($_POST['template_file']) ? filter_var($_POST['template_file'],FILTER_SANITIZE_STRING) : '';

    $thumb = upload_thumb();

    if (empty($_POST['cat_id']))
    {
        $_POST['cat_id'] = 0;
    }
    $sql = "INSERT INTO ".$ecs->table('article')."(title, custom_title, template_file, meta_robots, meta_title, meta_desc, article_thumb, article_sthumb, article_mthumb, cat_id, article_type, is_open, author, ".
                "author_email, keywords, content, add_time, open_type, link, description) ".
            "VALUES ('$title', '$custom_title', '$template_file', '$meta_robots', '$meta_title', '$meta_desc', '$thumb[thumb]', '$thumb[sthumb]', '$thumb[mthumb]', '$article_cat', '$article_type', '$is_open','$author', '$author_email', '$keywords', '$content', ".
                "'$add_time', '$open_type', '$link_url', '$description')";
    $db->query($sql);

    /* 处理关联商品 */
    $article_id = $db->insert_id();

    if($article_id){
        create_slug($article_id, 'article');
        $sql = "UPDATE " . $ecs->table('goods_article') . " SET article_id = '$article_id' WHERE article_id = 0";
        $db->query($sql);
    }


    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'article.php?act=add';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'article.php?act=list';

    admin_log($_POST['title'],'add','article');

    clear_cache_files();

    sys_msg($_LANG['articleadd_succeed'],0, $link);
}

/*------------------------------------------------------ */
//-- 编辑
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'edit')
{
    /* 权限判断 */
    admin_priv('article_manage');

    /* 取文章数据 */
    $sql = "SELECT * FROM " .$ecs->table('article'). " WHERE article_id='$_REQUEST[id]'";
    $article = $db->GetRow($sql);

    /* 创建 html editor */
    CKeditor('content', html_entity_decode($article['content']));

    /* 取得分类、品牌 */
    $smarty->assign('goods_cat_list', cat_list());
    $smarty->assign('brand_list', get_brand_list());

    /* 取得关联商品 */
    $goods_list = get_article_goods($_REQUEST['id']);
    $smarty->assign('goods_list', $goods_list);

    $smarty->assign('slug', get_slug($_REQUEST['id'], 'article'));

    $smarty->assign('article',     $article);
    $smarty->assign('cat_select',  article_cat_list(0, $article['cat_id']));
    $smarty->assign('ur_here',     $_LANG['article_edit']);
    $smarty->assign('action_link', array('text' => $_LANG['03_article_list'], 'href' => 'article.php?act=list&' . list_link_postfix()));
    $smarty->assign('form_action', 'update');

    assign_query_info();
    $smarty->display('article_info.htm');
}

if ($_REQUEST['act'] =='update')
{
    /* 权限判断 */
    admin_priv('article_manage');

    $title = isset($_POST['title']) ? filter_var($_POST['title'],FILTER_SANITIZE_STRING) : '';
    $article_cat = isset($_POST['article_cat']) ? intval($_POST['article_cat']) : 0;
    /*Check Exist title */
    $is_only = $exc->is_only('title', $title, $_POST['id'], "cat_id = '$article_cat'");
    if (!$is_only)
    {
        sys_msg(sprintf($_LANG['title_exist'], stripslashes($title)), 1);
    }
    /* edit by ecshopvietnam.com */
    $article_type = isset($_POST['article_type']) ? intval($_POST['article_type']) : 0;
    $author = isset($_POST['author']) ? filter_var($_POST['author'],FILTER_SANITIZE_STRING) : '';
    $author_email = isset($_POST['author_email']) ? filter_var($_POST['author_email'],FILTER_SANITIZE_EMAIL) : '';
    $keywords = isset($_POST['keywords']) ? filter_var($_POST['keywords'],FILTER_SANITIZE_STRING) : '';
    $description = isset($_POST['description']) ? filter_var($_POST['description'],FILTER_SANITIZE_STRING) : '';
    $link_url = isset($_POST['link_url']) ? filter_var($_POST['link_url'],FILTER_SANITIZE_URL) : '';
    $modify_time = time();
    $custom_title = isset($_POST['custom_title']) ? filter_var($_POST['custom_title'],FILTER_SANITIZE_STRING) : '';
    $meta_title = isset($_POST['meta_title']) ? filter_var($_POST['meta_title'],FILTER_SANITIZE_STRING) : '';
    $meta_desc = isset($_POST['meta_desc']) ? filter_var($_POST['meta_desc'],FILTER_SANITIZE_STRING) : '';
    $template_file = isset($_POST['template_file']) ? filter_var($_POST['template_file'],FILTER_SANITIZE_STRING) : '';
    $meta_robots = isset($_POST['meta_robots']) ? $_POST['meta_robots'] : 'INDEX,FOLLOW';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $is_open = isset($_POST['is_open']) ? intval($_POST['is_open']) : 0;

    if (empty($_POST['cat_id']))
    {
        $_POST['cat_id'] = 0;
    }

    $sql_update = " modify_time = '$modify_time', ";

    if (isset($_FILES['article_thumb']) && $_FILES['article_thumb']['tmp_name'] != '' &&
        isset($_FILES['article_thumb']['tmp_name']) &&$_FILES['article_thumb']['tmp_name'] != 'none')
    {
        $thumb = upload_thumb();
        unlink_thumb($_POST['old_thumb'], $_POST['old_sthumb'], $_POST['old_mthumb']);
        $sql_update .= "article_thumb ='$thumb[thumb]', article_sthumb ='$thumb[sthumb]', article_mthumb ='$thumb[mthumb]', ";
    }
    $sql_update .= " is_open = '$is_open', ";
    $sql_update .= " meta_title = '$meta_title', ";
    $sql_update .= " meta_desc = '$meta_desc', ";
    $sql_update .= " custom_title = '$custom_title', ";
    $sql_update .= " template_file = '$template_file', ";

    if ($exc->edit("title='$title', meta_robots='$meta_robots', {$sql_update} cat_id='$article_cat', article_type='$article_type', author='$author', author_email='$author_email', keywords ='$keywords', content='$content', link='$link_url', description = '$description'", $_POST['id']))
    {
         update_slug($_POST['id'], 'article');

        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'article.php?act=list&' . list_link_postfix();

        $note = sprintf($_LANG['articleedit_succeed'], stripslashes($_POST['title']));
        admin_log($_POST['title'], 'edit', 'article');

        clear_cache_files();

        sys_msg($note, 0, $link);
    }
    else
    {
        die($db->error());
    }
}

/*------------------------------------------------------ */
//-- 编辑文章主题
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_title')
{
    check_authz_json('article_manage');

    $id    = intval($_POST['id']);
    $title = json_str_iconv(trim($_POST['val']));

    /* 检查文章标题是否重复 */
    if ($exc->num("title", $title, $id) != 0)
    {
        make_json_error(sprintf($_LANG['title_exist'], $title));
    }
    else
    {
        if ($exc->edit("title = '$title'", $id))
        {
            clear_cache_files();
            admin_log($title, 'edit', 'article');
            make_json_result(stripslashes($title));
        }
        else
        {
            make_json_error($db->error());
        }
    }
}

/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_show')
{
    check_authz_json('article_manage');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("is_open = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 切换文章重要性
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_type')
{
    check_authz_json('article_manage');

    $id     = intval($_POST['id']);
    $val    = intval($_POST['val']);

    $exc->edit("article_type = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}



/*------------------------------------------------------ */
//-- 删除文章主题
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('article_manage');

    $id = intval($_GET['id']);


    $sql = "SELECT  article_thumb, article_sthumb, article_mthumb FROM " . $ecs->table('article') . " WHERE article_id = '$id'";
    $rm = $db->getRow($sql);

        /* xóa thumb */
    unlink_thumb($rm['article_thumb'], $rm['article_sthumb'],$rm['article_mthumb']);


    $name = $exc->get_name($id);
    if ($exc->drop($id))
    {
         del_slug($id, 'article');
        $db->query("DELETE FROM " . $ecs->table('comment') . " WHERE " . "comment_type = 1 AND id_value = $id");

        admin_log(addslashes($name),'remove','article');
        clear_cache_files();
    }

    $url = 'article.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 将商品加入关联
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add_link_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    check_authz_json('article_manage');

    $add_ids = $json->decode($_GET['add_ids']);
    $args = $json->decode($_GET['JSON']);
    $article_id = $args[0];

    if ($article_id == 0)
    {
        $article_id = $db->getOne('SELECT MAX(article_id)+1 AS article_id FROM ' .$ecs->table('article'));
    }

    foreach ($add_ids AS $key => $val)
    {
        $sql = 'INSERT INTO ' . $ecs->table('goods_article') . ' (goods_id, article_id) '.
               "VALUES ('$val', '$article_id')";
        $db->query($sql, 'SILENT') or make_json_error($db->error());
    }

    /* 重新载入 */
    $arr = get_article_goods($article_id);
    $opt = array();

    foreach ($arr AS $key => $val)
    {
        $opt[] = array('value'  => $val['goods_id'],
                        'text'  => $val['goods_name'],
                        'data'  => '');
    }

    make_json_result($opt);
}

/*------------------------------------------------------ */
//-- 将商品删除关联
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_link_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    check_authz_json('article_manage');

    $drop_goods     = $json->decode($_GET['drop_ids']);
    $arguments      = $json->decode($_GET['JSON']);
    $article_id     = $arguments[0];

    if ($article_id == 0)
    {
        $article_id = $db->getOne('SELECT MAX(article_id)+1 AS article_id FROM ' .$ecs->table('article'));
    }

    $sql = "DELETE FROM " . $ecs->table('goods_article').
            " WHERE article_id = '$article_id' AND goods_id " .db_create_in($drop_goods);
    $db->query($sql, 'SILENT') or make_json_error($db->error());

    /* 重新载入 */
    $arr = get_article_goods($article_id);
    $opt = array();

    foreach ($arr AS $key => $val)
    {
        $opt[] = array('value'  => $val['goods_id'],
                        'text'  => $val['goods_name'],
                        'data'  => '');
    }

    make_json_result($opt);
}

/*------------------------------------------------------ */
//-- 搜索商品
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'get_goods_list')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON;

    $filters = $json->decode($_GET['JSON']);

    $arr = get_goods_list($filters);
    $opt = array();

    foreach ($arr AS $key => $val)
    {
        $opt[] = array('value' => $val['goods_id'],
                        'text' => $val['goods_name'],
                        'data' => $val['shop_price']);
    }

    make_json_result($opt);
}
/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'batch')
{
    /* 批量删除 */
    if (isset($_POST['type']))
    {
        if ($_POST['type'] == 'button_remove')
        {
            admin_priv('article_manage');

            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg($_LANG['no_select_article'], 1);
            }

            $sql = "SELECT  article_thumb, article_sthumb, article_mthumb FROM " . $ecs->table('article') .
                    " WHERE article_id " . db_create_in(join(',', $_POST['checkboxes']));
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {

                unlink_thumb($row['article_thumb'], $row['article_sthumb'],$row['article_mthumb']);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
                del_slug($id, 'article');
                if ($exc->drop($id))
                {
                    $name = $exc->get_name($id);
                    admin_log(addslashes($name),'remove','article');
                }
            }

        }

        /* 批量隐藏 */
        if ($_POST['type'] == 'button_hide')
        {
            check_authz_json('article_manage');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg($_LANG['no_select_article'], 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("is_open = '0'", $id);
            }
        }

        /* 批量显示 */
        if ($_POST['type'] == 'button_show')
        {
            check_authz_json('article_manage');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']))
            {
                sys_msg($_LANG['no_select_article'], 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("is_open = '1'", $id);
            }
        }

        /* 批量移动分类 */
        if ($_POST['type'] == 'move_to')
        {
            check_authz_json('article_manage');
            if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes']) )
            {
                sys_msg($_LANG['no_select_article'], 1);
            }

            if(!$_POST['target_cat'])
            {
                sys_msg($_LANG['no_select_act'], 1);
            }

            foreach ($_POST['checkboxes'] AS $key => $id)
            {
              $exc->edit("cat_id = '".$_POST['target_cat']."'", $id);
            }
        }
    }

    /* 清除缓存 */
    clear_cache_files();
    $lnk[] = array('text' => $_LANG['back_list'], 'href' => 'article.php?act=list');
    sys_msg($_LANG['batch_handle_ok'], 0, $lnk);
}

/* 把商品删除关联 */
function drop_link_goods($goods_id, $article_id)
{
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('goods_article') .
            " WHERE goods_id = '$goods_id' AND article_id = '$article_id' LIMIT 1";
    $GLOBALS['db']->query($sql);
    create_result(true, '', $goods_id);
}

/* 取得文章关联商品 */
function get_article_goods($article_id)
{
    $list = array();
    $sql  = 'SELECT g.goods_id, g.goods_name'.
            ' FROM ' . $GLOBALS['ecs']->table('goods_article') . ' AS ga'.
            ' LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = ga.goods_id'.
            " WHERE ga.article_id = '$article_id'";
    $list = $GLOBALS['db']->getAll($sql);

    return $list;
}

/* 获得文章列表 */
function get_articleslist()
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
        $filter['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'a.article_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = '';
        if (!empty($filter['keyword']))
        {
            $where = " AND a.title LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }
        if ($filter['cat_id'])
        {
            $where .= " AND a." . get_article_children($filter['cat_id']);
        }

        /* 文章总数 */
        $sql = 'SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('article'). ' AS a '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('article_cat'). ' AS ac ON ac.cat_id = a.cat_id '.
               'WHERE 1 ' .$where;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = page_and_size($filter);

        /* 获取文章数据 */
        $sql = 'SELECT a.* , ac.cat_name '.
               'FROM ' .$GLOBALS['ecs']->table('article'). ' AS a '.
               'LEFT JOIN ' .$GLOBALS['ecs']->table('article_cat'). ' AS ac ON ac.cat_id = a.cat_id '.
               'WHERE 1 ' .$where. ' ORDER by '.$filter['sort_by'].' '.$filter['sort_order'];

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    }
    else
    {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $arr = array();
    $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);

    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        $rows['date'] = local_date($GLOBALS['_CFG']['time_format'], $rows['add_time']);
        $rows['url'] = build_uri('article', array('aid'=>$rows['article_id']));
        $arr[] = $rows;
    }

    return array('arr' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

/* 上传文件 */
function upload_article_file($upload)
{
    if (!make_dir("../" . DATA_DIR . "/article"))
    {
        /* 创建目录失败 */
        return false;
    }

    $filename = cls_image::random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
    $path     = ROOT_PATH. DATA_DIR . "/article/" . $filename;

    if (move_upload_file($upload['tmp_name'], $path))
    {
        return DATA_DIR . "/article/" . $filename;
    }
    else
    {
        return false;
    }
}

function upload_thumb(){
    $thumbs = array('thumb' => '', 'sthumb' => '');
    if (isset($_FILES['article_thumb']) && $_FILES['article_thumb']['tmp_name'] != '' &&
        isset($_FILES['article_thumb']['tmp_name']) &&$_FILES['article_thumb']['tmp_name'] != 'none')
    {
        if (!check_type_allow($_FILES['article_thumb']['name'], $GLOBALS['allow_image_types']))
        {
            sys_msg($GLOBALS['_LANG']['invalid_file']);
        }
        $image = new cls_image($GLOBALS['_CFG']['bgcolor']);
        $slug = build_slug($_POST['title']);
        $filename = $slug.'-thumb-'.time();
        $filename_small = $slug.'-sthumb-'.time();
        $filename_mobile = $slug.'-mthumb-'.time();
        $dir = ROOT_PATH .CDN_PATH.'/article_thumb/' . date('Ym').'/';
        if (!$dir){
            @mkdir($dir, 0777);
        }
        $thumbnail = $image->make_thumb($_FILES['article_thumb']['tmp_name'], $GLOBALS['_CFG']['article_thumb_width'],$GLOBALS['_CFG']['article_thumb_height'], $dir, '', $filename);
        $small_thumb = $image->make_thumb($_FILES['article_thumb']['tmp_name'], $GLOBALS['_CFG']['article_sthumb_width'],$GLOBALS['_CFG']['article_sthumb_height'], $dir, '', $filename_small);
        $mobile_thumb = $image->make_thumb($_FILES['article_thumb']['tmp_name'], $GLOBALS['_CFG']['article_mthumb_width'],$GLOBALS['_CFG']['article_mthumb_height'], $dir, '', $filename_mobile);
        
        /*convert to Webp*/
        $thumbnail_webp = $image->convertWebp(ROOT_PATH.CDN_PATH.'/'.$thumbnail);
        $image->convertWebp(ROOT_PATH.CDN_PATH.'/'.$small_thumb);
        $image->convertWebp(ROOT_PATH. CDN_PATH.'/'.$mobile_thumb);


        if ($thumbnail == false)
        {
            sys_msg($image->error_msg(), 1, array(), false);
        }
        if ($small_thumb == false)
        {
            sys_msg($image->error_msg(), 1, array(), false);
        }
         if ($mobile_thumb == false)
        {
            sys_msg($image->error_msg(), 1, array(), false);
        }
        $thumbs = array('thumb' => $thumbnail, 'sthumb' => $small_thumb,'mthumb'=> $mobile_thumb);
    }
    return $thumbs;
}
function unlink_old_thumb(){
    @unlink(ROOT_PATH. CDN_PATH.'/'. $_POST['old_thumb']);
    @unlink(ROOT_PATH. CDN_PATH.'/'. $_POST['old_sthumb']);
    @unlink(ROOT_PATH. CDN_PATH.'/'. $_POST['old_mthumb']);
}

function unlink_thumb($thumb,$sthumb, $mthumb){
    $img_thumb  = ROOT_PATH. CDN_PATH.'/'. $thumb;
    $img_sthumb = ROOT_PATH. CDN_PATH.'/'. $sthumb;
    $img_mthumb = ROOT_PATH. CDN_PATH.'/'. $mthumb;
    /* Xóa phiên bản webp trước hình thường, vì còn convert tên */
    $webp_thumb  = convertExtension($img_thumb, 'webp');
    $webp_sthumb = convertExtension($img_sthumb, 'webp');
    $webp_mthumb = convertExtension($img_mthumb, 'webp');
    @unlink($webp_thumb); @unlink($webp_sthumb); @unlink($webp_mthumb);
    @unlink($img_thumb); @unlink($img_sthumb); @unlink($img_mthumb);
}


?>