<?php
/**
 * Dynamic Sitemap
 * @author: Ecshopvietnam.com
 */
$base_path = $ecsvn_request['getBaseUrl'];
$smarty->caching = false;

define('INIT_NO_USERS', true);
define('INIT_NO_SMARTY', true);

/*
sitemap allow

 */
$index_list =  array('category', 'goods', 'article', 'article-cat',  'image-article');

if(!in_array($types, $index_list) && $types != 'index'){
    ecvn_withRedirect($base_path.'404');
}
/**
 * Class bulid Sitemap Product, Artilce
 */
class sitemap
{
    var $head = "<\x3Fxml version=\"1.0\" encoding=\"UTF-8\"\x3F>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    var $footer = "</urlset>\n";
    var $item;
    function item($item)
    {
        $this->item .= "<url>\n";
        foreach($item as $key => $val){
            $this->item .=" <$key>".htmlentities($val, ENT_QUOTES)."</$key>\n";
        }
        $this->item .= "</url>\n";
    }
    function generate(){
         return $this->head.$this->item.$this->footer;
    }
}
/**
 * Class bulid Sitemap Images
 */
class sitemap_image
{
    var $head   = "<\x3Fxml version=\"1.0\" encoding=\"UTF-8\"\x3F>\n<urlset  xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\">\n";
    var $footer = '</urlset>';
    var $item = '';
    function item($item)
    {
        foreach($item as $key => $val){
            $this->item .= "\t<url>\n";
            $this->item .= "\t\t".'<loc>'.htmlentities($val['url'], ENT_QUOTES).'</loc>'. "\n";
            foreach ($val['images'] as $k => $v) {
                $this->item .= "\t\t".'<image:image>'. "\n";
                $this->item .= "\t\t\t".'<image:loc>'.htmlentities($v['src'], ENT_QUOTES).'</image:loc>'. "\n";
                $this->item .= "\t\t\t".'<image:title>'.htmlspecialchars($val['title'], ENT_QUOTES).'</image:title>'. "\n";
                if(!empty($v['caption'])){
                $this->item .= "\t\t\t".'<image:caption>'.htmlspecialchars($v['caption'], ENT_QUOTES).'</image:caption>'. "\n";
                }
                $this->item .=  "\t\t".'</image:image>'. "\n";
            }
            $this->item .= "\t</url>\n";
        }

    }
    function generate(){
        return $this->head.$this->item.$this->footer;
    }
}

/**
 * Build Sitemap
 */

