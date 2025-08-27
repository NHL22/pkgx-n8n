<?php
/**
 * 10 tin xem nhieu nhat
 *
 * @access  public
 * @param   integer     $cat_id
 *
 * @return  array
 */
function siy_topview_article($cat_id)
{
    $num = get_library_number("topview_article");
    $limit = $num > 0 ? $num : 5;
    $cat_str = get_article_children($cat_id);
    $sql = 'SELECT article_id, title, description, article_sthumb, article_mthumb, author, add_time, click_count ' .
               ' FROM ' .$GLOBALS['ecs']->table('article') .
               ' WHERE is_open = 1 AND ' . $cat_str .
               " ORDER BY click_count DESC LIMIT $limit";

    $res = $GLOBALS['db']->getAll($sql);

    $arr = array();
    if ($res)
    {
        foreach ($res AS $row)
        {
            $article_id = $row['article_id'];

            $arr[$article_id]['id']          = $article_id;
            $arr[$article_id]['title']       = $row['title'];
            $arr[$article_id]['desc']        = $row['short_desc'];
            $arr[$article_id]['viewed']      = $row['click_count'];

            $arr[$article_id]['thumb']       = !empty($row['article_sthumb'])? $row['article_sthumb'] :'images/no_picture.gif';
            $arr[$article_id]['mthumb']      = !empty($row['article_mthumb'])? $row['article_mthumb'] :'images/no_picture.gif';

            $arr[$article_id]['author']      = empty($row['author']) || $row['author'] == '_SHOPHELP' ? $GLOBALS['_CFG']['shop_name'] : $row['author'];
            $arr[$article_id]['url']         =  build_uri('article', array('aid'=>$article_id), $row['title']);
            $arr[$article_id]['add_time']    = timeAgo(local_date($GLOBALS['_CFG']['time_format'], $row['add_time']));
        }
    }

    return $arr;
}
 ?>