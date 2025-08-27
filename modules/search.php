<?php

$act = isset($_REQUEST['act']) && !empty($_REQUEST['act']) ? filter_var($_REQUEST['act'], FILTER_SANITIZE_STRING) : '';
$gid = !empty($_REQUEST['gid']) ? intval($_REQUEST['gid']) : 0; // for compare
/* params search */
$_REQUEST['keywords']   = !empty($_REQUEST['keywords'])   ? filter_var($_REQUEST['keywords'], FILTER_SANITIZE_STRING) : '';
$_REQUEST['brand']      = !empty($_REQUEST['brand'])      ? intval($_REQUEST['brand'])      : 0;
$_REQUEST['category']   = !empty($_REQUEST['category'])   ? intval($_REQUEST['category'])   : 0;
$_REQUEST['min_price']  = !empty($_REQUEST['min_price'])  ? intval($_REQUEST['min_price'])  : 0;
$_REQUEST['max_price']  = !empty($_REQUEST['max_price'])  ? intval($_REQUEST['max_price'])  : 0;
$_REQUEST['goods_type'] = !empty($_REQUEST['goods_type']) ? intval($_REQUEST['goods_type']) : 0;
$_REQUEST['sc_ds']      = !empty($_REQUEST['sc_ds']) ? intval($_REQUEST['sc_ds']) : 0;
$_REQUEST['outstock']   = !empty($_REQUEST['outstock']) ? 1 : 0;

/* make where search */
$keywords  = '';
$tag_where = '';
if (!empty($_REQUEST['keywords']))
{
    $arr = array();
    if (stristr($_REQUEST['keywords'], ' AND ') !== false)
    {
        /* 检查关键字中是否有AND，如果存在就是并 */
        $arr        = explode('AND', $_REQUEST['keywords']);
        $operator   = " AND ";
    }
    elseif (stristr($_REQUEST['keywords'], ' OR ') !== false)
    {
        /* 检查关键字中是否有OR，如果存在就是或 */
        $arr        = explode('OR', $_REQUEST['keywords']);
        $operator   = " OR ";
    }
    elseif (stristr($_REQUEST['keywords'], ' + ') !== false)
    {
        /* 检查关键字中是否有加号，如果存在就是或 */
        $arr        = explode('+', $_REQUEST['keywords']);
        $operator   = " OR ";
    }
    else
    {
        /* 检查关键字中是否有空格，如果存在就是并 */
        $arr        = explode(' ', $_REQUEST['keywords']);
        $operator   = " AND ";
    }

    $keywords = 'AND (';
    $goods_ids = array();
    foreach ($arr AS $key => $val)
    {
        if ($key > 0 && $key < count($arr) && count($arr) > 1)
        {
            $keywords .= $operator;
        }
        $val        = mysql_like_quote(trim($val));
        $sc_dsad    = $_REQUEST['sc_ds'] ? " OR goods_desc LIKE '%$val%'" : '';
        //$keywords  .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%' OR keywords LIKE '%$val%' $sc_dsad)";
        $keywords  .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%')";

        /* search in tag_words */
        // $sql = 'SELECT DISTINCT goods_id FROM ' . $ecs->table('tag') . " WHERE tag_words LIKE '%$val%' ";
        // $res = $db->query($sql);
        // while ($row = $db->FetchRow($res))
        // {
        //     $goods_ids[] = $row['goods_id'];
        // }

        // $db->autoReplace($ecs->table('keywords'), array('date' => local_date('Y-m-d'),
        //     'searchengine' => 'ecshop', 'keyword' => addslashes(str_replace('%', '', $val)), 'count' => 1), array('count' => 1));
    }
    $keywords .= ')';

    $goods_ids = array_unique($goods_ids);
    $tag_where = implode(',', $goods_ids);
    if (!empty($tag_where))
    {
        $tag_where = 'OR g.goods_id ' . db_create_in($tag_where);
    }
}

