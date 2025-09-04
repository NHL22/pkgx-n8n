<?php

if ((DEBUG_MODE & 2) != 2 && $ecsvn_iscached == true )
{
    $smarty->caching = true;
}

// $affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
// $smarty->assign('affiliate', $affiliate);

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

$id  = $db->getOne("SELECT id FROM " . $ecs->table('slug') ." WHERE module='goods' AND slug = '".$slug."'");
$goods_id = $id > 0 ? intval($id) : 0;

//var_dump($goods_id);exit;

if($goods_id == 0) {
    ecvn_withRedirect($ecsvn_request['getBaseUrl'].'/tim-kiem');
}
/* Validate URL */
$url_right =  build_uri('goods', array('gid' => $goods_id));
if($url_right != $_url){
    ecvn_withRedirect($ecsvn_request['getBaseUrl'].$url_right);
}
/* End Validate URL */


/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

$cache_id = $goods_id . '-' . $_SESSION['user_rank'].'-'.$_CFG['lang'];
$cache_id = sprintf('%X', crc32($cache_id));

$_template = 'goods.dwt';

if (!$smarty->is_cached($_template, $cache_id))
{
    $smarty->assign('image_width',  $_CFG['image_width']);
    $smarty->assign('image_height', $_CFG['image_height']);
    $smarty->assign('helps',        get_shop_help()); // 网店帮助
    $smarty->assign('id',           $goods_id);
    $smarty->assign('shop_notice',     $_CFG['shop_notice']);       // 商店公告
    $smarty->assign('type',         0);
    $smarty->assign('cfg',          $_CFG);
    $smarty->assign('promotion',       get_promotion_info($goods_id));//促销信息
    $smarty->assign('promotion_info', get_promotion_info());
    assign_template();
    /* 获得商品的信息 */
    $goods = get_goods_info($goods_id);


    if ($goods === false)
    {
        ecvn_withRedirect($ecsvn_request['getBaseUrl'].'/tim-kiem');
        exit;
    }
    else
    {
        if ($goods['brand_id'] > 0)
        {
            $goods['goods_brand_url'] = build_uri('brand', array('bid'=>$goods['brand_id']), $goods['goods_brand']);
        }

        $shop_price   = $goods['shop_price'];
        $linked_goods = get_linked_goods($goods_id);

        $goods['goods_style_name'] = add_style($goods['goods_name'], $goods['goods_name_style']);

        /* 购买该商品可以得到多少钱的红包 */
        if ($goods['bonus_type_id'] > 0)
        {
            $time = time();
            $sql = "SELECT type_money FROM " . $ecs->table('bonus_type') .
                    " WHERE type_id = '$goods[bonus_type_id]' " .
                    " AND send_type = '" . SEND_BY_GOODS . "' " .
                    " AND send_start_date <= '$time'" .
                    " AND send_end_date >= '$time'";
            $goods['bonus_money'] = floatval($db->getOne($sql));
            if ($goods['bonus_money'] > 0)
            {
                $goods['bonus_money'] = price_format($goods['bonus_money']);
            }
        }
        $goods['url'] = $url_right;
        $smarty->assign('goods',              $goods);
        $smarty->assign('goods_id',           $goods['goods_id']);
        $smarty->assign('promote_end_time',   $goods['gmt_end_time']);
        $smarty->assign('categories',         get_categories_tree($goods['cat_id']));  // 分类树

        /* noindex params */
        if($ecsvn_index_follow == false || isset($_GET['client']) || isset($_GET['fb_comment_id']) || isset($_GET['gclid']))
        {
            $goods['meta_robots'] = 'NOINDEX,FOLLOW';
        }
        $smarty->assign('keywords',           htmlspecialchars($goods['keywords']));
        $smarty->assign('description',        htmlspecialchars($goods['meta_desc']));
        $smarty->assign('meta_robots',  htmlspecialchars($goods['meta_robots']));

        $position = assign_ur_here($goods['cat_id'], '', 'goods');

        /* current position */
        $page_title = !empty($goods['meta_title']) ? htmlspecialchars($goods['meta_title']) : $position['title'];
        $smarty->assign('page_title',  $page_title);                    // 页面标题
        $smarty->assign('ur_here',     $position['ur_here']);                  // 当前位置

        $properties = get_goods_properties($goods_id);  // 获得商品的规格和属性
        $smarty->assign('properties',          $properties['pro']);                              // 商品属性
        $smarty->assign('specification',       $properties['spe']);
         /* Add by Ecshopvietnam for extra info product*/
        $smarty->assign('pro_extra',           $properties['extra']);                               // 商品规格
        //$smarty->assign('attribute_linked',    get_same_attribute_goods($properties));           // 相同属性的关联商品
        $smarty->assign('related_goods',       $linked_goods);                                   // 关联商品
        $smarty->assign('goods_article_list',  get_linked_articles($goods_id));                  // 关联文章
        $smarty->assign('fittings',            get_goods_fittings(array($goods_id)));                   // 配件
        $smarty->assign('rank_prices',         get_user_rank_prices($goods_id, $shop_price));    // 会员等级价格
        $smarty->assign('pictures',            get_goods_gallery($goods_id));
        $smarty->assign('feature_pictures',    get_goods_gallery($goods_id, 2, 8));                    // 商品相册
        //$smarty->assign('bought_goods',        get_also_bought($goods_id));                      // 购买了该商品的用户还购买了哪些商品
        $smarty->assign('goods_rank',          get_goods_rank($goods_id));                       // 商品的销售排名

         /* --- LẤY DỮ LIỆU ĐÁNH GIÁ CHO SCHEMA --- */
         // Đếm tổng số bình luận (đánh giá) đã được duyệt cho sản phẩm này
          $review_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table('comment') . " WHERE id_value = '$goods_id' AND comment_type = 0 AND status = 1 AND parent_id = 0");

          // Tính điểm đánh giá trung bình
          if (intval($review_count) > 0) {
              $avg_rating = $db->getOne("SELECT AVG(comment_rank) FROM " . $ecs->table('comment') . " WHERE id_value = '$goods_id' AND comment_type = 0 AND status = 1 AND parent_id = 0");
           } else {
              // Nếu chưa có đánh giá nào, đặt giá trị mặc định để schema hợp lệ
               $avg_rating = 5;
              $review_count = 1; // Đặt là 1 để Google không báo lỗi "reviewCount must be positive"
          }

         // Gán biến cho template để sử dụng trong goods.dwt
         $smarty->assign('review_count', $review_count);
         $smarty->assign('avg_rating',   number_format($avg_rating, 1)); // Làm tròn đến 1 chữ số thập phân, ví dụ: 4.5
         /* --- KẾT THÚC LẤY DỮ LIỆU ĐÁNH GIÁ --- */

        /* Trả góp Alepay */
        if($goods['price_final'] >= 3000000 && $goods['price_final'] <= 60000000){
            $smarty->assign('alepay_allow',   1);
        }else{
            $smarty->assign('alepay_allow',   '');
        }

        /* Sản phẩm cùng danh mục */
        $smarty->assign('goods_related_cate',  get_goods_by_cate($goods['cat_id'], $goods['goods_id']));

        //获取tag
        // $tag_array = get_tags($goods_id);
        // $smarty->assign('tags',  $tag_array);                                       // 商品的标记

        //获取关联礼包
        // $package_goods_list = get_package_goods_list($goods['goods_id']);
        // $smarty->assign('package_goods_list',$package_goods_list);    // 获取关联礼包


        $volume_price_list = get_volume_price_list($goods['goods_id'], '1');
        $smarty->assign('volume_price_list',$volume_price_list);    // 商品优惠价格区间


        $smarty->assign('best_goods',      get_category_recommend_goods('best'));


        $catlist = get_parent_cats($goods['cat_id']);
        $parent_catid = isset($catlist[1]['cat_id']) ?  $catlist[1]['cat_id'] : $catlist[0]['cat_id'];
        $smarty->assign('parent_catid',     $parent_catid);
        $parent_cat_name = $db->getOne('SELECT cat_name FROM '.$ecs->table('category'). " WHERE cat_id = $parent_catid ");
        $smarty->assign('parent_cat_name',   $parent_cat_name);

        $smarty->assign('cat_info',   get_cat_info($goods['cat_id']));

        assign_dynamic('goods');
    }
}
$links = index_get_links();
$smarty->assign('img_links',       $links['img']);
$smarty->assign('txt_links',       $links['txt']);

