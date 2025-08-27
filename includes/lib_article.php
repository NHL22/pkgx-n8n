<?php

/**
 * ECSHOP 文章及文章分类相关函数库
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: lib_article.php 17217 2011-01-19 06:29:08Z liubo $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * Lấy tin có lượt xem nhiều theo danh mục
 */

function getTopViewArticles($cat_id, $num = 10){

    $limit = $num > 0 ? $num : 10;
    $cat_str = get_article_children($cat_id);
    $sql = 'SELECT article_id, title, description, article_sthumb, article_mthumb, author, add_time, click_count ' .
                   ' FROM ' .$GLOBALS['ecs']->table('article') .
                   ' WHERE is_open = 1 AND cat_id > 3 AND ' . $cat_str.
                   " ORDER BY click_count DESC LIMIT $limit ";

    $res = $GLOBALS['db']->getAll($sql);

    $arr = [];
    foreach ($res as $key => $row) {

        $arr[$key]['url']         = build_uri('article', ['aid'=> $row['article_id']]);
        $arr[$key]['id']          = $row['article_id'];
        $arr[$key]['title']       = $row['title'];
        $arr[$key]['viewed']      = $row['click_count'];
        $arr[$key]['thumb']       = !empty($row['article_sthumb'])? $row['article_sthumb'] :'images/no_picture.gif';
        $arr[$key]['mthumb']      = !empty($row['article_mthumb'])? $row['article_mthumb'] :'images/no_picture.gif';
        $arr[$key]['author']      = empty($row['author']) || $row['author'] == '_SHOPHELP' ? $GLOBALS['_CFG']['shop_name'] : $row['author'];
        $arr[$key]['add_time']    = timeAgo(local_date($GLOBALS['_CFG']['time_format'], $row['add_time']));

        /* webp cho pc */
        $thumb_webp = convertExtension($row['article_sthumb'], 'webp');
        if(file_exists(ROOT_PATH.CDN_PATH.'/'.$thumb_webp)){
            $arr[$key]['thumb_webp'] = $thumb_webp;
        }else{
            $arr[$key]['thumb_webp'] = '';
        }

        /* webp cho pc */
        $mthumb_webp = convertExtension($row['article_mthumb'], 'webp');
        if(file_exists(ROOT_PATH.CDN_PATH.'/'.$mthumb_webp)){
            $arr[$key]['mthumb_webp'] = $mthumb_webp;
        }else{
            $arr[$key]['mthumb_webp'] = '';
        }

    }
    return $arr;
}


/**
 * Lấy tin vừa lên
 *
 * @access  private
 * @return  array
 */
function index_get_new_articles($article_id = 0)
{
    $where = $article_id > 0 ? " AND a.article_id <> $article_id " : '';
    $num = get_library_number("new_articles");
    $limit = $num > 0 ? $num : $GLOBALS['_CFG']['article_number'];
    $sql = 'SELECT a.article_id, a.title, a.add_time, a.article_thumb, a.article_sthumb, a.click_count  ' .
            ' FROM ' . $GLOBALS['ecs']->table('article') . ' AS a, ' .
                $GLOBALS['ecs']->table('article_cat') . ' AS ac' .
            ' WHERE a.is_open = 1 '.$where.' AND a.cat_id = ac.cat_id AND a.cat_id > 3 AND ac.cat_type = 1' .
            ' ORDER BY a.article_type DESC, a.add_time DESC LIMIT ' . $limit;
    $res = $GLOBALS['db']->getAll($sql);

    $arr = array();
    foreach ($res AS $idx => $row)
    {
        $arr[$idx]['id']          = $row['article_id'];
        $arr[$idx]['title']       = $row['title'];
        $arr[$idx]['add_time']    = timeAgo(local_date($GLOBALS['_CFG']['time_format'], $row['add_time']));
        $arr[$idx]['url']         = build_uri('article', array('aid' => $row['article_id']), $row['title']);
        $arr[$idx]['viewed']      = $row['click_count'];
        $arr[$idx]['thumb']      = (empty($row['article_thumb'])) ? $GLOBALS['_CFG']['no_picture'] : $row['article_thumb'];
        $arr[$idx]['sthumb']       = (empty($row['article_sthumb'])) ? $GLOBALS['_CFG']['no_picture'] : $row['article_sthumb'];

        /* webp cho pc */
        $thumb_webp = convertExtension($row['article_thumb'], 'webp');
        if(file_exists(ROOT_PATH.CDN_PATH.'/'.$thumb_webp)){
            $arr[$idx]['thumb_webp'] = $thumb_webp;
        }else{
            $arr[$idx]['thumb_webp'] = '';
        }

        $sthumb_webp = convertExtension($row['article_sthumb'], 'webp');
        if(file_exists(ROOT_PATH.CDN_PATH.'/'.$sthumb_webp)){
            $arr[$idx]['sthumb_webp'] = $sthumb_webp;
        }else{
            $arr[$idx]['sthumb_webp'] = '';
        }

    }

    return $arr;
}