// XML Sitemap Indexed
if ($types == 'index') {
    $out_header = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $out_header .= '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $out_footer = '</sitemapindex>';

    $out_body = '';
    foreach ($index_list as $val) {
        $out_body .= "\t".'<sitemap>'. "\n";
        $out_body .= "\t\t".'<loc>'.$base_path.'sitemap-'.$val.'.xml</loc>'. "\n";
        $out_body .= "\t\t".'<lastmod>'.date('c').'</lastmod>'. "\n";
        $out_body .= "\t".'</sitemap>'. "\n";
    }

    $out = $out_header.$out_body.$out_footer;
}
// XML Sitemap News
elseif ($types == 'article') {
    /**
     * Đọc sitemap article từ Cache
     */
    $data = read_static_cache('sitemap_article');
    if ($data === false)
    {
        $sitemap = new sitemap;
        /* khai báo lại head riêng cho tin tức */
        $sitemap->head = "<\x3Fxml version=\"1.0\" encoding=\"UTF-8\"\x3F>\n<urlset  xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\">\n";
        $config = unserialize($_CFG['sitemap']);
        $item = array(
            'loc'        =>  "$base_path",
            'lastmod'     =>  date('c'),
            'changefreq' => $config['homepage_changefreq'],
            'priority' => $config['homepage_priority'],
        );
        $sql = "SELECT article_id, add_time, modify_time FROM " .$ecs->table('article'). " WHERE is_open=1 AND cat_id > 3 AND (meta_robots = 'INDEX,FOLLOW' || meta_robots = 'INDEX,NOFOLLOW')";
         $res = $db->getAll($sql);
        foreach ($res as $key => $row)
        {

            $item = array(
                'loc'        =>  $base_path. build_uri('article', array('aid'=>$row['article_id'])),
                'lastmod'     =>  date('c', $row['modify_time']),
                'changefreq' => $config['content_changefreq'],
                'priority' => $config['content_priority'],
            );
            $sitemap->item($item);
        }
        $out =  $sitemap->generate();
        write_static_cache('sitemap_article',$out, 8640); /* Cache 1 days */
    }else{
        $out = $data;
    }

}
// XML Sitemap Category News
elseif ($types == 'article-cat') {
    /**
     * Đọc sitemap article từ Cache
     */
    $data = read_static_cache('sitemap_article_cat');
    if ($data === false)
    {
        $sitemap = new sitemap;
        /* khai báo lại head riêng cho tin tức */
        $sitemap->head = "<\x3Fxml version=\"1.0\" encoding=\"UTF-8\"\x3F>\n<urlset  xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\">\n";
        $config = unserialize($_CFG['sitemap']);

        $sql = "SELECT cat_id, meta_robots FROM " .$ecs->table('article_cat'). " WHERE cat_type=1 AND (meta_robots = 'INDEX,FOLLOW' || meta_robots = 'INDEX,NOFOLLOW')";
        $res = $db->getAll($sql);
        foreach ($res as $key => $row)
        {
            $item = array(
                'loc'        =>  $base_path . build_uri('article_cat', array('acid' => $row['cat_id'])),
                'lastmod'     =>  date('c'),
                'changefreq' => $config['category_changefreq'],
                'priority' => $config['category_priority'],
            );
            $sitemap->item($item);
        }
        $out =  $sitemap->generate();
        write_static_cache('sitemap_article_cat',$out, 86400*7);  /* Cache 7 days */
    }else{
        $out = $data;
    }
}
// XML Sitemap Products
elseif ($types == 'goods') {
    /**
     * Đọc sitemap article từ Cache
     */
    $data = read_static_cache('sitemap_goods');
    if ($data === false)
    {
        $sitemap = new sitemap;
        $config = unserialize($_CFG['sitemap']);

        $sql = "SELECT goods_id, last_update FROM " .$ecs->table('goods'). " WHERE is_delete = 0 AND (meta_robots = 'INDEX,FOLLOW' || meta_robots = 'INDEX,NOFOLLOW')";
        $res = $db->getAll($sql);
        foreach ($res as $key => $row)
        {
            $item = array(
                'loc'        =>  $base_path . build_uri('goods', array('gid' => $row['goods_id'])),
                'lastmod'     =>  date('c', $row['last_update']),
                'changefreq' => $config['content_changefreq'],
                'priority' => $config['content_priority'],
            );
            $sitemap->item($item);
        }
        $out =  $sitemap->generate();
        write_static_cache('sitemap_goods',$out, 86400);  /* Cache 1 days */
    }else{
        $out = $data;
    }
}
// XML Sitemap Products Category
elseif ($types == 'category') {

    /**
     * Đọc sitemap article từ Cache
     */
    $data = read_static_cache('sitemap_category');
    if ($data === false)
    {
        $sitemap = new sitemap;
        $config = unserialize($_CFG['sitemap']);

        $sql = "SELECT cat_id, cat_name FROM " .$ecs->table('category'). " WHERE meta_robots = 'INDEX,FOLLOW'";
        $res = $db->getAll($sql);
        foreach ($res as $key => $row)
        {
            $item = array(
                'loc'        =>  $base_path . build_uri('category', array('cid' => $row['cat_id'])),
                'lastmod'     =>  date('c'),
                'changefreq' => $config['category_changefreq'],
                'priority' => $config['category_priority'],
            );
            $sitemap->item($item);
        }
        $out =  $sitemap->generate();
        write_static_cache('sitemap_category',$out, 86400*7);  /* Cache 7 days */
    }else{
        $out = $data;
    }
}
// XML Sitemap Images
elseif ($types == 'image-goods') {

    /* Đọc sitemap sitemap_goods_img từ Cache */
    $data = read_static_cache('sitemap_goods_img');
    if ($data === false)
    {
        $sitemap = new sitemap_image;
        /* Hình sản phẩm */
        $sql = "SELECT goods_id, goods_name, goods_thumb, keywords, goods_img FROM " .$ecs->table('goods'). " WHERE is_delete = 0";
        $res = $db->getAll($sql);

        var_dump($res);exit;
        $item = [];
        foreach ($res as $key => $row)
        {
            $item[$key] = array(
                'url'     => $base_path . build_uri('goods', array('gid' => $row['goods_id'])),
                'title'   => strip_tags($row['goods_name']),
            );
            $item[$key]['images'][] = array(
                                'src'=> $base_cdn.'/'.$row['goods_img'],
                                'caption'=> !empty($row['keywords']) ? $row['keywords'] : $row['goods_name']
                            );
            /* Lấy hình từ gallery */
            // $sql = "SELECT img_url, img_desc FROM " .$GLOBALS['ecs']->table('goods_gallery'). " WHERE goods_id = $row[goods_id]";
            // $gallery = $GLOBALS['db']->getAll($sql);
            // if($gallery){
            //     foreach ($gallery as $k => $val){
            //         $item[$key]['images'][]  =  array('src'=> $base_cdn.'/'.$val['img_url'], 'caption'=> !empty($val['img_desc']) ? strip_tags($val['img_desc']) : strip_tags($row['goods_name']));
            //     }
            // }
            $sitemap->item($item);
        }
        $out =  $sitemap->generate();
        write_static_cache('sitemap_goods_img',$out, 86400);  /* Cache 1 days */
    }else{
        $out = $data;
    }
}
elseif ($types == 'image-article') {

    $head   = "<\x3Fxml version=\"1.0\" encoding=\"UTF-8\"\x3F>\n<urlset  xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\">\n";
    $footer = '</urlset>';
    $item = '';

        $data = read_static_cache('sitemap_article_img');
        if ($data === false)
        {
            /* Hình Tin tức */
            $sql = "SELECT article_id, title, keywords, article_thumb FROM " .$ecs->table('article'). " WHERE is_open=1 AND cat_id > 3 AND (meta_robots = 'INDEX,FOLLOW' || meta_robots = 'INDEX,NOFOLLOW')";
            $res = $db->getAll($sql);
            write_static_cache('sitemap_article_img',$res, 86400*3);  /* Cache 1 days */
        }else{
            $res = $data;
        }
        foreach ($res as $key => $row)
        {
            if(!empty($row['article_thumb'])){
                $item .= "\t<url>\n";
                $item .= "\t\t".'<loc>'.htmlentities($base_path.build_uri('article', array('aid' => $row['article_id'])), ENT_QUOTES).'</loc>'. "\n";
                $item .= "\t\t".'<image:image>'. "\n";
                $item .= "\t\t\t".'<image:loc>'.htmlspecialchars($base_cdn.'/'.$row['article_thumb'], ENT_QUOTES).'</image:loc>'. "\n";
                $item .= "\t\t\t".'<image:title>'.htmlspecialchars($row['title'], ENT_QUOTES).'</image:title>'. "\n";
                $item .= "\t\t\t".'<image:caption>'.htmlspecialchars($row['keywords'], ENT_QUOTES).'</image:caption>'. "\n";
                $item .= "\t\t".'</image:image>'. "\n";
                $item .= "\t</url>\n";
            }
        }
    $out = $head.$item.$footer;
}
/* Nếu không tồn tại loại nào cụ thể => Chuyển hướng về Indexed */
else{
    ecvn_withRedirect($base_path.'sitemap-index.xml');
}
/* render Sitemap */
header('Content-type: application/xml; charset=utf-8');
die($out);

 ?>