$category   = !empty($_REQUEST['category']) ? intval($_REQUEST['category'])        : 0;
$categories = ($category > 0)               ? ' AND ' . get_children($category)    : '';
$brand      = $_REQUEST['brand']            ? " AND brand_id = '$_REQUEST[brand]'" : '';
$outstock   = !empty($_REQUEST['outstock']) ? " AND g.goods_number > 0 "           : '';

$min_price  = $_REQUEST['min_price'] != 0                               ? " AND g.shop_price >= '$_REQUEST[min_price]'" : '';
$max_price  = $_REQUEST['max_price'] != 0 || $_REQUEST['min_price'] < 0 ? " AND g.shop_price <= '$_REQUEST[max_price]'" : '';

/* 排序、显示方式以及类型 */
$default_display_type = $_CFG['show_order_type'] == '0' ? 'list' : ($_CFG['show_order_type'] == '1' ? 'grid' : 'text');
$default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'ASC' : 'DESC';
$default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'goods_id' : 'last_update');

$sort = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'shop_price', 'last_update'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;
$order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC'))) ? trim($_REQUEST['order']) : $default_sort_order_method;
$display  = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array('list', 'grid', 'text'))) ? trim($_REQUEST['display'])  : (isset($_SESSION['display_search']) ? $_SESSION['display_search'] : $default_display_type);

$_SESSION['display_search'] = $display;