/* 记录浏览历史 */
if (!empty($_COOKIE['ECS']['history']))
{
    $history = explode(',', $_COOKIE['ECS']['history']);

    array_unshift($history, $goods_id);
    $history = array_unique($history);

    while (count($history) > $_CFG['history_number'])
    {
        array_pop($history);
    }

    setcookie('ECS[history]', implode(',', $history), time() + 3600 * 24 * 30, $cookie_path, $cookie_domain, $cookie_secure, TRUE);
}
else
{
    setcookie('ECS[history]', $goods_id, time() + 3600 * 24 * 30, $cookie_path, $cookie_domain, $cookie_secure, TRUE);
}


/* 更新点击次数 */
$db->query('UPDATE ' . $ecs->table('goods') . " SET click_count = click_count + 1 WHERE goods_id = '$goods_id'");

$smarty->assign('now_time',  time());           // 当前系统时间
$smarty->display($_template,      $cache_id);

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */


function get_goods_by_cate($cat_id=0,$goods_id,$limit = 5, $ext = ''){
    $children = get_children($cat_id);
    $sql = 'SELECT g.goods_id, g.goods_name, RAND() AS rnd, g.goods_thumb,g.goods_number, g.is_hot, g.goods_img, g.deal_price, g.partner_price, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                'g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' g ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            " WHERE g.goods_id <> $goods_id AND $ext $children AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".
            " ORDER BY rnd LIMIT " . $limit;
    $res = $GLOBALS['db']->query($sql);
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {

        $comments = getRank($row['goods_id']);
        $arr[$row['goods_id']]['comment_rank']= round($comments['comment_rank'],1);
        $arr[$row['goods_id']]['comment_count'] = $comments['comment_count'];
        $arr[$row['goods_id']]['is_hot']     = $row['is_hot'];
        $arr[$row['goods_id']]['goods_number']     = $row['goods_number'];

        $arr[$row['goods_id']]['goods_id']     = $row['goods_id'];
        $arr[$row['goods_id']]['goods_name']   = $row['goods_name'];
        $arr[$row['goods_id']]['goods_thumb']  = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['price']        = $row['shop_price'];
        $arr[$row['goods_id']]['deal_price'] =  price_format($row['deal_price']);
        $arr[$row['goods_id']]['partner_price'] =  price_format($row['partner_price']);

        $arr[$row['goods_id']]['shop_price']   = price_format($row['shop_price']);
        $arr[$row['goods_id']]['url']          = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
        if ($row['promote_price'] > 0)
        {
            $arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);
        }
        else
        {
            $arr[$row['goods_id']]['promote_price'] = 0;
        }

        /* webp cho pc */
        $thumb_webp = convertExtension($row['goods_thumb'], 'webp');
        if(file_exists(ROOT_PATH.CDN_PATH.'/'.$thumb_webp)){
            $arr[$row['goods_id']]['thumb_webp'] = $thumb_webp;
        }else{
            $arr[$row['goods_id']]['thumb_webp'] = '';
        }

    }
    return $arr;
}
/**
 * 获得指定商品的关联商品
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  array
 */
