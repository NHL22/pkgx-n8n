<?php

if ((DEBUG_MODE & 2) != 2 && $ecsvn_iscached == true )
{
    $smarty->caching = true;
}
/* 清除缓存 */
clear_cache_files();

/*------------------------------------------------------ */
//-- INPUT
/*------------------------------------------------------ */

// $id  = $db->getOne("SELECT id FROM " . $ecs->table('slug') ." WHERE slug = '".$slug."' AND module='article_cat'");
// $cat_id = $id > 0 ? intval($id) : 0;

if($cat_id == 0)
{
    ecvn_withRedirect($ecsvn_request['getBaseUrl'].'/tim-kiem');
}

/* Validate URL */
$url_right =  build_uri('article_cat', array('acid' => $cat_id));
if($url_right != $_url){
    ecvn_withRedirect($ecsvn_request['getBaseUrl'].$url_right);
}
/* End Validate URL */

$cat_url = $url_right;

/* 获得当前页码 */
$page   = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;

/*------------------------------------------------------ */
//-- PROCESSOR
/*------------------------------------------------------ */

/* 获得页面的缓存ID */
$cache_id = sprintf('%X', crc32($cat_id . '-' . $page . '-' . $_CFG['lang']));

if (!$smarty->is_cached('article_cat.dwt', $cache_id))
{
    /* 如果页面没有被缓存则重新获得页面的内容 */

    assign_template('a', array($cat_id));
    $position = assign_ur_here($cat_id, '' , 'article_cat');
    $smarty->assign('ur_here',              $position['ur_here']);   // 当前位置
    $links = index_get_links();
    $smarty->assign('img_links',       $links['img']);
    $smarty->assign('txt_links',       $links['txt']);

    $smarty->assign('categories',           get_categories_tree(0)); // 分类树
    $smarty->assign('article_categories',   article_categories_tree(8)); //文章分类树
    $smarty->assign('helps',                get_shop_help());        // 网店帮助
    //$smarty->assign('top_goods',            get_top10());            // 销售排行
    // $smarty->assign('best_goods',           get_recommend_goods('best'));
    // $smarty->assign('new_goods',            get_recommend_goods('new'));
    // $smarty->assign('hot_goods',            get_recommend_goods('hot'));
    // $smarty->assign('promotion_goods',      get_promote_goods());
    // $smarty->assign('promotion_info', get_promotion_info());
    $smarty->assign('new_articles',    index_get_new_articles());



    /* Meta */
    $meta = $db->getRow("SELECT keywords, meta_title, cat_name, cat_desc, meta_robots FROM " . $ecs->table('article_cat') . " WHERE cat_id = '$cat_id'");

    if ($meta === false || empty($meta))
    {
        ecvn_withRedirect($ecsvn_request['getBaseUrl'].'/tim-kiem');
        exit;
    }

    /* noindex params: page */
    if($ecsvn_index_follow == false || isset($_REQUEST['page']) || isset($_GET['client']) || isset($_GET['fb_comment_id']))
    {
        $meta['meta_robots'] = 'NOINDEX,FOLLOW';
    }

    $smarty->assign('keywords',    htmlspecialchars($meta['keywords']));
    $smarty->assign('description', htmlspecialchars($meta['cat_desc']));
    $smarty->assign('cat_name',    htmlspecialchars($meta['cat_name']));
    $smarty->assign('meta_robots',  htmlspecialchars($meta['meta_robots']));

    $page_title = !empty($meta['meta_title']) ? htmlspecialchars($meta['meta_title']) : $position['title'];
    $smarty->assign('page_title',  $page_title);


    if($_device == 'mobile' && $page > 1){
        $smarty->assign('topview_article',  []);
    }else{
        $smarty->assign('topview_article',  getTopViewArticles($cat_id));
    }



    /* 获得文章总数 */
    $size   = isset($_CFG['article_page_size']) && intval($_CFG['article_page_size']) > 0 ? intval($_CFG['article_page_size']) : 20;
    $count  = get_article_count($cat_id);
    $pages  = ($count > 0) ? ceil($count / $size) : 1;

    if ($page > $pages)
    {
        $page = $pages;
    }
    $pager['search']['id'] = $cat_id;
    $keywords = '';
    $goon_keywords = ''; //继续传递的搜索关键词

    /* 获得文章列表 */
    if (isset($_REQUEST['keywords']))
    {
        $keywords = addslashes(htmlspecialchars(urldecode(trim($_REQUEST['keywords']))));
        $pager['search']['keywords'] = $keywords;
        //$search_url = substr(strrchr($_POST['cur_url'], '/'), 1);

        $smarty->assign('search_value',    stripslashes(stripslashes($keywords)));
        //$smarty->assign('search_url',       $search_url);
        $count  = get_article_count($cat_id, $keywords);
        $pages  = ($count > 0) ? ceil($count / $size) : 1;
        if ($page > $pages)
        {
            $page = $pages;
        }
        $smarty->assign('total_search',    $count);
        $goon_keywords = urlencode($_REQUEST['keywords']);
    }
    $smarty->assign('artciles_list',    get_cat_articles($cat_id, $page, $size ,$keywords));
    $smarty->assign('cat_id',    $cat_id);
    $smarty->assign('cat_url',    $cat_url);



    /* 分页 */
    assign_pager('article_cat', $cat_id, $count, $size, '', '', $page, $goon_keywords);
    assign_dynamic('article_cat');

    $viewmore_number = intval($count)-($page*$size);
    $smarty->assign('viewmore_number', $viewmore_number);

    $db->query('UPDATE ' . $ecs->table('article_cat') . " SET click_count = click_count + 1 WHERE cat_id = '$cat_id'");
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
$smarty->display('article_cat.dwt', $cache_id);

?>