/**
 * Lấy danh sách tin tức theo ID danh mục tin
 *
 * @access  public
 * @param   integer     $cat_id
 * @param   integer     $page
 * @param   integer     $size
 *
 * @return  array
 */
function get_cat_articles($cat_id, $page = 1, $size = 20 ,$requirement='')
{
    //取出所有非0的文章
    if ($cat_id == '-1')
    {
        $cat_str = 'cat_id > 0';
    }
    else
    {
        $cat_str = get_article_children($cat_id);
    }
    //增加搜索条件，如果有搜索内容就进行搜索
    if ($requirement != '')
    {
        $sql = 'SELECT article_id, title, author, add_time, description, article_thumb, article_sthumb, click_count ' .
               ' FROM ' .$GLOBALS['ecs']->table('article') .
               ' WHERE is_open = 1 AND title like \'%' . $requirement . '%\' ' .
               ' ORDER BY  article_id DESC';
    }
    else
    {

        $sql = 'SELECT article_id, title, author, add_time, description, article_thumb, article_sthumb, click_count ' .
               ' FROM ' .$GLOBALS['ecs']->table('article') .
               ' WHERE is_open = 1 AND ' . $cat_str .
               ' ORDER BY  article_id DESC';
    }

    $res = $GLOBALS['db']->selectLimit($sql, $size, ($page-1) * $size);

    $arr = array();
    if ($res)
    {
        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            $article_id = $row['article_id'];
            $arr[$article_id]['id']          = $article_id;
            $arr[$article_id]['title']       = $row['title'];
            $arr[$article_id]['author']      = empty($row['author']) || $row['author'] == '_SHOPHELP' ? $GLOBALS['_CFG']['shop_name'] : $row['author'];
            $arr[$article_id]['url']         = build_uri('article', array('aid'=>$article_id), $row['title']);
            $arr[$article_id]['add_time']    = timeAgo(local_date($GLOBALS['_CFG']['time_format'], $row['add_time']));
            $arr[$article_id]['viewed']      = $row['click_count'];
            $arr[$article_id]['desc']        = $row['description'];
            $arr[$article_id]['thumb']      = (empty($row['article_thumb'])) ? $GLOBALS['_CFG']['no_picture'] : $row['article_thumb'];
            $arr[$article_id]['sthumb']       = (empty($row['article_sthumb'])) ? $GLOBALS['_CFG']['no_picture'] : $row['article_sthumb'];

            /* webp cho pc */
            $thumb_webp = convertExtension($row['article_thumb'], 'webp');
            if(file_exists(ROOT_PATH.CDN_PATH.'/'.$thumb_webp)){
                $arr[$article_id]['thumb_webp'] = $thumb_webp;
            }else{
               $arr[$article_id]['thumb_webp'] = '';
            }

            $sthumb_webp = convertExtension($row['article_sthumb'], 'webp');
            if(file_exists(ROOT_PATH.CDN_PATH.'/'.$sthumb_webp)){
                $arr[$article_id]['sthumb_webp'] = $sthumb_webp;
            }else{
                $arr[$article_id]['sthumb_webp'] = '';
            }
        }
    }

    return $arr;
}

/**
 * 获得指定分类下的文章总数
 *
 * @param   integer     $cat_id
 *
 * @return  integer
 */
function get_article_count($cat_id ,$requirement='')
{
    global $db, $ecs;
    if ($requirement != '')
    {
        $count = $db->getOne('SELECT COUNT(*) FROM ' . $ecs->table('article') . ' WHERE ' . get_article_children($cat_id) . ' AND  title like \'%' . $requirement . '%\'  AND is_open = 1');
    }
    else
    {
        $count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table('article') . " WHERE " . get_article_children($cat_id) . " AND is_open = 1");
    }
    return $count;
}

?>