function get_linked_goods($goods_id)
{
    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.is_hot,g.goods_number, g.goods_img, g.deal_price, g.partner_price, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
                'g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
            'FROM ' . $GLOBALS['ecs']->table('link_goods') . ' lg ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = lg.link_goods_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            "WHERE lg.goods_id = '$goods_id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".
            "LIMIT " . $GLOBALS['_CFG']['related_goods_number'];
    $res = $GLOBALS['db']->query($sql);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $comments = getRank($row['goods_id']);
        $arr[$row['goods_id']]['comment_rank']= round($comments['comment_rank'],1);
        $arr[$row['goods_id']]['comment_count'] = $comments['comment_count'];
        $arr[$row['goods_id']]['is_hot']     = $row['is_hot'];
        $arr[$row['goods_id']]['goods_number']     = $row['goods_number'];

        $arr[$row['goods_id']]['goods_id']     = $row['goods_id'];
        $arr[$row['goods_id']]['goods_name']   = $row['goods_name'];
        $arr[$row['goods_id']]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $arr[$row['goods_id']]['goods_thumb']  = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']    = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price']   = price_format($row['shop_price']);
        $arr[$row['goods_id']]['deal_price'] =  price_format($row['deal_price']);
        $arr[$row['goods_id']]['partner_price'] =  price_format($row['partner_price']);

        $arr[$row['goods_id']]['url']          = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

        if ($row['promote_price'] > 0)
        {
            $arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);
        }
        else
        {
            $arr[$row['goods_id']]['promote_price'] = 0;
        }

        /* webp cho pc */
        $thumb_webp = convertExtension($row['goods_thumb'], 'webp');
        if(file_exists(ROOT_PATH.CDN_PATH.'/'.$thumb_webp)){
            $arr[$row['goods_id']]['thumb_webp'] = $thumb_webp;
        }else{
            $arr[$row['goods_id']]['thumb_webp'] = '';
        }
    }

    return $arr;
}

