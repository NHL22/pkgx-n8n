<?php
$sync = isset($_REQUEST['sync']) ? intval($_REQUEST['sync']) : 0;

if ((DEBUG_MODE & 2) != 2 && $ecsvn_iscached == true )
{
    $smarty->caching = $sync > 0 ? false: true;
}

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

$id  = $db->getOne("SELECT id FROM " . $ecs->table('slug') ." WHERE module='category' AND slug = '".$slug."'");
$cat_id = $id > 0 ? intval($id) : 0;
if($cat_id == 0)
{
    ecvn_withRedirect($ecsvn_request['getBaseUrl']);
}

$cat_url = build_uri('category', array('cid'=>$cat_id));

$fkeywords = isset($_REQUEST['keywords']) ? filter_var($_REQUEST['keywords'], FILTER_SANITIZE_STRING) : '';

/* 初始化分页信息 */
$page = isset($_REQUEST['page'])   && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
$size = isset($_CFG['page_size'])  && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;
if($sync === 2) $size = 20;
//$brand = isset($_REQUEST['brand']) && intval($_REQUEST['brand']) > 0 ? intval($_REQUEST['brand']) : 0;
if(isset($slug_brand))
{
    $id  = $db->getOne("SELECT id FROM " . $ecs->table('slug') ." WHERE slug = '".$slug_brand."' AND module='brand'");
    $brand = $id > 0 ? intval($id) : 0;
}else{
    $brand = 0;
}
$price_max = isset($_REQUEST['price_max']) && intval($_REQUEST['price_max']) > 0 ? intval($_REQUEST['price_max']) : 0;
$price_min = isset($_REQUEST['price_min']) && intval($_REQUEST['price_min']) > 0 ? intval($_REQUEST['price_min']) : 0;
$filter_attr_str = isset($_REQUEST['filter']) ? htmlspecialchars(trim($_REQUEST['filter'])) : '';

$filter_attr_str = trim(urldecode($filter_attr_str));
$filter_attr_str = preg_match('/^[\d\.]+$/',$filter_attr_str) ? $filter_attr_str : '';
$filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);
//$filter_attr = empty($filter_attr_str) ? '' : explode(',', $filter_attr_str);
if($cat_id==382){
  $default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'ASC' : 'DESC';
  $default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'goods_id' : 'sort_order');
}
else {
  $default_sort_order_method = $_CFG['sort_order_method'] == '0' ? 'DESC' : 'ASC';
  $default_sort_order_type   = $_CFG['sort_order_type'] == '0' ? 'goods_id' : ($_CFG['sort_order_type'] == '1' ? 'shop_price' : 'sort_order');
}

/* 排序、显示方式以及类型 */
$default_display_type = $_CFG['show_order_type'] == '0' ? 'list' : ($_CFG['show_order_type'] == '1' ? 'grid' : 'text');



$sort  = (isset($_REQUEST['sort'])  && in_array(trim(strtolower($_REQUEST['sort'])), array('goods_id', 'shop_price', 'last_update'))) ? trim($_REQUEST['sort'])  : $default_sort_order_type;
$order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array('ASC', 'DESC')))   ? trim($_REQUEST['order']) : $default_sort_order_method;
$display  = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array('list', 'grid', 'text'))) ? trim($_REQUEST['display'])  : (isset($_COOKIE['ECS']['display']) ? $_COOKIE['ECS']['display'] : $default_display_type);
$display  = in_array($display, array('list', 'grid', 'text')) ? $display : 'text';
/* Lọc sản phẩm mới, nỗi bật, bán chạy của danh mục đang xem */
$recommend = isset($_REQUEST['rc']) && !empty($_REQUEST['rc']) ? $_REQUEST['rc'] : '';
$recommend  = in_array($recommend, array('moi-ve', 'ban-chay', 'noi-bat')) ? $recommend : '';

setcookie('ECS[display]', $display, time() + 86400 * 7, $cookie_path, $cookie_domain, $cookie_secure, TRUE);
/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

/* 页面的缓存ID */
$cache_id = sprintf('%X', crc32($cat_id . '-' .$recommend.$display . '-' . $sort  .'-' . $order  .'-' . $page . '-' . $size . '-' . $_SESSION['user_rank'] . '-' .
    $_CFG['lang'] .'-'. $brand. '-' . $price_max . '-' .$price_min . '-' . $filter_attr_str));

