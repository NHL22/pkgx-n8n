<?php
/** Lấy phần trăm giảm giá */
function get_discount($normal_price, $promote_price){
    return '-'.round(100-($promote_price*100/$normal_price), 0).'%';
}
/**
 * Lấy xếp hạng sao của sản phẩm
 * @param  [type] $goods_id [description]
 * @return [type]           [description]
 */
function getRank($goods_id){
    $sql = 'SELECT AVG(comment_rank) AS comment_rank, COUNT(comment_rank) AS comment_count FROM '.$GLOBALS['ecs']->table('comment').
         " WHERE id_value = $goods_id AND comment_type = 0 AND parent_id = 0 AND is_admin = 0 AND status = 1 ";
    return  $GLOBALS['db']->getRow($sql);
}

/**
 * Các hàm riêng cho Category
 */
function get_cat_child($cat_id, $parent_id){
    $cat_arr = array();
    /** Nếu hiện tại là cha, lấy con cháu */
    if($parent_id == 0){
        $sql = 'SELECT cat_id, cat_name, icon, thumb, class, parent_id ' .
            'FROM ' . $GLOBALS['ecs']->table('category') .
            "WHERE parent_id = $cat_id AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC";
    }
    /** Hiện tại là con thì lấy con, cháu của chá nó  */
    else{
        $sql = 'SELECT parent_id FROM ' . $GLOBALS['ecs']->table('category') . " WHERE cat_id = '$parent_id'";
        $pid = $GLOBALS['db']->getOne($sql);

        $parent_id = $pid > 0 ? $pid : $parent_id;

        $sql = 'SELECT cat_id, cat_name, icon, thumb, class, parent_id ' .
            'FROM ' . $GLOBALS['ecs']->table('category') .
            "WHERE parent_id = $parent_id AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC";
    }
    $res = $GLOBALS['db']->getAll($sql);
    foreach ($res AS $row) {
        $cat_arr[$row['cat_id']]['id']   = $row['cat_id'];
        $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
        $cat_arr[$row['cat_id']]['class'] = $row['class'];
        $cat_arr[$row['cat_id']]['icon'] = $row['icon'];
        $cat_arr[$row['cat_id']]['thumb'] = $row['thumb'];
        $cat_arr[$row['cat_id']]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);
        if (isset($row['cat_id']) != NULL) {
                $cat_arr[$row['cat_id']]['cat_id'] = get_cat_child($row['cat_id'], 0);
        }
        $cat_arr[$row['cat_id']]['parent'] = $parent_id;
    }
    return $cat_arr;
}


/**
 * Mã hóa số ĐT
 * 0988.xxx.042
 * 10688.xxx.344
*/
function hidetel($tel = ''){
    $leng = strlen($tel);
    $start = $leng == 10 ? 4 : 5;
    $end   = $start == 4 ? 8 : 9;
    $t1 = substr($tel,  0, $start);
    $t2 = substr($tel,  $end, $leng+1);
    return $t1.'.xxx.'.$t2;
}
function nl2p($string)
{
    $paragraphs = '';
    foreach (explode("\n", $string) as $line) {
        if (trim($line)) {
            $paragraphs .= '<p>' . $line . '</p>';
        }
    }
    return $paragraphs;
}

function utf8convert($str) {
    if(!$str) return false;
    $utf8 = array(
            'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ|Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
            'd'=>'₫|đ|Đ',
            'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ|É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
            'i'=>'í|ì|ỉ|ĩ|ị|Í|Ì|Ỉ|Ĩ|Ị',
            'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ|Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
            'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự|Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
            'y'=>'ý|ỳ|ỷ|ỹ|ỵ|Ý|Ỳ|Ỷ|Ỹ|Ỵ',
            );
    foreach($utf8 as $ascii=>$uni) $str = preg_replace("/($uni)/i",$ascii,$str);
    return $str;
 }
/**
* by Nobj
* $file = $_FILES["file"]["name"]
*/
function check_type_allow($file, $type_allows)
{
    $tmp = explode('.', $file);
    $end = end($tmp);
  return in_array($end, $type_allows);
}

/**
 * Check số điện thoại Việt Nam
 * Bắt đầu là 0, có độ dài 10,11 kí tự
 * @param  string  $tel
 * @return boolean
 */
function is_tel($tel){
    if (preg_match('/^0\d{9,10}$/i', $tel))
    {
        return true;
    }
    return false;
}