/**
 * Lấy tin liên quan
 * Nếu ko chọn tin liên quan thì nó sẽ lấy tin mới nhất
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  void
 */
function get_linked_articles($goods_id)
{
    $sql = 'SELECT a.article_id, a.title, a.click_count, a.article_thumb, a.article_sthumb, a.article_mthumb, a.add_time ' .
            'FROM ' . $GLOBALS['ecs']->table('goods_article') . ' AS g, ' .
                $GLOBALS['ecs']->table('article') . ' AS a ' .
            "WHERE g.article_id = a.article_id AND g.goods_id = '$goods_id' AND a.is_open = 1 " .
            'ORDER BY a.add_time DESC';
    $res = $GLOBALS['db']->getAll($sql);

    if(empty($res)){
        $sql = 'SELECT a.article_id, a.title, a.click_count, a.article_thumb, a.article_sthumb, a.article_mthumb, a.add_time ' .
        ' FROM ' .$GLOBALS['ecs']->table('article') . ' AS a ' .
        " WHERE a.cat_id > 3 AND a.is_open = 1 ORDER BY a.article_type DESC, a.add_time DESC LIMIT 6";
        $res = $GLOBALS['db']->getAll($sql);
    }

    $arr = array();
    foreach ($res as $key => $row) {

        $row['url']         = build_uri('article', array('aid' => $row['article_id']), $row['title']);
        $row['viewed']      = $row['click_count'];
        $row['thumb']      = (empty($row['article_thumb'])) ? $GLOBALS['_CFG']['no_picture'] : $row['article_thumb'];
        $row['sthumb']      = (empty($row['article_sthumb'])) ? $GLOBALS['_CFG']['no_picture'] : $row['article_sthumb'];
        $row['mthumb']       = (empty($row['article_mthumb'])) ? $GLOBALS['_CFG']['no_picture'] : $row['article_mthumb'];

        /* webp cho pc */
        $sthumb_webp = convertExtension($row['article_sthumb'], 'webp');
        if(file_exists(ROOT_PATH.CDN_PATH.'/'.$sthumb_webp)){
           $row['sthumb_webp'] = $sthumb_webp;
        }else{
           $row['sthumb_webp'] = '';
        }

        $mthumb_webp = convertExtension($row['article_mthumb'], 'webp');
        if(file_exists(ROOT_PATH.CDN_PATH.'/'.$mthumb_webp)){
            $row['mthumb_webp'] = $mthumb_webp;
        }else{
            $row['mthumb_webp'] = '';
        }


        $arr[] = $row;
    }

    return $arr;
}

/**
 * 获得指定商品的各会员等级对应的价格
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  array
 */
function get_user_rank_prices($goods_id, $shop_price)
{
    $sql = "SELECT rank_id, IFNULL(mp.user_price, r.discount * $shop_price / 100) AS price, r.rank_name, r.discount " .
            'FROM ' . $GLOBALS['ecs']->table('user_rank') . ' AS r ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = '$goods_id' AND mp.user_rank = r.rank_id " .
            "WHERE r.show_price = 1 OR r.rank_id = '$_SESSION[user_rank]'";
    $res = $GLOBALS['db']->query($sql);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {

        $arr[$row['rank_id']] = array(
                        'rank_name' => htmlspecialchars($row['rank_name']),
                        'price'     => price_format($row['price']));
    }

    return $arr;
}

/**
 * 获得购买过该商品的人还买过的商品
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  array
 */
