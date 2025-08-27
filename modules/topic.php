<?php
if ((DEBUG_MODE & 2) != 2 && $ecsvn_iscached == true )
{
    $smarty->caching = true;
}

if(!empty($slug)){
    $id  = $db->getOne("SELECT id FROM " . $ecs->table('slug') ." WHERE slug = '".$slug."' AND module='topic'");
    $topic_id  = $id > 0 ? intval($id) : 0;
}else{$topic_id=0;}

/* Topic Index */

/** Nếu ko tồn tại đẩy về Trang chủ khuyến mãi */
if(empty($topic_id)){
    $cache_id = sprintf('%X', crc32($_CFG['lang']));
    $_template = 'topic_index.dwt';

    if (!$smarty->is_cached($_template, $cache_id))
    {
        assign_template();
        $position = assign_ur_here('', 'Chuyên trang khuyến mãi');
        $smarty->assign('page_title',      $position['title']);
        $smarty->assign('ur_here',         $position['ur_here']);
        //$smarty->assign('categories',      get_categories_tree());
      /**
       * Lấy ra danh sách các chương trình KM, còn và ko còn thời hạn
       * Có ngày bắt đầu kết thúc, kết thúc sẽ bị disnable ko kích link được
       */
       $sql = "SELECT t.topic_id, s.slug, t.title, t.description, t.start_time, t.end_time, t.title_pic ".
       " FROM " . $ecs->table('topic') . " as t LEFT JOIN ".$GLOBALS['ecs']->table('slug') . ' AS s '.
       " ON t.topic_id = s.id WHERE s.module = 'topic' ORDER BY t.topic_id DESC  LIMIT 20";
       $res = $db->getAll($sql);

       $topic = array();
       foreach($res as $key => $row){
           $topic[$key]['title'] = $row['title'];
           $topic[$key]['url'] = !empty($row['remote_url']) ? $row['remote_url'] : 'khuyen-mai/'.$row['slug'].'.html';
           $topic[$key]['desc'] = $row['description'];
           $topic[$key]['thumb'] = $row['title_pic'];
           $topic[$key]['start_time']    = date('d-m-Y', $row['start_time']);
           $topic[$key]['end_time']    = date('d-m-Y', $row['end_time']);
           $topic[$key]['active']  = (time() >=  $row['start_time'] && time() <= $row['end_time']) ? 1: 0;
       }

       $smarty->assign('topics', $topic);

    }
     $smarty->assign('helps',            get_shop_help());
    $smarty->display($_template, $cache_id);
    exit;
}

/* Topic Detail */

$sql = "SELECT template FROM " . $ecs->table('topic') ." WHERE topic_id = '$topic_id'";
$topic_template = $db->getOne($sql);


$templates = empty($topic_template) ? 'topic.dwt' : $topic_template;

$cache_id = sprintf('%X', crc32($_SESSION['user_rank'] . '-' . $_CFG['lang'] . '-' . $topic_id));

if (!$smarty->is_cached($templates, $cache_id))
{
    $sql = "SELECT * FROM " . $ecs->table('topic') . " WHERE topic_id = '$topic_id'";

    $topic = $db->getRow($sql);
    $topic['data'] = addcslashes($topic['data'], "'");
    $tmp = @unserialize($topic["data"]);
    $arr = (array)$tmp;

    $topic['active']  = (time() >=  $topic['start_time'] && time() <= $topic['end_time']) ? 1: 0;
    $topic['start_time']  = date('d-m-Y', $topic['start_time']);
    $topic['end_time']    = date('d-m-Y', $topic['end_time']);

    $goods_id = array();

    foreach ($arr AS $key=>$value)
    {
        foreach($value AS $k => $val)
        {
            $opt = explode('|', $val);
            $arr[$key][$k] = $opt[1];
            $goods_id[] = $opt[1];
        }
    }

    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, " .
                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
                'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
                'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                "WHERE " . db_create_in($goods_id, 'g.goods_id');

    $res = $GLOBALS['db']->query($sql);

    $sort_goods_arr = array();

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $row['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
        }
        else
        {
            $row['promote_price'] = '';
        }

        if ($row['shop_price'] > 0)
        {
            $row['shop_price'] =  price_format($row['shop_price']);
        }
        else
        {
            $row['shop_price'] = '';
        }

        $row['url']              = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
        $row['goods_style_name'] = add_style($row['goods_name'], $row['goods_name_style']);
        $row['short_name']       = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                                    sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $row['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $row['short_style_name'] = add_style($row['short_name'], $row['goods_name_style']);

        foreach ($arr AS $key => $value)
        {
            foreach ($value AS $val)
            {
                if ($val == $row['goods_id'])
                {
                    $key = $key == 'default' ? $_LANG['all_goods'] : $key;
                    $sort_goods_arr[$key][] = $row;
                }
            }
        }
    }

    /* 模板赋值 */
    assign_template();
    $position = assign_ur_here(0, $topic['title'],'topic');
    $smarty->assign('page_title',       $position['title']);       // 页面标题
    $smarty->assign('ur_here',          $position['ur_here'] );     // 当前位置
    $smarty->assign('show_marketprice', $_CFG['show_marketprice']);
    $smarty->assign('sort_goods_arr',   $sort_goods_arr);          // 商品列表
    $smarty->assign('topic',            $topic);                   // 专题信息
    $smarty->assign('keywords',         $topic['keywords']);       // 专题信息
    $smarty->assign('description',      $topic['description']);    // 专题信息
    $smarty->assign('title_pic',        $topic['title_pic']);      // 分类标题图片地址
    $smarty->assign('base_style',       '#' . $topic['base_style']);     // 基本风格样式颜色
    $smarty->assign('helps',            get_shop_help());
    $template_file = empty($topic['template']) ? 'topic.dwt' : $topic['template'];

    $db->query('UPDATE ' . $ecs->table('topic') . " SET click_count = click_count + 1 WHERE topic_id = '$topic_id'");
}
/* 显示模板 */
$smarty->display($templates, $cache_id);

?>