$page       = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
$size       = !empty($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;

$intromode = '';

if (!empty($_REQUEST['intro']))
{
    switch ($_REQUEST['intro'])
    {
        case 'best':
            $intro   = ' AND g.is_best = 1';
            $intromode = 'best';
            $ur_here = $_LANG['best_goods'];
            break;
        case 'new':
            $intro   = ' AND g.is_new = 1';
            $intromode ='new';
            $ur_here = $_LANG['new_goods'];
            break;
        case 'hot':
            $intro   = ' AND g.is_hot = 1';
            $intromode = 'hot';
            $ur_here = $_LANG['hot_goods'];
            break;
        case 'promotion':
            $time    = gmtime();
            $intro   = " AND g.promote_price > 0 AND g.promote_start_date <= '$time' AND g.promote_end_date >= '$time'";
            $intromode = 'promotion';
            $ur_here = $_LANG['promotion_goods'];
            break;
        default:
            $intro   = '';
    }
}
else
{
    $intro = '';
}

if (empty($ur_here))
{
    $ur_here = $_LANG['search_goods'];
}

/*------------------------------------------------------ */
//-- 属性检索
/*------------------------------------------------------ */
$attr_in  = '';
$attr_num = 0;
$attr_url = '';
$attr_arg = array();

if (!empty($_REQUEST['attr']))
{
    $sql = "SELECT goods_id, COUNT(*) AS num FROM " . $ecs->table("goods_attr") . " WHERE 0 ";
    foreach ($_REQUEST['attr'] AS $key => $val)
    {
        if (is_not_null($val) && is_numeric($key))
        {
            $attr_num++;
            $sql .= " OR (1 ";

            if (is_array($val))
            {
                $sql .= " AND attr_id = '$key'";

                if (!empty($val['from']))
                {
                    $sql .= is_numeric($val['from']) ? " AND attr_value >= " . floatval($val['from'])  : " AND attr_value >= '$val[from]'";
                    $attr_arg["attr[$key][from]"] = $val['from'];
                    $attr_url .= "&amp;attr[$key][from]=$val[from]";
                }

                if (!empty($val['to']))
                {
                    $sql .= is_numeric($val['to']) ? " AND attr_value <= " . floatval($val['to']) : " AND attr_value <= '$val[to]'";
                    $attr_arg["attr[$key][to]"] = $val['to'];
                    $attr_url .= "&amp;attr[$key][to]=$val[to]";
                }
            }
            else
            {
                /* 处理选购中心过来的链接 */
                $sql .= isset($_REQUEST['pickout']) ? " AND attr_id = '$key' AND attr_value = '" . $val . "' " : " AND attr_id = '$key' AND attr_value LIKE '%" . mysql_like_quote($val) . "%' ";
                $attr_url .= "&amp;attr[$key]=$val";
                $attr_arg["attr[$key]"] = $val;
            }

            $sql .= ')';
        }
    }

    /* 如果检索条件都是无效的，就不用检索 */
    if ($attr_num > 0)
    {
        $sql .= " GROUP BY goods_id HAVING num = '$attr_num'";

        $row = $db->getCol($sql);
        if (count($row))
        {
            $attr_in = " AND " . db_create_in($row, 'g.goods_id');
        }
        else
        {
            $attr_in = " AND 0 ";
        }
    }
}
elseif (isset($_REQUEST['pickout']))
{
    /* 从选购中心进入的链接 */
    $sql = "SELECT DISTINCT(goods_id) FROM " . $ecs->table('goods_attr');
    $col = $db->getCol($sql);
    //如果商店没有设置商品属性,那么此检索条件是无效的
    if (!empty($col))
    {
        $attr_in = " AND " . db_create_in($col, 'g.goods_id');
    }
}

/* 获得符合条件的商品总数 */
$sql   = "SELECT COUNT(*) FROM " .$ecs->table('goods'). " AS g ".
    "WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 $attr_in ".
    "AND (( 1 " . $categories . $keywords . $brand . $min_price . $max_price . $intro . $outstock ." ) ".$tag_where." )";
$count = $db->getOne($sql);

$max_page = ($count> 0) ? ceil($count / $size) : 1;
if ($page > $max_page)
{
    $page = $max_page;
}
/* for search_suggest */
if($gid > 0){
    $tag_where = " AND g.goods_id <> $gid ";
}
if($act == 'search_suggest'){
    $size = 10;
    $page = 1;
}
/* End search_suggest */

/* 查询商品 */
$sql = "SELECT g.goods_id, g.cat_id, g.brand_id, g.goods_name, g.seller_note, g.deal_price, g.partner_price, g.market_price, g.is_new, g.is_best,g.goods_number, g.is_hot, g.shop_price AS org_price, ".
            "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
             " IFNULL(AVG(r.comment_rank),0) AS comment_rank,IF(r.comment_rank,count(*),0) AS  comment_count, ".
            "g.promote_price, g.promote_start_date, g.promote_end_date, g.goods_thumb, g.goods_img, g.goods_brief, g.goods_type ".
        "FROM " .$ecs->table('goods'). " AS g ".
        "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
        ' LEFT JOIN  '. $GLOBALS['ecs']->table('comment') .' AS r '.
            'ON r.id_value = g.goods_id AND comment_type = 0 AND r.parent_id = 0 AND r.status = 1 ' .
        "WHERE g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 $attr_in ".
            "AND (( 1 " . $categories . $keywords . $brand . $min_price . $max_price . $intro . $outstock . " ) ".$tag_where." ) " .
        " GROUP BY g.goods_id ORDER BY $sort $order";
$res = $db->SelectLimit($sql, $size, ($page - 1) * $size);

$arr = array();
while ($row = $db->FetchRow($res))
{
    if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $arr[$row['goods_id']]['discount'] =  $promote_price > 0 ? get_discount($row['shop_price'],$promote_price) : '';
        }
        else
        {
            $promote_price = 0;
            $arr[$row['goods_id']]['discount'] ='';
        }


    $arr[$row['goods_id']]['goods_id']      = $row['goods_id'];
    if($display == 'grid')
    {
        $arr[$row['goods_id']]['goods_name']    = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
    }
    else
    {
        $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
    }


     $arr[$row['goods_id']]['cat_name']  = $GLOBALS['db']->getOne('SELECT cat_name FROM '.$GLOBALS['ecs']->table('category').' WHERE cat_id = '.$row['cat_id']);

         $arr[$row['goods_id']]['brand_name']  = $GLOBALS['db']->getOne('SELECT brand_name FROM '.$GLOBALS['ecs']->table('brand').' WHERE brand_id = '.$row['brand_id']);


    $arr[$row['goods_id']]['comment_rank']= ceil($row['comment_rank']) == 0 ? 1 : ceil($row['comment_rank']);
    $arr[$row['goods_id']]['comment_count']=$row['comment_count'];

    $arr[$row['goods_id']]['name']             = $row['goods_name'];

    $arr[$row['goods_id']]['seller_note']  = nl2p(strip_tags($row['seller_note']));
    $arr[$row['goods_id']]['type']          = $row['goods_type'];
    $arr[$row['goods_id']]['market_price']  = price_format($row['market_price']);
    $arr[$row['goods_id']]['shop_price']    = price_format($row['shop_price']);
    $arr[$row['goods_id']]['deal_price'] =  price_format($row['deal_price']);
    $arr[$row['goods_id']]['partner_price'] =  price_format($row['partner_price']);
    $arr[$row['goods_id']]['is_hot']      = $row['is_hot'];
        $arr[$row['goods_id']]['is_new']      = $row['is_new'];
        $arr[$row['goods_id']]['is_best']      = $row['is_best'];
    $arr[$row['goods_id']]['goods_number'] =  $row['goods_number'];

    $arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';

     $arr[$row['goods_id']]['final_price'] = $promote_price > 0 ? $promote_price : $row['shop_price'];

    $arr[$row['goods_id']]['goods_brief']   = $row['goods_brief'];
    $arr[$row['goods_id']]['goods_thumb']   = get_image_path($row['goods_id'], $row['goods_thumb'], true);
    $arr[$row['goods_id']]['goods_img']     = get_image_path($row['goods_id'], $row['goods_img']);
    /* make url for compare */
    if($gid > 0){
        $arr[$row['goods_id']]['url'] = build_uri('goods', array('gid' => $gid, 'cpid'=>$row['goods_id'], 'compare'=>1));
    }else{
        $arr[$row['goods_id']]['url']  = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
    }

    /* webp cho pc */
    $thumb_webp = convertExtension($row['goods_thumb'], 'webp');
    if(file_exists(ROOT_PATH.CDN_PATH.'/'.$thumb_webp)){
        $arr[$row['goods_id']]['thumb_webp'] = $thumb_webp;
    }else{
        $arr[$row['goods_id']]['thumb_webp'] = '';
    }

}
$links = index_get_links();
$smarty->assign('img_links',       $links['img']);
$smarty->assign('txt_links',       $links['txt']);
$smarty->assign('goods_list', $arr);