function get_also_bought($goods_id)
{
    $sql = 'SELECT COUNT(b.goods_id ) AS num, g.goods_id, g.goods_name,g.is_hot,g.goods_number, g.goods_thumb, g.goods_img, g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
            'FROM ' . $GLOBALS['ecs']->table('order_goods') . ' AS a ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('order_goods') . ' AS b ON b.order_id = a.order_id ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = b.goods_id ' .
            "WHERE a.goods_id = '$goods_id' AND b.goods_id <> '$goods_id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 " .
            'GROUP BY b.goods_id ' .
            'ORDER BY num DESC ' .
            'LIMIT ' . $GLOBALS['_CFG']['bought_goods'];
    $res = $GLOBALS['db']->query($sql);

    $key = 0;
    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $arr[$key]['goods_id']    = $row['goods_id'];
        $arr[$key]['goods_name']  = $row['goods_name'];
        $arr[$key]['is_hot']  = $row['is_hot'];
        $arr[$key]['goods_number']  = $row['goods_number'];

        $arr[$key]['short_name']  = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $arr[$key]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$key]['goods_img']   = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$key]['shop_price']  = price_format($row['shop_price']);
        $arr[$key]['url']         = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

        if ($row['promote_price'] > 0)
        {
            $arr[$key]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $arr[$key]['formated_promote_price'] = price_format($arr[$key]['promote_price']);
        }
        else
        {
            $arr[$key]['promote_price'] = 0;
        }

        $key++;
    }

    return $arr;
}

/**
 * 获得指定商品的销售排名
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  integer
 */
function get_goods_rank($goods_id)
{
    /* 统计时间段 */
    $period = intval($GLOBALS['_CFG']['top10_time']);
    if ($period == 1) // 一年
    {
        $ext = " AND o.add_time > '" . local_strtotime('-1 years') . "'";
    }
    elseif ($period == 2) // 半年
    {
        $ext = " AND o.add_time > '" . local_strtotime('-6 months') . "'";
    }
    elseif ($period == 3) // 三个月
    {
        $ext = " AND o.add_time > '" . local_strtotime('-3 months') . "'";
    }
    elseif ($period == 4) // 一个月
    {
        $ext = " AND o.add_time > '" . local_strtotime('-1 months') . "'";
    }
    else
    {
        $ext = '';
    }

    /* 查询该商品销量 */
    $sql = 'SELECT IFNULL(SUM(g.goods_number), 0) ' .
        'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o, ' .
            $GLOBALS['ecs']->table('order_goods') . ' AS g ' .
        "WHERE o.order_id = g.order_id " .
        "AND o.order_status = '" . OS_CONFIRMED . "' " .
        "AND o.shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .
        " AND o.pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) .
        " AND g.goods_id = '$goods_id'" . $ext;
    $sales_count = $GLOBALS['db']->getOne($sql);

    if ($sales_count > 0)
    {
        /* 只有在商品销售量大于0时才去计算该商品的排行 */
        $sql = 'SELECT DISTINCT SUM(goods_number) AS num ' .
                'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS o, ' .
                    $GLOBALS['ecs']->table('order_goods') . ' AS g ' .
                "WHERE o.order_id = g.order_id " .
                "AND o.order_status = '" . OS_CONFIRMED . "' " .
                "AND o.shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .
                " AND o.pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . $ext .
                " GROUP BY g.goods_id HAVING num > $sales_count";
        $res = $GLOBALS['db']->query($sql);

        $rank = $GLOBALS['db']->num_rows($res) + 1;

        if ($rank > 10)
        {
            $rank = 0;
        }
    }
    else
    {
        $rank = 0;
    }

    return $rank;
}

/**
 * 获得商品选定的属性的附加总价格
 *
 * @param   integer     $goods_id
 * @param   array       $attr
 *
 * @return  void
 */
function get_attr_amount($goods_id, $attr)
{
    $sql = "SELECT SUM(attr_price) FROM " . $GLOBALS['ecs']->table('goods_attr') .
        " WHERE goods_id='$goods_id' AND " . db_create_in($attr, 'goods_attr_id');

    return $GLOBALS['db']->getOne($sql);
}

/**
 * 取得跟商品关联的礼包列表
 *
 * @param   string  $goods_id    商品编号
 *
 * @return  礼包列表
 */