if (!$smarty->is_cached('category.dwt', $cache_id))
{

    $children = get_children($cat_id);

    $cat = get_cat_info($cat_id);


    $smarty->assign('active_url',   $active_url);
    $smarty->assign('shop_notice',     $_CFG['shop_notice']);       // 商店公告

    /* noindex params: page, sort, order, price_min, price_max, filter_attr, display */
    if($ecsvn_index_follow == false || isset($_GET['fb_comment_id']) || isset($_GET['client']) || isset($_REQUEST['page']) || isset($_REQUEST['price_min']) || isset($_REQUEST['price_max']) || isset($_REQUEST['rc']) || isset($_REQUEST['filter']) || isset($_REQUEST['display']) || isset($_REQUEST['sort']) || isset($_REQUEST['order']))
    {
        $cat['meta_robots'] = 'NOINDEX,FOLLOW';
    }

    if (!empty($cat))
    {
        $smarty->assign('keywords',    htmlspecialchars($cat['keywords']));
        $smarty->assign('description', htmlspecialchars($cat['meta_desc']));
        $smarty->assign('meta_robots',  htmlspecialchars($cat['meta_robots']));
        $smarty->assign('cat_style',   htmlspecialchars($cat['style']));
        $smarty->assign('cat_name',   htmlspecialchars($cat['cat_name']));
        $smarty->assign('cat_custom_name',   !empty($cat['custom_name']) ? htmlspecialchars($cat['custom_name']) : htmlspecialchars($cat['cat_name']));
    }
    else
    {
        /* 如果分类不存在则返回首页 */
        ecvn_withRedirect($ecsvn_request['getBaseUrl'].'/tim-kiem');
        exit;
    }

    /* 赋值固定内容 */
    if ($brand > 0)
    {
        $sql = "SELECT brand_name FROM " .$GLOBALS['ecs']->table('brand'). " WHERE brand_id = '$brand'";
        $brand_name = $db->getOne($sql);
    }
    else
    {
        $brand_name = '';
    }

    /* 获取价格分级 */
    $price_name = '';
    if ($cat['grade'] == 0  && $cat['parent_id'] != 0)
    {
        $cat['grade'] = get_parent_grade($cat_id); //如果当前分类级别为空，取最近的上级分类
    }
    $cat['grade'] = $cat['grade'] == 0 ? 4: $cat['grade'];
    if ($cat['grade'] > 1)
    {


        $sql = "SELECT min(g.shop_price) AS min, max(g.shop_price) as max ".
               " FROM " . $ecs->table('goods'). " AS g ".
               " WHERE ($children OR " . get_extension_goods($children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1  ';
               //获得当前分类下商品价格的最大值、最小值

        $row = $db->getRow($sql);

        // 取得价格分级最小单位级数，比如，千元商品最小以100为级数
        $price_grade = 0.0001;
        for($i=-2; $i<= log10($row['max']); $i++)
        {
            $price_grade *= 10;
        }

        //跨度
        $dx = ceil(($row['max'] - $row['min']) / ($cat['grade']) / $price_grade) * $price_grade;
        if($dx == 0)
        {
            $dx = $price_grade;
        }

        for($i = 1; $row['min'] > $dx * $i; $i ++);

        for($j = 1; $row['min'] > $dx * ($i-1) + $price_grade * $j; $j++);
        $row['min'] = $dx * ($i-1) + $price_grade * ($j - 1);

        for(; $row['max'] >= $dx * $i; $i ++);
        $row['max'] = $dx * ($i) + $price_grade * ($j - 1);

        $sql = "SELECT (FLOOR((g.shop_price - $row[min]) / $dx)) AS sn, COUNT(*) AS goods_num  ".
               " FROM " . $ecs->table('goods') . " AS g ".
               " WHERE ($children OR " . get_extension_goods($children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 '.
               " GROUP BY sn ";

        $price_grade = $db->getAll($sql);

        foreach ($price_grade as $key=>$val)
        {

            $temp_key = $key + 1;
            $price_grade[$temp_key]['goods_num'] = $val['goods_num'];
            $price_grade[$temp_key]['start'] = $row['min'] + round($dx * $val['sn']);
            $price_grade[$temp_key]['end'] = $row['min'] + round($dx * ($val['sn'] + 1));
            /* Làm đẹp lọc giá */
            $start_price = $price_grade[$temp_key]['start'];
            $end_price = $price_grade[$temp_key]['end'];
            //($start_price % 1000000 == 0 && $start_price > 999000)
            $text_start = $start_price > 999000 ? 'Từ '.$start_price/1000000 .'&nbsp;-' : ($start_price == 0 ? 'Dưới ': price_format($start_price).'&nbsp;-');
            $text_end= $end_price > 999000 ? $end_price/1000000 .' triệu' : price_format($end_price);
            /* End Làm đẹp lọc giá */
            $price_grade[$temp_key]['price_range'] =  $text_start. '&nbsp;' . $text_end;
            $price_grade[$temp_key]['formated_start'] = price_format($start_price);
            $price_grade[$temp_key]['formated_end'] = price_format($end_price);
            $price_grade[$temp_key]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_grade[$temp_key]['start'], 'price_max'=> $price_grade[$temp_key]['end'], 'filter_attr'=>$filter_attr_str,'rc'=>$recommend, 'keywords'=> $fkeywords), $cat['cat_name']);

            /* 判断价格区间是否被选中 */
            if (isset($_REQUEST['price_min']) && $price_grade[$temp_key]['start'] == $price_min && $price_grade[$temp_key]['end'] == $price_max)
            {
                $price_grade[$temp_key]['selected'] = 1;
                $price_name .= $price_grade[$temp_key]['price_range'];
            }
            else
            {
                $price_grade[$temp_key]['selected'] = 0;
            }
        }

        $price_grade[0]['start'] = 0;
        $price_grade[0]['end'] = 0;
        $price_grade[0]['price_range'] = $_LANG['all_attribute'];
        $price_grade[0]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>0, 'price_max'=> 0, 'filter_attr'=>$filter_attr_str,'rc'=>$recommend, 'keywords'=> $fkeywords), $cat['cat_name']);
        $price_grade[0]['selected'] = empty($price_max) ? 1 : 0;

        $smarty->assign('price_grade',     $price_grade);

    }


    /* Lấy All danh sách thương hiệu khi ở danh mục con cuối cùng */
    // if($cat['parent_id'] == 0){
    //     $children_brand = $children;
    //     $cat_id_in =  $cat_id;
    // }
    // else{
    //    $children_brand = get_children($cat['parent_id']);
    //    $cat_id_in =  $cat['parent_id'];
    // }

    $children_brand = $children;
    $cat_id_in =  $cat_id;

    $sql = "SELECT b.brand_id, b.brand_logo, b.brand_name, COUNT(*) AS goods_num ".
            "FROM " . $GLOBALS['ecs']->table('brand') . "AS b, ".
                $GLOBALS['ecs']->table('goods') . " AS g LEFT JOIN ". $GLOBALS['ecs']->table('goods_cat') . " AS gc ON g.goods_id = gc.goods_id " .
            "WHERE g.brand_id = b.brand_id AND ($children_brand OR " . 'gc.cat_id ' . db_create_in(array_unique(array_merge(array($cat_id_in), array_keys(cat_list($cat_id_in, 0, false))))) . ") AND b.is_show = 1 " .
            " AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".
            "GROUP BY b.brand_id HAVING goods_num > 0 ORDER BY b.sort_order, b.brand_id ASC";

    // $sql = "SELECT b.brand_id, b.brand_logo, b.brand_name, COUNT(*) AS goods_num ".
    //         "FROM " . $GLOBALS['ecs']->table('brand') . "AS b, ".
    //             $GLOBALS['ecs']->table('goods') . " AS g LEFT JOIN ". $GLOBALS['ecs']->table('goods_cat') . " AS gc ON g.goods_id = gc.goods_id " .
    //         "WHERE g.brand_id = b.brand_id AND ($children OR " . 'gc.cat_id ' . db_create_in(array_unique(array_merge(array($cat_id), array_keys(cat_list($cat_id, 0, false))))) . ") AND b.is_show = 1 " .
    //         " AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".
    //         "GROUP BY b.brand_id HAVING goods_num > 0 ORDER BY b.sort_order, b.brand_id ASC";

    $brands = $GLOBALS['db']->getAll($sql);

    foreach ($brands AS $key => $val)
    {
        $temp_key = $key + 1;
        $brands[$temp_key]['brand_name'] = $val['brand_name'];
        $brands[$temp_key]['brand_logo'] = $val['brand_logo'];
        $brands[$temp_key]['url'] = build_uri('category', array('cid' => $cat_id_in, 'bid' => $val['brand_id'], 'price_min'=>$price_min, 'price_max'=> $price_max, 'filter_attr'=>$filter_attr_str,'rc'=>$recommend, 'keywords'=> $fkeywords), $cat['cat_name']);

        /* 判断品牌是否被选中 */
        if ($brand == $brands[$key]['brand_id'])
        {
            $brands[$temp_key]['selected'] = 1;
        }
        else
        {
            $brands[$temp_key]['selected'] = 0;
        }
    }
    $brands[0]['brand_logo'] = '';
    $brands[0]['brand_name'] = $_LANG['all_attribute'];
    $brands[0]['url'] = build_uri('category', array('cid' => $cat_id_in, 'bid' => 0, 'price_min'=>$price_min, 'price_max'=> $price_max, 'filter_attr'=>$filter_attr_str,'rc'=>$recommend, 'keywords'=> $fkeywords), $cat['cat_name']);
    $brands[0]['selected'] = empty($brand) ? 1 : 0;

    $smarty->assign('brands', $brands);


    /* 属性筛选 */
    $ext = ''; //商品查询条件扩展
   $attribute_name = array();
   /**
     * Save ID listselected attribute
     * @var array
     */
    $attribute_selected = array();

    if ($cat['filter_attr'] > 0)
    {
        $cat_filter_attr = explode(',', $cat['filter_attr']);       //提取出此分类的筛选属性
        $all_attr_list = array();

        foreach ($cat_filter_attr AS $key => $value)
        {
            $sql = "SELECT a.attr_name FROM " . $ecs->table('attribute') . " AS a, " . $ecs->table('goods_attr') . " AS ga, " . $ecs->table('goods') . " AS g WHERE ($children OR " . get_extension_goods($children) . ") AND a.attr_id = ga.attr_id AND g.goods_id = ga.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND a.attr_id='$value'";
            if($temp_name = $db->getOne($sql))
            {
                $all_attr_list[$key]['filter_attr_name'] = $temp_name;

                $sql = "SELECT a.attr_id, MIN(a.goods_attr_id ) AS goods_id, a.attr_value AS attr_value FROM " . $ecs->table('goods_attr') . " AS a, " . $ecs->table('goods') .
                       " AS g" .
                       " WHERE ($children OR " . get_extension_goods($children) . ') AND g.goods_id = a.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 '.
                       " AND a.attr_id='$value' ".
                       " GROUP BY a.attr_value";

                $attr_list = $db->getAll($sql);

                $temp_arrt_url_arr = array();

                for ($i = 0; $i < count($cat_filter_attr); $i++)        //获取当前url中已选择属性的值，并保留在数组中
                {
                    $temp_arrt_url_arr[$i] = !empty($filter_attr[$i]) ? $filter_attr[$i] : 0;
                }

                $temp_arrt_url_arr[$key] = 0;                           //“全部”的信息生成
                $temp_arrt_url = implode('.', $temp_arrt_url_arr);
                $all_attr_list[$key]['attr_list'][0]['attr_value'] = $_LANG['all_attribute'];
                $all_attr_list[$key]['attr_list'][0]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$temp_arrt_url, 'keywords'=> $fkeywords), $cat['cat_name']);
                $all_attr_list[$key]['attr_list'][0]['selected'] = empty($filter_attr[$key]) ? 1 : 0;

                foreach ($attr_list as $k => $v)
                {
                    $temp_key = $k + 1;
                    $temp_arrt_url_arr[$key] = $v['goods_id'];       //为url中代表当前筛选属性的位置变量赋值,并生成以‘.’分隔的筛选属性字符串
                    $temp_arrt_url = implode('.', $temp_arrt_url_arr);

                    $all_attr_list[$key]['attr_list'][$temp_key]['attr_value'] = $v['attr_value'];
                    $all_attr_list[$key]['attr_list'][$temp_key]['id'] = $v['attr_id'];

                    $all_attr_list[$key]['attr_list'][$temp_key]['url'] = build_uri('category', array('cid'=>$cat_id, 'bid'=>$brand, 'price_min'=>$price_min, 'price_max'=>$price_max, 'filter_attr'=>$temp_arrt_url, 'keywords'=> $fkeywords), $cat['cat_name']);

                    if (!empty($filter_attr[$key]) AND $filter_attr[$key] == $v['goods_id'])
                    {
                        $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 1;

                        /* tạo mảng thuộc tính đã chọn */
                        $attribute_name[$key]['pid'] =  $value;
                        $attribute_name[$key]['id']   = $all_attr_list[$key]['attr_list'][1]['id'];
                        $attribute_name[$key]['name'] = ' '.$v['attr_value'];
                        $attribute_name[$key]['url']  = $all_attr_list[$key]['attr_list'][1]['url'];

                    }
                    else
                    {
                        $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 0;
                    }
                }
            }

        }

        $smarty->assign('filter_attr_list',  $all_attr_list);
        /* 扩展商品查询条件 */
        if (!empty($filter_attr))
        {
            $ext_sql = "SELECT DISTINCT(b.goods_id) FROM " . $ecs->table('goods_attr') . " AS a, " . $ecs->table('goods_attr') . " AS b " .  "WHERE ";
            $ext_group_goods = array();

            foreach ($filter_attr AS $k => $v)                      // 查出符合所有筛选属性条件的商品id */
            {
                if (is_numeric($v) && $v !=0 &&isset($cat_filter_attr[$k]))
                {
                    $sql = $ext_sql . "b.attr_value = a.attr_value AND b.attr_id = " . $cat_filter_attr[$k] ." AND a.goods_attr_id = " . $v;
                    $ext_group_goods = $db->getColCached($sql);
                    $ext .= ' AND ' . db_create_in($ext_group_goods, 'g.goods_id');
                }
            }
        }
    }


    /**
     * Tính năng mới thêm để tránh trùng lặp meta title
     * Lọc sp recommend 1:new|2:best|3:hot tại danh mục hiện hành
     */
    $ext_title = '';
    if($recommend == 'moi-ve'){
        $ext .= ' AND g.is_new=1 ';
        $ext_title .= $_LANG['recommend_goods'][$recommend];
    }elseif ($recommend == 'ban-chay') {
        $ext .= ' AND g.is_best=1 ';
        $ext_title .= $_LANG['recommend_goods'][$recommend];
    }elseif ($recommend == 'noi-bat') {
        $ext .= ' AND g.is_hot=1 ';
        $ext_title .= $_LANG['recommend_goods'][$recommend];
    }

    if(!empty($fkeywords)){
        $ext .= " AND g.goods_name LIKE '%" . mysql_like_quote($fkeywords) . "%' ";
    }

    assign_template('c', array($cat_id));

    $position = assign_ur_here($cat_id, '', 'category');
    if(!empty($recommend)){
        $page_title = htmlspecialchars($cat['cat_name']).' '.$ext_title.$_LANG['salein'].$_CFG['shop_name'];
    }else{
        $page_title = !empty($cat['meta_title']) ? htmlspecialchars($cat['meta_title']) : $position['title'];
    }
    $smarty->assign('page_title',  $page_title);
    $smarty->assign('ur_here',          $position['ur_here']);  // 当前位置

    $smarty->assign('categories',       get_categories_tree($cat_id)); // 分类树
    $smarty->assign('helps',            get_shop_help());              // 网店帮助
    //$smarty->assign('top_goods',        get_top10());                  // 销售排行
    $smarty->assign('show_marketprice', $_CFG['show_marketprice']);
    $smarty->assign('category',         $cat_id);
    $smarty->assign('brand_id',         $brand);
    $smarty->assign('price_max',        $price_max);
    $smarty->assign('price_min',        $price_min);
    $smarty->assign('filter_attr',      $filter_attr_str);
    $smarty->assign('recommend',        $recommend);
    $smarty->assign('cat_url',         $cat_url);
    $smarty->assign('ext_title',       $ext_title);


    // if ($brand > 0)
    // {
    //     $arr['all'] = array('brand_id'  => 0,
    //                     'brand_name'    => $GLOBALS['_LANG']['all_goods'],
    //                     'brand_logo'    => '',
    //                     'goods_num'     => '',
    //                     'url'           => build_uri('category', array('cid'=>$cat_id), $cat['cat_name'])
    //                 );
    // }
    // else
    // {
    //     $arr = array();
    // }

    // $brand_list = array_merge($arr, get_brands($cat_id, 'category'));
    // $smarty->assign('brand_list',      $brand_list);

    /* links */
    $links = index_get_links();
    $smarty->assign('img_links',       $links['img']);
    $smarty->assign('txt_links',       $links['txt']);
    $smarty->assign('data_dir',    DATA_DIR);
    //$smarty->assign('promotion_info', get_promotion_info());


    /* 调查 */
    // $vote = get_vote();
    // if (!empty($vote))
    // {
    //     $smarty->assign('vote_id',     $vote['id']);
    //     $smarty->assign('vote',        $vote['content']);
    // }

    $smarty->assign('catsubs', get_cat_child($cat_id, $cat['parent_id']));
    $smarty->assign('parent_id', $cat['parent_id']);
    $smarty->assign('long_desc', $cat['long_desc']);
    $smarty->assign('cat_desc', $cat['cat_desc']);
    $smarty->assign('ads_id', $cat['ads_category']);
    $smarty->assign('ads_id_mobile', $cat['ads_category_mobile']);
    $smarty->assign('cat_thumb', $cat['thumb']);

    $smarty->assign('best_goods',      get_category_recommend_goods('best', $children, $brand, $price_min, $price_max, $ext));
    //$smarty->assign('promotion_goods', get_category_recommend_goods('promote', $children, $brand, $price_min, $price_max, $ext));
    $smarty->assign('hot_goods',       get_category_recommend_goods('hot', $children, $brand, $price_min, $price_max, $ext));

    $count = get_cagtegory_goods_count($children, $brand, $price_min, $price_max, $ext);
    $max_page = ($count> 0) ? ceil($count / $size) : 1;
    if ($page > $max_page)
    {
        $page = $max_page;
    }
    $goodslist = category_get_goods($children, $brand, $price_min, $price_max, $ext, $size, $page, $sort, $order);
    if($display == 'grid')
    {
        if(count($goodslist) % 2 != 0)
        {
            $goodslist[] = array();
        }
    }
    $smarty->assign('count',       $count);
    $smarty->assign('goods_list',       $goodslist);
    $smarty->assign('category',         $cat_id);
    $smarty->assign('script_name', 'category');
    $smarty->assign('slug',   $cat_url);

    /* Name filter */
    $smarty->assign('brand_name',  $brand_name);
    $smarty->assign('price_name',  $price_name);
    $smarty->assign('attribute_name', $attribute_name);

     /* fix sort order default when pagination */
    if($sort == 'sort_order'){
        $sort = $order = '';
    }

    assign_pager('category',  $cat_id, $count, $size, $sort, $order, $page, '', $brand, $price_min, $price_max, $display, $filter_attr_str,'','',$recommend); // 分页
    assign_dynamic('category');



    $viewmore_number = intval($count)-($page*$size);
    $smarty->assign('viewmore_number', $viewmore_number);

    $smarty->assign('page',  $page);
    $nextpage = $page+1;
    $smarty->assign('nextpage',  $page+1);

    $ajax_params = array(
        'cid' =>$cat_id,
        'filter_attr'=>$filter_attr_str,
        'rc'=>$recommend,
        'bid'=>$brand,
        'price_min'=>$price_min,
        'price_max'=>$price_max,
        'keywords'=>$fkeywords
    );
    if($sort == 'shop_price'){
        $ajax_params['sort'] = $sort;
        $ajax_params['order'] = $order;
    }
    $cat_url_ajax = build_uri('category', $ajax_params);
    $smarty->assign('cat_url_ajax',  $cat_url_ajax);

    if($sync === 1){
        define('IS_AJAX', true);
        $smarty->caching = false;
        $smarty->assign('is_ajax', 1);
        include_once(ROOT_PATH.'includes/cls_json.php');
        $json  = new JSON;
        $result = array('error' => 0, 'message' => '', 'content' => '', 'pagination' => '', 'goods_list'=> '');
        /* Load sản phẩm phân trang Ajax */
        $result['goods_list'] =  $smarty->fetch('library/goods_list.lbi');
        /* Phân trang Ajax */
        if($viewmore_number > 0){
            $result['pagination'] =  $smarty->fetch('library/pages_ajax.lbi');
        }
        $result['content'] = $viewmore_number;
        $result['cat_url_ajax'] = $cat_url_ajax;

        die($json->encode($result));
    }
    /* Ajax Build cau hinh */
    elseif ($sync === 2) {
        define('IS_AJAX', true);
        include_once(ROOT_PATH.'includes/cls_json.php');
        $json  = new JSON;
        $result = array('error' => 0, 'message' => '', 'data' => '', 'pagination'=> '', 'filter'=> '');
        $ajax_params['page'] = $page+1;
        $cat_url_ajax = build_uri('category', $ajax_params);
        $smarty->assign('keywords',  $fkeywords);

        if($viewmore_number > 0){
            $href='javascript:loadGoods("'.$cat_url_ajax.'",true)';
            $result['pagination'] = "<a rel='nofflow' class='caret_down' href='".$href."'>Xem thêm ".$viewmore_number." ".$cat['cat_name']." <span></span></a>";
        }
        $result['viewmore_number'] = $viewmore_number;
        $result['data']   =  $smarty->fetch('library/quick_order_goods_list.lbi');
        $result['filter'] = $smarty->fetch('library/quick_order_filter.lbi');
        $result['choosedfilter'] = $smarty->fetch('library/quick_order_choosedfilter.lbi');
        $result['keywords'] = $fkeywords;
        $result['cat_url_ajax'] = $cat_url_ajax;
        die($json->encode($result));

    }

    $db->query('UPDATE ' . $ecs->table('category') . " SET click_count = click_count + 1 WHERE cat_id = '$cat_id'");
}