function timeAgo($time_ago)
{
    $time_ago = strtotime($time_ago);
    $cur_time   = time();
    $time_elapsed   = $cur_time - $time_ago;
    $seconds    = $time_elapsed ;
    $minutes    = round($time_elapsed / 60 );
    $hours      = round($time_elapsed / 3600);
    $days       = round($time_elapsed / 86400 );
    $weeks      = round($time_elapsed / 604800);
    $months     = round($time_elapsed / 2600640 );
    $years      = round($time_elapsed / 31207680 );

    // Seconds
    if($seconds <= 60){
        return "ít giây trước";
    }
    //Minutes
    else if($minutes <= 60){
        if($minutes==1){
            return "1 phút trước";
        }
        else{
            return "$minutes phút trước";
        }
    }
    //Hours
    else if($hours <=24){
        if($hours==1){
            return "1 giờ trước";
        }else{
            return "$hours giờ trước";
        }
    }
    //Days
    else if($days <= 7){
        if($days==1){
            return "hôm qua";
        }else{
            return "$days ngày trước";
        }
    }
    //Weeks
    else if($weeks <= 4.3){
        if($weeks==1){
            return "1 tuần trước";
        }else{
            return "$weeks tuần trước";
        }
    }
    //Months
    else if($months <=12){
        if($months==1){
            return "1 tháng trước";
        }else{
            return "$months tháng trước";
        }
    }
    //Years
    else{
        if($years==1){
            return "1 năm trước";
        }else{
            return "$years năm trước";
        }
    }
}


/**
 * ============================================
 *  function for Admin Panel                  =
 *  ===========================================
 */

/* Start Slug Seo */
function get_slug($id, $module){
    return $GLOBALS['db']->getOne("SELECT slug FROM " . $GLOBALS['ecs']->table('slug') . " WHERE id = '$id' AND module='$module'");
}
function del_slug($id, $module){
    $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('slug') . " WHERE id = '$id' AND module='$module' LIMIT 1");
}
function batch_slug($module, $sql){
    $GLOBALS['db']->query("DELETE FROM " . $GLOBALS['ecs']->table('slug') . " WHERE module='$module' AND $sql");
}

function create_slug($id, $module){
    $slug = gen_slug($module);
    $GLOBALS['db']->query("INSERT INTO " . $GLOBALS['ecs']->table('slug') . " (id, module, slug) VALUES ('$id', '$module', '$slug')");
}
function create_slug_direct($id, $slug, $module){
    $GLOBALS['db']->query("INSERT INTO " . $GLOBALS['ecs']->table('slug') . " (id, module, slug) VALUES ('$id', '$module', '$slug')");
}
function update_slug_direct($id, $slug, $modules){
    $id = $GLOBALS['db']->getOne("SELECT id FROM ". $GLOBALS['ecs']->table('slug')." WHERE id = $id AND module = '$modules'");
    if($id>0){
         $sql_seo = "UPDATE " . $GLOBALS['ecs']->table('slug') . " SET slug = '$slug' WHERE id = $id AND module='$modules' LIMIT 1";
    }else{
        $sql_seo =  "INSERT INTO " . $GLOBALS['ecs']->table('slug') . "(id,module,slug) VALUES ($id,'$modules','$slug')";
    }
    $GLOBALS['db']->query($sql_seo);
}
function update_slug($id, $modules){
    if ($_POST['slug'] != $_POST['old_slug'])
    {
        $slug = gen_slug($modules); // POST Slug

        $old_id = $GLOBALS['db']->getOne("SELECT id FROM ". $GLOBALS['ecs']->table('slug')." WHERE id = $id AND module = '$modules'");
        if($old_id>0){
             $sql_seo = "UPDATE " . $GLOBALS['ecs']->table('slug') . " SET slug = '$slug' WHERE id = $id AND module='$modules' LIMIT 1";
        }else{
            $sql_seo =  "INSERT INTO " . $GLOBALS['ecs']->table('slug') . "(id,module,slug) VALUES ($id,'$modules','$slug')";
        }
        $GLOBALS['db']->query($sql_seo);
    }
}
function gen_slug($mod){
    $slug = !empty($_POST['slug']) ? build_slug($_POST['slug']) : '';
    /* if empty Slug then build slug follow another field */
    if ($slug == '')
    {   if($mod == 'goods'){
            $slug = build_slug($_POST['goods_name']);
        }elseif ($mod == 'category') {
            $slug = build_slug($_POST['cat_name']);
        }elseif ($mod == 'article') {
            $slug = build_slug($_POST['title']);
        }elseif ($mod == 'article_cat') {
            $slug = build_slug($_POST['cat_name']);
        }elseif ($mod == 'brand') {
            $slug = build_slug($_POST['brand_name']);
        }
    }
    /* check empty input data */
    if ($slug == '')
    {
        sys_msg('URL SEO không tạo được', 1, array(), false);
        return false;
    }
    /* check exits slug */
    $check_slug = $GLOBALS['db']->getOne("SELECT COUNT('id_slug') FROM " .$GLOBALS['ecs']->table('slug') ." WHERE slug='$slug'");
    if ($check_slug > 0)
    {
        sys_msg("URL SEO '$slug' đã tồn tại !", 1, array(), false);
        return false;
    }
    return $slug;
}
/* ENd Slug Seo */