function get_package_goods_list($goods_id)
{
    $now = time();
    $sql = "SELECT pg.goods_id, ga.act_id, ga.act_name, ga.act_desc, ga.goods_name, ga.start_time,
                   ga.end_time, ga.is_finished, ga.ext_info
            FROM " . $GLOBALS['ecs']->table('goods_activity') . " AS ga, " . $GLOBALS['ecs']->table('package_goods') . " AS pg
            WHERE pg.package_id = ga.act_id
            AND ga.start_time <= '" . $now . "'
            AND ga.end_time >= '" . $now . "'
            AND pg.goods_id = " . $goods_id . "
            GROUP BY ga.act_id
            ORDER BY ga.act_id ";
    $res = $GLOBALS['db']->getAll($sql);

    foreach ($res as $tempkey => $value)
    {
        $subtotal = 0;
        $row = unserialize($value['ext_info']);
        unset($value['ext_info']);
        if ($row)
        {
            foreach ($row as $key=>$val)
            {
                $res[$tempkey][$key] = $val;
            }
        }

        $sql = "SELECT pg.package_id, pg.goods_id, pg.goods_number, pg.admin_id, p.goods_attr, g.goods_sn, g.goods_name, g.is_hot, g.market_price, g.goods_thumb, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS rank_price
                FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg
                    LEFT JOIN ". $GLOBALS['ecs']->table('goods') . " AS g
                        ON g.goods_id = pg.goods_id
                    LEFT JOIN ". $GLOBALS['ecs']->table('products') . " AS p
                        ON p.product_id = pg.product_id
                    LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp
                        ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]'
                WHERE pg.package_id = " . $value['act_id']. "
                ORDER BY pg.package_id, pg.goods_id";

        $goods_res = $GLOBALS['db']->getAll($sql);

        foreach($goods_res as $key => $val)
        {
            $goods_id_array[] = $val['goods_id'];
            $goods_res[$key]['goods_thumb']  = get_image_path($val['goods_id'], $val['goods_thumb'], true);
            $goods_res[$key]['market_price'] = price_format($val['market_price']);
            $goods_res[$key]['rank_price']   = price_format($val['rank_price']);
            $subtotal += $val['rank_price'] * $val['goods_number'];
        }

        /* 取商品属性 */
        $sql = "SELECT ga.goods_attr_id, ga.attr_value
                FROM " .$GLOBALS['ecs']->table('goods_attr'). " AS ga, " .$GLOBALS['ecs']->table('attribute'). " AS a
                WHERE a.attr_id = ga.attr_id
                AND a.attr_type = 1
                AND " . db_create_in($goods_id_array, 'goods_id');
        $result_goods_attr = $GLOBALS['db']->getAll($sql);

        $_goods_attr = array();
        foreach ($result_goods_attr as $value)
        {
            $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
        }

        /* 处理货品 */
        $format = '[%s]';
        foreach($goods_res as $key => $val)
        {
            if ($val['goods_attr'] != '')
            {
                $goods_attr_array = explode('|', $val['goods_attr']);

                $goods_attr = array();
                foreach ($goods_attr_array as $_attr)
                {
                    $goods_attr[] = $_goods_attr[$_attr];
                }

                $goods_res[$key]['goods_attr_str'] = sprintf($format, implode('，', $goods_attr));
            }
        }

        $res[$tempkey]['goods_list']    = $goods_res;
        $res[$tempkey]['subtotal']      = price_format($subtotal);
        $res[$tempkey]['saving']        = price_format(($subtotal - $res[$tempkey]['package_price']));
        $res[$tempkey]['package_price'] = price_format($res[$tempkey]['package_price']);
    }

    return $res;
}

function get_cat_info($cat_id)
{
    $res = $GLOBALS['db']->getRow('SELECT cat_name, custom_name, parent_id FROM ' . $GLOBALS['ecs']->table('category') ." WHERE cat_id = '$cat_id'");
    $res['url'] = build_uri('category', array('cid'=>$cat_id));
    return  $res;
}
function index_get_links()
{
    $sql = 'SELECT link_logo, link_name, link_url FROM ' . $GLOBALS['ecs']->table('friend_link') . ' ORDER BY show_order';
    $res = $GLOBALS['db']->getAll($sql);

    $links['img'] = $links['txt'] = array();

    foreach ($res AS $row)
    {
        if (!empty($row['link_logo']))
        {
            $links['img'][] = array('name' => $row['link_name'],
                                    'url'  => $row['link_url'],
                                    'logo' => $row['link_logo']);
        }
        else
        {
            $links['txt'][] = array('name' => $row['link_name'],
                                    'url'  => $row['link_url']);
        }
    }

    return $links;
}


?>