$viewmore_number = intval($count)-($page*$size);
$smarty->assign('viewmore_number', $viewmore_number);
$smarty->assign('nextpage',  $page+1);

/* return for search suggest and compare */
if($act == 'search_suggest'){

    require(ROOT_PATH . 'includes/cls_json.php');
    $json   = new JSON;
    $result = array('error' => 0, 'message' => '', 'content' => '');
    $result['content'] = $smarty->fetch('library/search_suggest.lbi');
    die($json->encode($result));
}
elseif ($act == 'search_order') {
    $result = array('error' => 0, 'message' => '', 'content' => '', 'pagination'=> '');

    $smarty->assign('cat_url_ajax',  build_uri('search', [
        'keywords'=>stripslashes(urlencode($_REQUEST['keywords'])),
        'page'=>$page,
        'act'=> 'search_order']
     ));
    $smarty->assign('cat_name',  ' kết quả');
    /* Phân trang Ajax */
    if($viewmore_number > 0){
        $result['pagination'] =  $smarty->fetch('library/pages_ajax.lbi');
    }
    $result['viewmore_number'] = $viewmore_number;

    $result['content'] = $smarty->fetch('library/quick_order_goods_list.lbi');
    die(json_encode($result));
}


$smarty->assign('category',   $category);
$smarty->assign('keywords',   htmlspecialchars(stripslashes($_REQUEST['keywords'])));
$smarty->assign('search_keywords',   stripslashes(htmlspecialchars_decode($_REQUEST['keywords'])));
$smarty->assign('brand',      $_REQUEST['brand']);
$smarty->assign('min_price',  $min_price);
$smarty->assign('max_price',  $max_price);
$smarty->assign('outstock',  $_REQUEST['outstock']);


$pager['search'] = array(
        'keywords'   => stripslashes(urlencode($_REQUEST['keywords'])),
        // 'sort'       => $sort,
        // 'order'      => $order,
        'intro'      => empty($intromode) ? '' : trim($intromode)
    );