$smarty->display('category.dwt', $cache_id);

/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */

/**
 * 获得分类的信息
 *
 * @param   integer $cat_id
 *
 * @return  void
 */
function get_cat_info($cat_id)
{
    return $GLOBALS['db']->getRow('SELECT cat_name, custom_name, meta_desc, meta_title, meta_robots, thumb, keywords, ads_category, ads_category_mobile, cat_desc, long_desc, style, grade, filter_attr, parent_id FROM ' . $GLOBALS['ecs']->table('category') .
        " WHERE cat_id = '$cat_id'");
}

/**
 * 获得分类下的商品
 *
 * @access  public
 * @param   string  $children
 * @return  array
 */
function category_get_goods($children, $brand, $min, $max, $ext, $size, $page, $sort, $order)
{
    $display = $GLOBALS['display'];
    $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ".
            "g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';

    if ($brand > 0)
    {
        $where .=  "AND g.brand_id=$brand ";
    }

    if ($min > 0)
    {
        $where .= " AND g.shop_price >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND g.shop_price <= $max ";
    }

    /* 获得商品列表 */
    $sql = 'SELECT g.goods_id, g.goods_sn, g.cat_id, g.brand_id, g.goods_name, g.click_count, g.seller_note, g.goods_name_style, g.deal_price, g.partner_price, g.market_price, g.is_new,g.goods_number, g.is_best, g.is_hot, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " .
                " IFNULL(AVG(r.comment_rank),0) AS comment_rank,IF(r.comment_rank,count(*),0) AS  comment_count, ".
                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . ' AS mp ' .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
            ' LEFT JOIN  '. $GLOBALS['ecs']->table('comment') .' AS r '.
                'ON r.id_value = g.goods_id AND comment_type = 0 AND r.parent_id = 0 AND r.status = 1 ' .
            "WHERE $where $ext GROUP BY g.goods_id ORDER BY $sort $order";

    $res = $GLOBALS['db']->selectLimit($sql, $size, ($page - 1) * $size);

    $arr = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
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

        $arr[$row['goods_id']]['comment_rank']= ceil($row['comment_rank']) == 0 ? 1 : ceil($row['comment_rank']);
        $arr[$row['goods_id']]['comment_count']=$row['comment_count'];

        $arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
        if($display == 'grid')
        {
            $arr[$row['goods_id']]['goods_name']       = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        }
        else
        {
            $arr[$row['goods_id']]['goods_name']       = $row['goods_name'];
        }

         $arr[$row['goods_id']]['cat_name']  = $GLOBALS['db']->getOne('SELECT cat_name FROM '.$GLOBALS['ecs']->table('category').' WHERE cat_id = '.$row['cat_id']);

         $arr[$row['goods_id']]['brand_name']  = $GLOBALS['db']->getOne('SELECT brand_name FROM '.$GLOBALS['ecs']->table('brand').' WHERE brand_id = '.$row['brand_id']);

        $arr[$row['goods_id']]['seller_note']  = nl2p(strip_tags($row['seller_note']));
        $arr[$row['goods_id']]['name']             = $row['goods_name'];
        $arr[$row['goods_id']]['goods_sn']             = $row['goods_sn'];
        $arr[$row['goods_id']]['goods_brief']      = $row['goods_brief'];
        $arr[$row['goods_id']]['is_hot']      = $row['is_hot'];
        $arr[$row['goods_id']]['is_new']      = $row['is_new'];
        $arr[$row['goods_id']]['is_best']      = $row['is_best'];
        $arr[$row['goods_id']]['goods_number']      = $row['goods_number'];
        $arr[$row['goods_id']]['click_count']      = $row['click_count'];

        $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_name'],$row['goods_name_style']);
        $arr[$row['goods_id']]['market_price']     = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
        $arr[$row['goods_id']]['deal_price'] =  price_format($row['deal_price']);
        $arr[$row['goods_id']]['partner_price'] =  price_format($row['partner_price']);

        $arr[$row['goods_id']]['type']             = $row['goods_type'];
        $arr[$row['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';

        $arr[$row['goods_id']]['final_price'] = $promote_price > 0 ? $promote_price : $row['shop_price'];
        $arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['goods_img']        = get_image_path($row['goods_id'], $row['goods_img']);
        $arr[$row['goods_id']]['url']              = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

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
 * 获得分类下的商品总数
 *
 * @access  public
 * @param   string     $cat_id
 * @return  integer
 */
function get_cagtegory_goods_count($children, $brand = 0, $min = 0, $max = 0, $ext='')
{
    $where  = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND ($children OR " . get_extension_goods($children) . ')';

    if ($brand > 0)
    {
        $where .=  " AND g.brand_id = $brand ";
    }

    if ($min > 0)
    {
        $where .= " AND g.shop_price >= $min ";
    }

    if ($max > 0)
    {
        $where .= " AND g.shop_price <= $max ";
    }

    /* 返回商品总数 */
    return $GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('goods') . " AS g WHERE $where $ext");
}

/**
 * 取得最近的上级分类的grade值
 *
 * @access  public
 * @param   int     $cat_id    //当前的cat_id
 *
 * @return int
 */
function get_parent_grade($cat_id)
{
    static $res = NULL;

    if ($res === NULL)
    {
        $data = read_static_cache('cat_parent_grade');
        if ($data === false)
        {
            $sql = "SELECT parent_id, cat_id, grade ".
                   " FROM " . $GLOBALS['ecs']->table('category');
            $res = $GLOBALS['db']->getAll($sql);
            write_static_cache('cat_parent_grade', $res);
        }
        else
        {
            $res = $data;
        }
    }

    if (!$res)
    {
        return 0;
    }

    $parent_arr = array();
    $grade_arr = array();

    foreach ($res as $val)
    {
        $parent_arr[$val['cat_id']] = $val['parent_id'];
        $grade_arr[$val['cat_id']] = $val['grade'];
    }

    while ($parent_arr[$cat_id] >0 && $grade_arr[$cat_id] == 0)
    {
        $cat_id = $parent_arr[$cat_id];
    }

    return $grade_arr[$cat_id];

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