$pager['search'] = array_merge($pager['search'], $attr_arg);

$pager = get_pager('tim-kiem', $pager['search'], $count, $page, $size);
$pager['display'] = $display;
$smarty->assign('pager', $pager);

$meta_robots = 'INDEX,FOLLOW';
/* noindex params: page */
if($ecsvn_index_follow == false || isset($_REQUEST['page']))
{
    $meta_robots = 'NOINDEX,FOLLOW';
}

$smarty->assign('meta_robots',  htmlspecialchars($meta_robots));

assign_template();
assign_dynamic('search');
$smarty->assign('total_results', $count);
$position = assign_ur_here(0, $ur_here . ($_REQUEST['keywords'] ? '_' . $_REQUEST['keywords'] : ''), 'search');
$smarty->assign('page_title', $position['title']);    // 页面标题
$smarty->assign('ur_here',    $position['ur_here']);  // 当前位置
$smarty->assign('intromode',      $intromode);
$smarty->assign('categories', get_categories_tree()); // 分类树
$smarty->assign('helps',       get_shop_help());      // 网店帮助
//$smarty->assign('top_goods',  get_top10());           // 销售排行
// $smarty->assign('promotion_info', get_promotion_info());

$smarty->display('search.dwt');


/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */
/**
 *
 *
 * @access public
 * @param
 *
 * @return void
 */
function is_not_null($value)
{
    if (is_array($value))
    {
        return (!empty($value['from'])) || (!empty($value['to']));
    }
    else
    {
        return !empty($value);
    }
}

/**
 * 获得可以检索的属性
 *
 * @access  public
 * @params  integer $cat_id
 * @return  void
 */
function get_seachable_attributes($cat_id = 0)
{
    $attributes = array(
        'cate' => array(),
        'attr' => array()
    );

    /* 获得可用的商品类型 */
    $sql = "SELECT t.cat_id, cat_name FROM " .$GLOBALS['ecs']->table('goods_type'). " AS t, ".
           $GLOBALS['ecs']->table('attribute') ." AS a".
           " WHERE t.cat_id = a.cat_id AND t.enabled = 1 AND a.attr_index > 0 ";
    $cat = $GLOBALS['db']->getAll($sql);

    /* 获取可以检索的属性 */
    if (!empty($cat))
    {
        foreach ($cat AS $val)
        {
            $attributes['cate'][$val['cat_id']] = $val['cat_name'];
        }
        $where = $cat_id > 0 ? ' AND a.cat_id = ' . $cat_id : " AND a.cat_id = " . $cat[0]['cat_id'];

        $sql = 'SELECT attr_id, attr_name, attr_input_type, attr_type, attr_values, attr_index, sort_order ' .
               ' FROM ' . $GLOBALS['ecs']->table('attribute') . ' AS a ' .
               ' WHERE a.attr_index > 0 ' .$where.
               ' ORDER BY cat_id, sort_order ASC';
        $res = $GLOBALS['db']->query($sql);

        while ($row = $GLOBALS['db']->FetchRow($res))
        {
            if ($row['attr_index'] == 1 && $row['attr_input_type'] == 1)
            {
                $row['attr_values'] = str_replace("\r", '', $row['attr_values']);
                $options = explode("\n", $row['attr_values']);

                $attr_value = array();
                foreach ($options AS $opt)
                {
                    $attr_value[$opt] = $opt;
                }
                $attributes['attr'][] = array(
                    'id'      => $row['attr_id'],
                    'attr'    => $row['attr_name'],
                    'options' => $attr_value,
                    'type'    => 3
                );
            }
            else
            {
                $attributes['attr'][] = array(
                    'id'   => $row['attr_id'],
                    'attr' => $row['attr_name'],
                    'type' => $row['attr_index']
                );
            }
        }
    }

    return $attributes;
}

if (!function_exists("htmlspecialchars_decode"))
{
    function htmlspecialchars_decode($string, $quote_style = ENT_COMPAT)
    {
        return strtr($string, array_flip(get_html_translation_table(HTML_SPECIALCHARS, $quote_style)));
    }
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
