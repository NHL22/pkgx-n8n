<?php

/**
 * ECSHOP 商品批量上传、修改
 * ============================================================================
 * * 版权所有 2005-2018 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: goods_batch.php 17217 2011-01-19 06:29:08Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH.ADMIN_PATH.'/includes/lib_goods.php');

/*------------------------------------------------------ */
//-- 批量上传
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'add')
{
    /* 检查权限 */
    admin_priv('goods_batch');

    /* 取得分类列表 */
    $smarty->assign('cat_list', cat_list());

    /* 取得可选语言 */
    $dir = opendir('../languages');
    $lang_list = array(
        'UTF8'      => $_LANG['charset']['utf8']
    );
    $download_list = array();
    while (@$file = readdir($dir))
    {
        if ($file != '.' && $file != '..' && $file != ".svn" && $file != "_svn" && is_dir('../languages/' .$file) == true)
        {
            $download_list[$file] = sprintf($_LANG['download_file'], isset($_LANG['charset'][$file]) ? $_LANG['charset'][$file] : $file);
        }
    }
    @closedir($dir);
    $data_format_array = array(
                                'ecshop'    => $_LANG['export_ecshop']
                               );
    $smarty->assign('data_format', $data_format_array);
    $smarty->assign('lang_list',     $lang_list);
    $smarty->assign('download_list', $download_list);

    /* 参数赋值 */
    $ur_here = $_LANG['13_batch_add'];
    $smarty->assign('ur_here', $ur_here);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('goods_batch_add.htm');
}


elseif ($_REQUEST['act'] == 'update_exel') {
    admin_priv('goods_batch');

     header('Content-Type: text/html; charset=utf-8');
    date_default_timezone_set("Asia/Ho_Chi_Minh");
    require ROOT_PATH.ADMIN_PATH.'/lib/PHPExcel.php';

     //upload csv
    if (isset($_POST['submit_excel'])) {

        if(isset($_FILES['file2'])){
            if ($_FILES['file2']['error'] > 0)
            {
                 sys_msg('File bị lổi', 1, array(), false);

            }else{

                /* Tăng thời gian xử lí lên */
                @set_time_limit(300);

                $array = excelToArray($_FILES['file2']['tmp_name'], $header=true);

                foreach ($array as $rows) {

                    $goods = array();

                    $goods_id = intval($rows['goods_id']);

                    $goods['last_update'] = time();

                    if(isset($rows['market_price']) && $rows['market_price'] != NULL){
                        $goods['market_price'] = floatval($rows['market_price']);
                    }
                    if(isset($rows['shop_price']) && $rows['shop_price'] != NULL){
                        $goods['shop_price'] = floatval($rows['shop_price']);
                    }

                    if(isset($rows['is_new'])){
                        $goods['is_new'] = floatval($rows['is_new']);
                    }
                    if(isset($rows['is_hot'])){
                        $goods['is_hot'] = floatval($rows['is_hot']);
                    }
                    if(isset($rows['is_best'])){
                        $goods['is_best'] = floatval($rows['is_best']);
                    }
                    if(isset($rows['is_home'])){
                        $goods['is_home'] = floatval($rows['is_home']);
                    }

                    if(isset($rows['goods_name']) && $rows['goods_name'] != NULL){
                        $goods['goods_name'] = addslashes(trim($rows['goods_name']));
                    }
                    if(isset($rows['goods_sn']) && $rows['goods_sn'] != NULL){
                        $goods['goods_sn'] = addslashes(trim($rows['goods_sn']));
                    }
                    if(isset($rows['meta_title']) && $rows['meta_title'] != NULL){
                        $goods['meta_title'] = addslashes(trim($rows['meta_title']));
                    }


                    /** Các trường Option ko có cũng được */
                    if(isset($rows['keywords']) &&  $rows['keywords'] != NULL){
                        $goods['keywords'] = addslashes($rows['keywords']);
                    }
                    if(isset($rows['meta_desc']) && $rows['meta_desc'] != NULL){
                        $goods['meta_desc'] = addslashes($rows['meta_desc']);
                    }

                    if(isset($rows['goods_desc']) && $rows['goods_desc'] != NULL){
                        $goods['goods_desc'] = $rows['goods_desc'];
                    }

                    if(isset($rows['goods_brief']) && $rows['goods_brief'] != NULL){
                        $goods['goods_brief'] = $rows['goods_brief'];
                    }

                    if(isset($rows['seller_note'])){
                        $goods['seller_note'] = $rows['seller_note'];
                    }

                    if(isset($rows['goods_shopee'])){
                        $goods['goods_shopee'] = $rows['goods_shopee'];
                    }

                    if(isset($rows['deal_price']) && $rows['deal_price'] != NULL){
                        $goods['deal_price'] = intval(trim($rows['deal_price']));
                    }

                    if(isset($rows['partner_price']) && $rows['partner_price'] != NULL){
                        $goods['partner_price'] = intval(trim($rows['partner_price']));
                    }

                    if(isset($rows['brand_id'])){
                        $goods['brand_id'] = intval(trim($rows['brand_id']));
                    }

                    if(isset($rows['cat_id'])){
                        $goods['cat_id'] = intval(trim($rows['cat_id']));
                    }

                    require_once(ROOT_PATH . 'includes/cls_image.php');
                    $image = new cls_image($GLOBALS['_CFG']['bgcolor']);

                    if($rows['original_img'] != NULL){


                        $goods_old = $db->getRow("SELECT goods_img, goods_thumb, original_img FROM".$ecs->table('goods')," WHERE goods_id = $goods_id");
                        /* xóa link cũ */

                        if (!empty($goods_old['goods_thumb']))
                        {
                            $webp_goods_thumb  = convertExtension(ROOT_PATH.CDN_PATH. '/'.$goods_old['goods_thumb'], 'webp');
                            @unlink($webp_goods_thumb);
                            @unlink(ROOT_PATH.CDN_PATH.'/'. $goods_old['goods_thumb']);
                        }
                        if (!empty($goods_old['goods_img']))
                        {
                            $webp_goods_img  = convertExtension(ROOT_PATH.CDN_PATH. '/'.$goods_old['goods_img'], 'webp');
                            @unlink($webp_goods_img);
                            @unlink(ROOT_PATH.CDN_PATH.'/'. $goods_old['goods_img']);
                        }
                        if (!empty($goods_old['original_img']))
                        {
                            $webp_original_img  = convertExtension(ROOT_PATH.CDN_PATH. '/'.$goods_old['original_img'], 'webp');
                            @unlink($webp_original_img);
                            @unlink(ROOT_PATH.CDN_PATH.'/'. $goods_old['original_img']);
                        }

                        /* update hình mới */

                        $slug = $db->getOne("SELECT slug FROM".$ecs->table('slug')," WHERE id = $goods_id AND module = 'goods'");

                        $goods['original_img'] = $rows['original_img'];

                        $img_ex = explode("/",$rows['original_img']);
                        $goods_dir = ROOT_PATH .CDN_PATH.'/'.$img_ex['0'].'/'.$img_ex['1'].'/goods_img/';
                        $thumb_dir = ROOT_PATH .CDN_PATH.'/'.$img_ex['0'].'/'.$img_ex['1'].'/thumb_img/';

                        $filename_thumb  = $slug.'-thumb-'.time();
                        $filename_img    = $slug.'-G-'.time();

                        $thumb_img = $image->make_thumb(ROOT_PATH .CDN_PATH.'/'.$rows['original_img'], $GLOBALS['_CFG']['thumb_width'],$GLOBALS['_CFG']['thumb_height'], $thumb_dir, '', $filename_thumb);
                        $goods_img = $image->make_thumb(ROOT_PATH .CDN_PATH.'/'.$rows['original_img'], $GLOBALS['_CFG']['image_width'],$GLOBALS['_CFG']['image_height'], $goods_dir, '', $filename_img);



                        /* convert webp */
                        $a= $image->convertWebp(ROOT_PATH.CDN_PATH.'/'.$thumb_img);
                        $b=  $image->convertWebp(ROOT_PATH.CDN_PATH.'/'.$goods_img);



                        $goods['goods_img'] = $goods_img;
                        $goods['goods_thumb'] = $thumb_img;
                    }


                    /* Gia KM  */
                    $is_promote = (!empty($rows['is_promote']) && $rows['is_promote'] == 1) ? 1 : 0;

                    if($is_promote == 1){
                        $promote_start_date = strtotime(PHPExcel_Style_NumberFormat::toFormattedString($rows['promote_start_date'],'YYYY-MM-DD' ));
                        $promote_end_date = strtotime(PHPExcel_Style_NumberFormat::toFormattedString($rows['promote_end_date'],'YYYY-MM-DD' ));

                        $goods['is_promote'] = 1;
                        $goods['promote_price'] = floatval($rows['promote_price']);
                        $goods['promote_start_date'] = $promote_start_date;
                        $goods['promote_end_date'] = $promote_end_date;
                    }
                    else{
                        $goods['is_promote'] = 0;
                        $goods['promote_price'] = 0;
                        $goods['promote_start_date'] = 0;
                        $goods['promote_end_date'] = 0;
                    }



                    $db->autoExecute($ecs->table('goods'), $goods, 'UPDATE', "goods_id = $goods_id");


                    /* Thêm nhiều danh mục cho sản phẩm */
                    if($rows['other_cat'] != NULL){
                        $other_cat = explode("-",trim($rows['other_cat']));
                        /* loại bỏ ID chính ra khỏi other_cat nếu có */
                        if(isset($goods['cat_id']) && $goods['cat_id'] > 0){
                            $index = array_search($goods['cat_id'], $other_cat);
                            array_splice($other_cat,$index,1);
                        }

                        if (!empty($other_cat) && is_array($other_cat))
                        {
                            handle_other_cat($goods_id, array_unique($other_cat));
                        }
                    }


                    /* memmber price */
                     if(isset($rows['member_price']) && $rows['member_price'] != NULL){
                        $member_price = trim($rows['member_price']);
                        $multi = strpos($member_price,",");


                        if($multi) {
                            $arr  = explode(',',$member_price);

                            foreach ($arr as $key => $val) {

                                list($user_rank,$user_price) = explode(':',$val);

                                /* check tồn tại */
                                $count = $db->getOne("SELECT COUNT(*) FROM ".$ecs->table('member_price')." WHERE goods_id = $goods_id AND user_rank = $user_rank");
                                if( $count > 0){
                                    $payload = [
                                        'user_price'=> $user_price
                                    ];
                                    $db->autoExecute($ecs->table('member_price'), $payload, 'UPDATE', "goods_id = $goods_id AND user_rank = $user_rank");
                                }else{
                                    $payload = [
                                        'goods_id'=> $goods_id,
                                        'user_rank'=> $user_rank,
                                        'user_price'=> $user_price
                                    ];
                                    $db->autoExecute($ecs->table('member_price'), $payload, 'INSERT');
                                }


                            }

                        }else{
                            list($user_rank,$user_price) = explode(':',$member_price);
                            /* check tồn tại */
                            $count = $db->getOne("SELECT COUNT(*) FROM ".$ecs->table('member_price')." WHERE goods_id = $goods_id AND user_rank = $user_rank");
                            if( $count > 0){
                                $payload = [
                                    'user_price'=> $user_price
                                ];
                                $db->autoExecute($ecs->table('member_price'), $payload, 'UPDATE', "goods_id = $goods_id AND user_rank = $user_rank");
                            }else{
                                $payload = [
                                    'goods_id'=> $goods_id,
                                    'user_rank'=> $user_rank,
                                    'user_price'=> $user_price
                                ];
                                $db->autoExecute($ecs->table('member_price'), $payload, 'INSERT');
                            }
                        }
                    }

                }
            }
        }


        clear_cache_files(); //clear old cache
    }
  $link[] = array('href' => 'goods_batch.php?act=add', 'text' => 'Import/Update Sản phẩm');
  sys_msg('Cập nhật thành công !', 0, $link);
}
/*------------------------------------------------------ */
//-- 批量上传：处理
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'upload')
{
    /* 检查权限 */
    admin_priv('goods_batch');

    header('Content-Type: text/html; charset=utf-8');
    date_default_timezone_set("Asia/Ho_Chi_Minh");
    require ROOT_PATH.ADMIN_PATH.'/lib/PHPExcel.php';

    if(isset($_FILES['file'])){
        if ($_FILES['file']['error'] > 0)
        {
             sys_msg('File bị lổi', 1, array(), false);

        }else{

            /* Tăng thời gian xử lí lên */
            @set_time_limit(300);

            $array = excelToArray($_FILES['file']['tmp_name'], $header=true);

            /* lấy ra danh sách thương hiệu để đối chiếu */
            $brand_list = array();
            $sql = "SELECT brand_id, brand_name FROM " . $ecs->table('brand');
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $brand_list[$row['brand_id']] = $row['brand_name'];
            }

            /* tạo vòng lặp duyệt qua các dòng để lấy dữ liệu */
            $max_id = $db->getOne("SELECT MAX(goods_id) + 1 FROM ".$ecs->table('goods'));

            foreach ($array as $rows) {

                $goods = array();
                $goods['last_update'] = time();
                $goods['add_time'] = time();
                $goods['goods_number'] = 10000;

                $goods['cat_id'] = $_POST['cat'];

                if($rows['market_price'] != NULL){
                    $goods['market_price'] = intval(trim($rows['market_price']));
                }

                if($rows['shop_price'] != NULL){
                    $goods['shop_price'] = intval(trim($rows['shop_price']));
                }



                if($rows['is_new'] != NULL && intval(trim($rows['is_new'])) == 1){
                    $goods['is_new'] = 1;
                }else{
                    $goods['is_new'] = 0;
                }


                $goods['is_hot'] = ($rows['is_hot'] != NULL && intval(trim($rows['is_hot'])) == 1) ? 1 : 0;
                $goods['is_best'] = ($rows['is_best'] != NULL && intval(trim($rows['is_best'])) == 1) ? 1 : 0;
                $goods['is_home'] = ($rows['is_home'] != NULL && intval(trim($rows['is_home'])) == 1) ? 1 : 0;



                /* Gia KM  */
                $is_promote = ($rows['is_promote'] != NULL && intval(trim($rows['is_promote'])) == 1) ? 1 : 0;

                if($is_promote == 1){
                    $promote_start_date = strtotime(PHPExcel_Style_NumberFormat::toFormattedString($rows['promote_start_date'],'YYYY-MM-DD' ));
                    $promote_end_date = strtotime(PHPExcel_Style_NumberFormat::toFormattedString($rows['promote_end_date'],'YYYY-MM-DD' ));

                    $goods['is_promote'] = 1;
                    $goods['promote_price'] = intval(trim($rows['promote_price']));
                    $goods['promote_start_date'] = $promote_start_date;
                    $goods['promote_end_date'] = $promote_end_date;
                }
                else{
                    $goods['is_promote'] = 0;
                    $goods['promote_price'] = 0;
                    $goods['promote_start_date'] = 0;
                    $goods['promote_end_date'] = 0;
                }

                $goods['goods_sn']   = $rows['keywords'] == NULL ? generate_goods_sn($max_id) : addslashes($rows['goods_sn']);
                $goods['goods_name'] = addslashes(trim($rows['goods_name']));
                $goods['meta_title'] = isset($rows['meta_title']) && $rows['meta_title'] == NULL ? addslashes(trim($rows['goods_name'])) : addslashes(trim($rows['meta_title']));
                $slug = build_slug($rows['goods_name']);

                /** Các trường Option ko có cũng được */
                if($rows['keywords'] != NULL){
                    $goods['keywords'] = addslashes($rows['keywords']);
                }
                if($rows['description'] != NULL){
                    $goods['meta_desc'] = addslashes($rows['description']);
                }

                if($rows['goods_desc'] != NULL){
                    $goods['goods_desc'] = $rows['goods_desc'];
                }

                if($rows['goods_brief'] != NULL){
                    $goods['goods_brief'] = $rows['goods_brief'];
                }

                if($rows['seller_note'] != NULL){
                    $goods['seller_note'] = $rows['seller_note'];
                }

                if(isset($rows['deal_price']) && $rows['deal_price'] != NULL){
                    $goods['deal_price'] = intval(trim($rows['deal_price']));
                }

                if(isset($rows['partner_price']) && $rows['partner_price'] != NULL){
                    $goods['partner_price'] = intval(trim($rows['partner_price']));
                }

                require_once(ROOT_PATH . 'includes/cls_image.php');
                $image = new cls_image($GLOBALS['_CFG']['bgcolor']);

                if($rows['original_img'] != NULL){


                    $goods['original_img'] = $rows['original_img'];

                    $img_ex = explode("/",$rows['original_img']);
                    $goods_dir = ROOT_PATH .CDN_PATH.'/'.$img_ex['0'].'/'.$img_ex['1'].'/goods_img/';
                    $thumb_dir = ROOT_PATH .CDN_PATH.'/'.$img_ex['0'].'/'.$img_ex['1'].'/thumb_img/';
                    $filename_thumb  = $slug.'-thumb-'.time();
                    $filename_img    = $slug.'-G-'.time();

                    $thumb_img = $image->make_thumb(ROOT_PATH .CDN_PATH.'/'.$rows['original_img'], $GLOBALS['_CFG']['thumb_width'],$GLOBALS['_CFG']['thumb_height'], $thumb_dir, '', $filename_thumb);
                    $goods_img = $image->make_thumb(ROOT_PATH .CDN_PATH.'/'.$rows['original_img'], $GLOBALS['_CFG']['image_width'],$GLOBALS['_CFG']['image_height'], $goods_dir, '', $filename_img);


                    /* convert webp */
                    $a= $image->convertWebp(ROOT_PATH.CDN_PATH.'/'.$thumb_img);
                    $b=  $image->convertWebp(ROOT_PATH.CDN_PATH.'/'.$goods_img);



                    $goods['goods_img'] = $goods_img;
                    $goods['goods_thumb'] = $thumb_img;
                }

                /* nếu brand_id là số */
                if ($rows['brand_id'] != NULL)
                {
                    $brand_id = floatval($rows['brand_id']);
                    $goods['brand_id'] = isset($brand_list[$brand_id]) ? $brand_id : 0;
                }

                $db->autoExecute($ecs->table('goods'), $goods, 'INSERT');
                $goods_id = $db->insert_id();
                $max_id = $db->insert_id() + 1;

                /* CHống trùng lặp url */
                $check_slug = $db->getOne("SELECT COUNT('id_slug') FROM " .$ecs->table('slug') ." WHERE slug='$slug'");
                $slug = $check_slug > 0 ? $slug.'-'.$goods_id : $slug;

                 $insert_slug = array(
                    'id'=>  $goods_id,
                    'module'=> 'goods',
                    'slug' => $slug
                );
                $db->autoExecute($ecs->table('slug'), $insert_slug, 'INSERT');

                /* Thêm nhiều danh mục cho sản phẩm */
                if($rows['other_cat'] != NULL){
                    $other_cat = explode("-",trim($rows['other_cat']));
                    /* loại bỏ ID chính ra khỏi other_cat nếu có */
                    $index = array_search($goods['cat_id'], $other_cat);
                    array_splice($other_cat,$index,1);

                    if (!empty($other_cat) && is_array($other_cat))
                    {
                        handle_other_cat($goods_id, array_unique($other_cat));
                    }
                }

                 /* gallery */
                if($rows['gallery_img'] != NULL){

                    $gallery_list = array();
                    /* nếu tìm thấy có nhiều hình */
                    if(strpos($rows['gallery_img'], ',')){
                        $rows['gallery_img'] = preg_replace('/\s+/', '', $rows['gallery_img']);
                        $gallery_list = explode(",",$rows['gallery_img']);
                    }else{
                        $gallery_list[] = $rows['gallery_img'];
                    }


                    foreach ($gallery_list as $key => $val) {
                        $img_ex = explode("/",$val);

                        $goods_dir = ROOT_PATH .CDN_PATH.'/'.$img_ex['0'].'/'.$img_ex['1'].'/goods_img/';
                        $thumb_dir = ROOT_PATH .CDN_PATH.'/'.$img_ex['0'].'/'.$img_ex['1'].'/thumb_img/';
                        $filename_thumb  = $slug.'-Gthumb-'.$key.'-'.time();
                        $filename_img    = $slug.'-Gimg-'.$key.'-'.time();

                        $thumb_url = $image->make_thumb(ROOT_PATH .CDN_PATH.'/'.$val, $GLOBALS['_CFG']['thumb_width'],$GLOBALS['_CFG']['thumb_height'], $thumb_dir, '', $filename_thumb);
                        $img_url = $image->make_thumb(ROOT_PATH .CDN_PATH.'/'.$val, $GLOBALS['_CFG']['image_width'],$GLOBALS['_CFG']['image_height'], $goods_dir, '', $filename_img);

                        /* convert webp */
                        $image->convertWebp(ROOT_PATH.CDN_PATH.'/'.$thumb_url);
                        $image->convertWebp(ROOT_PATH.CDN_PATH.'/'.$img_url);



                        $data_gallery = array(
                            'img_url'=> $img_url,
                            'thumb_url'=>  $thumb_url,
                            'img_original'=> $val,
                            'img_desc'=> $rows['goods_name'],
                            'goods_id'=> $goods_id
                        );




                        $db->autoExecute($ecs->table('goods_gallery'), $data_gallery, 'INSERT');
                   }

                }

                 /* end gallery */

                 /* Memmber price */
                 if(isset($rows['member_price']) && $rows['member_price'] != NULL){
                    $member_price = trim($rows['member_price']);
                    $multi = strpos($member_price,",");


                    if($multi) {
                        $arr  = explode(',',$member_price);

                        foreach ($arr as $key => $val) {

                            list($user_rank,$user_price) = explode(':',$val);
                               $payload = [
                                'goods_id'=> $goods_id,
                                'user_rank'=> $user_rank,
                                'user_price'=> $user_price
                            ];

                            $db->autoExecute($ecs->table('member_price'), $payload, 'INSERT');
                        }

                    }else{
                        list($user_rank,$user_price) = explode(':',$member_price);
                        $payload = [
                            'goods_id'=> $goods_id,
                            'user_rank'=> $user_rank,
                            'user_price'=> $user_price
                        ];
                        $db->autoExecute($ecs->table('member_price'), $payload, 'INSERT');
                    }
                }
                  /* ENd Memmber price */


            } /* hết vòng lặp */

            admin_log('Up san pham bang file excel ', 'batch_upload', 'goods');
            $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']);
            sys_msg($_LANG['batch_upload_ok'], 0, $link);
            exit;
        }
    }
}

/*------------------------------------------------------ */
//-- 批量上传：入库
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'insert')
{
    /* 检查权限 */
    admin_priv('goods_batch');

    if (isset($_POST['checked']))
    {
        include_once(ROOT_PATH . 'includes/cls_image.php');
        $image = new cls_image($_CFG['bgcolor']);

        /* 字段默认值 */
        $default_value = array(
            'brand_id'      => 0,
            'goods_number'  => 0,
            'goods_weight'  => 0,
            'market_price'  => 0,
            'shop_price'    => 0,
            'warn_number'   => 0,
            'is_real'       => 1,
            'is_on_sale'    => 1,
            'is_alone_sale' => 1,
            'integral'      => 0,
            'is_best'       => 0,
            'is_new'        => 0,
            'is_hot'        => 0,
            'goods_type'    => 0,
        );

        /* 查询品牌列表 */
        $brand_list = array();
        $sql = "SELECT brand_id, brand_name FROM " . $ecs->table('brand');
        $res = $db->query($sql);
        while ($row = $db->fetchRow($res))
        {
            $brand_list[$row['brand_name']] = $row['brand_id'];
        }

        /* 字段列表 */
        $field_list = array_keys($_LANG['upload_goods']);
        $field_list[] = 'goods_class'; //实体或虚拟商品

        /* 获取商品good id */
        $max_id = $db->getOne("SELECT MAX(goods_id) + 1 FROM ".$ecs->table('goods'));

        /* 循环插入商品数据 */
        foreach ($_POST['checked'] AS $key => $value)
        {
            // 合并
            $field_arr = array(
                'cat_id'        => $_POST['cat'],
                'add_time'      => gmtime(),
                'last_update'   => gmtime(),
            );

            foreach ($field_list AS $field)
            {
                // 转换编码
                $field_value = isset($_POST[$field][$value]) ? $_POST[$field][$value] : '';

                /* 虚拟商品处理 */
                if ($field == 'goods_class')
                {
                    $field_value = intval($field_value);
                    if ($field_value == G_CARD)
                    {
                        $field_arr['extension_code'] = 'virtual_card';
                    }
                    continue;
                }

                // 如果字段值为空，且有默认值，取默认值
                $field_arr[$field] = !isset($field_value) && isset($default_value[$field]) ? $default_value[$field] : $field_value;

                // 特殊处理
                if (!empty($field_value))
                {
                    // 图片路径
                    if (in_array($field, array('original_img', 'goods_img', 'goods_thumb')))
                    {
                        if(strpos($field_value,'|;')>0)
                        {
                            $field_value=explode(':',$field_value);
                            $field_value=$field_value['0'];
                            @copy(ROOT_PATH.'images/'.$field_value.'.tbi',ROOT_PATH.'images/'.$field_value.'.jpg');
                            if(is_file(ROOT_PATH.'images/'.$field_value.'.jpg'))
                            {
                                $field_arr[$field] =IMAGE_DIR . '/' . $field_value.'.jpg';
                            }
                        }
                        else
                        {
                            $field_arr[$field] = IMAGE_DIR . '/' . $field_value;
                        }
                      }
                    // 品牌
                    elseif ($field == 'brand_name')
                    {
                        if (isset($brand_list[$field_value]))
                        {
                            $field_arr['brand_id'] = $brand_list[$field_value];
                        }
                        else
                        {
                            $sql = "INSERT INTO " . $ecs->table('brand') . " (brand_name) VALUES ('" . addslashes($field_value) . "')";
                            $db->query($sql);
                            $brand_id = $db->insert_id();
                            $brand_list[$field_value] = $brand_id;
                            $field_arr['brand_id'] = $brand_id;
                        }
                    }
                    // 整数型
                    elseif (in_array($field, array('goods_number', 'warn_number', 'integral')))
                    {
                        $field_arr[$field] = intval($field_value);
                    }
                    // 数值型
                    elseif (in_array($field, array('goods_weight', 'market_price', 'shop_price')))
                    {
                        $field_arr[$field] = floatval($field_value);
                    }
                    // bool型
                    elseif (in_array($field, array('is_best', 'is_new', 'is_hot', 'is_on_sale', 'is_alone_sale', 'is_real')))
                    {
                        $field_arr[$field] = intval($field_value) > 0 ? 1 : 0;
                    }
                }

                if ($field == 'is_real')
                {
                    $field_arr[$field] = intval($_POST['goods_class'][$key]);
                }
            }

            if (empty($field_arr['goods_sn']))
            {
                $field_arr['goods_sn'] = generate_goods_sn($max_id);
            }

            /* 如果是虚拟商品，库存为0 */
            if ($field_arr['is_real'] == 0)
            {
                $field_arr['goods_number'] = 0;
            }
            $db->autoExecute($ecs->table('goods'), $field_arr, 'INSERT');

            $max_id = $db->insert_id() + 1;

            /* 如果图片不为空,修改商品图片，插入商品相册*/
            if (!empty($field_arr['original_img']) || !empty($field_arr['goods_img']) || !empty($field_arr['goods_thumb']))
            {
                $goods_img     = '';
                $goods_thumb   = '';
                $original_img  = '';
                $goods_gallery = array();
                $goods_gallery['goods_id'] = $db->insert_id();

                if (!empty($field_arr['original_img']))
                {
                    //设置商品相册原图和商品相册图
                    if ($_CFG['auto_generate_gallery'])
                    {
                        $ext         = substr($field_arr['original_img'], strrpos($field_arr['original_img'], '.'));
                        $img         = dirname($field_arr['original_img']) . '/' . $image->random_filename() . $ext;
                        $gallery_img = dirname($field_arr['original_img']) . '/' . $image->random_filename() . $ext;
                        @copy(ROOT_PATH . $field_arr['original_img'], ROOT_PATH . $img);
                        @copy(ROOT_PATH . $field_arr['original_img'], ROOT_PATH . $gallery_img);
                        $goods_gallery['img_original'] = reformat_image_name('gallery', $goods_gallery['goods_id'], $img, 'source');
                    }
                    //设置商品原图
                    if ($_CFG['retain_original_img'])
                    {
                        $original_img                  = reformat_image_name('goods', $goods_gallery['goods_id'], $field_arr['original_img'], 'source');
                    }
                    else
                    {
                        @unlink(ROOT_PATH . $field_arr['original_img']);
                    }
                }

                if (!empty($field_arr['goods_img']))
                {
                    //设置商品相册图
                    if ($_CFG['auto_generate_gallery'] && !empty($gallery_img))
                    {
                        $goods_gallery['img_url'] = reformat_image_name('gallery', $goods_gallery['goods_id'], $gallery_img, 'goods');
                    }
                    //设置商品图
                    $goods_img                = reformat_image_name('goods', $goods_gallery['goods_id'], $field_arr['goods_img'], 'goods');
                }

                if (!empty($field_arr['goods_thumb']))
                {
                    //设置商品相册缩略图
                    if ($_CFG['auto_generate_gallery'])
                    {
                        $ext           = substr($field_arr['goods_thumb'], strrpos($field_arr['goods_thumb'], '.'));
                        $gallery_thumb = dirname($field_arr['goods_thumb']) . '/' . $image->random_filename() . $ext;
                        @copy(ROOT_PATH . $field_arr['goods_thumb'], ROOT_PATH . $gallery_thumb);
                        $goods_gallery['thumb_url'] = reformat_image_name('gallery_thumb', $goods_gallery['goods_id'], $gallery_thumb, 'thumb');
                    }
                    //设置商品缩略图
                    $goods_thumb = reformat_image_name('goods_thumb', $goods_gallery['goods_id'], $field_arr['goods_thumb'], 'thumb');
                }

                //修改商品图
                $db->query("UPDATE " . $ecs->table('goods') . " SET goods_img = '$goods_img', goods_thumb = '$goods_thumb', original_img = '$original_img' WHERE goods_id='" . $goods_gallery['goods_id'] . "'");

                //添加商品相册图
                if ($_CFG['auto_generate_gallery'])
                {
                    $db->autoExecute($ecs->table('goods_gallery'), $goods_gallery, 'INSERT');
                }
            }
        }
    }

    // 记录日志
    admin_log('', 'batch_upload', 'goods');

    /* 显示提示信息，返回商品列表 */
    $link[] = array('href' => 'goods.php?act=list', 'text' => $_LANG['01_goods_list']);
    sys_msg($_LANG['batch_upload_ok'], 0, $link);
}

/*------------------------------------------------------ */
//-- 批量修改：选择商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'select')
{
    /* 检查权限 */
    admin_priv('goods_batch');

    /* 取得分类列表 */
    $smarty->assign('cat_list', cat_list());

    /* 取得品牌列表 */
    $smarty->assign('brand_list', get_brand_list());

    /* 参数赋值 */
    $ur_here = $_LANG['15_batch_edit'];
    $smarty->assign('ur_here', $ur_here);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('goods_batch_select.htm');
}

/*------------------------------------------------------ */
//-- 批量修改：修改
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    admin_priv('goods_batch');

    /* 取得商品列表 */
    if ($_POST['select_method'] == 'cat')
    {
        $where = " WHERE goods_id " . db_create_in($_POST['goods_ids']);
    }
    else
    {
        $goods_sns = str_replace("\n", ',', str_replace("\r", '', $_POST['sn_list']));
        $sql = "SELECT DISTINCT goods_id FROM " . $ecs->table('goods') .
                " WHERE goods_sn " . db_create_in($goods_sns);
        $goods_ids = join(',', $db->getCol($sql));
        $where = " WHERE goods_id " . db_create_in($goods_ids);
    }
    $sql = "SELECT DISTINCT goods_id, goods_sn, goods_name, market_price, shop_price, goods_number, integral, give_integral, brand_id, is_real FROM " . $ecs->table('goods') . $where;
    $smarty->assign('goods_list', $db->getAll($sql));

    /* 取编辑商品的货品列表 */
    $product_exists = false;
    $sql = "SELECT * FROM " . $ecs->table('products') . $where;
    $product_list = $db->getAll($sql);

    if (!empty($product_list))
    {
        $product_exists = true;
        $_product_list = array();
        foreach ($product_list as $value)
        {
            $goods_attr = product_goods_attr_list($value['goods_id']);
            $_goods_attr_array = explode('|', $value['goods_attr']);
            if (is_array($_goods_attr_array))
            {
                $_temp = [];
                foreach ($_goods_attr_array as $_goods_attr_value)
                {
                    if( !$_goods_attr_value ) continue;
                    $_temp[] = $goods_attr[$_goods_attr_value];
                }
                $value['goods_attr'] = implode('，', $_temp);
            }

            $_product_list[$value['goods_id']][] = $value;
        }
        $smarty->assign('product_list', $_product_list);

        //释放资源
        unset($product_list, $sql, $_product_list);
    }

    $smarty->assign('product_exists', $product_exists);

    /* 取得会员价格 */
    $member_price_list = array();
    $sql = "SELECT DISTINCT goods_id, user_rank, user_price FROM " . $ecs->table('member_price') . $where;
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        $member_price_list[$row['goods_id']][$row['user_rank']] = $row['user_price'];
    }
    $smarty->assign('member_price_list', $member_price_list);

    /* 取得会员等级 */
    $sql = "SELECT rank_id, rank_name, discount " .
            "FROM " . $ecs->table('user_rank') .
            " ORDER BY discount DESC";
    $smarty->assign('rank_list', $db->getAll($sql));

    /* 取得品牌列表 */
    $smarty->assign('brand_list', get_brand_list());

    /* 赋值编辑方式 */
    $smarty->assign('edit_method', $_POST['edit_method']);

    /* 参数赋值 */
    $ur_here = $_LANG['15_batch_edit'];
    $smarty->assign('ur_here', $ur_here);

    /* 显示模板 */
    assign_query_info();
    $smarty->display('goods_batch_edit.htm');
}

/*------------------------------------------------------ */
//-- 批量修改：提交
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'update')
{
    /* 检查权限 */
    admin_priv('goods_batch');

    if ($_POST['edit_method'] == 'each')
    {
        // 循环更新每个商品
        if (!empty($_POST['goods_id']))
        {
            foreach ($_POST['goods_id'] AS $goods_id)
            {
                //如果存在货品则处理货品
                if (!empty($_POST['product_number'][$goods_id]))
                {
                    $_POST['goods_number'][$goods_id] = 0;
                    foreach ($_POST['product_number'][$goods_id] as $key => $value)
                    {
                        $db->autoExecute($ecs->table('products'), array('product_number', $value), 'UPDATE', "goods_id = '$goods_id' AND product_id = " . $key);

                        $_POST['goods_number'][$goods_id] += $value;
                    }
                }

                // 更新商品
                $goods = array(
                    'market_price'  => floatval($_POST['market_price'][$goods_id]),
                    'shop_price'    => floatval($_POST['shop_price'][$goods_id]),
                    'integral'      => intval($_POST['integral'][$goods_id]),
                    'give_integral'      => intval($_POST['give_integral'][$goods_id]),
                    'goods_number'  => intval($_POST['goods_number'][$goods_id]),
                    'brand_id'      => intval($_POST['brand_id'][$goods_id]),
                    'last_update'   => gmtime(),
                );
                $db->autoExecute($ecs->table('goods'), $goods, 'UPDATE', "goods_id = '$goods_id'");

                // 更新会员价格
                if (!empty($_POST['rank_id']))
                {
                    foreach ($_POST['rank_id'] AS $rank_id)
                    {
                        if (trim($_POST['member_price'][$goods_id][$rank_id]) == '')
                        {
                            /* 为空时不做处理 */
                            continue;
                        }

                        $rank = array(
                            'goods_id'  => $goods_id,
                            'user_rank' => $rank_id,
                            'user_price'=> floatval($_POST['member_price'][$goods_id][$rank_id]),
                        );
                        $sql = "SELECT COUNT(*) FROM " . $ecs->table('member_price') . " WHERE goods_id = '$goods_id' AND user_rank = '$rank_id'";
                        if ($db->getOne($sql) > 0)
                        {
                            if ($rank['user_price'] < 0)
                            {
                                $db->query("DELETE FROM " . $ecs->table('member_price') . " WHERE goods_id = '$goods_id' AND user_rank = '$rank_id'");
                            }
                            else
                            {
                                $db->autoExecute($ecs->table('member_price'), $rank, 'UPDATE', "goods_id = '$goods_id' AND user_rank = '$rank_id'");
                            }

                        }
                        else
                        {
                            if ($rank['user_price'] >= 0)
                            {
                                $db->autoExecute($ecs->table('member_price'), $rank, 'INSERT');
                            }
                        }
                    }
                }
            }
        }
    }
    else
    {
        // 循环更新每个商品
        if (!empty($_POST['goods_id']))
        {
            foreach ($_POST['goods_id'] AS $goods_id)
            {
                // 更新商品
                $goods = array();
                if (trim($_POST['market_price'] != ''))
                {
                    $goods['market_price'] = floatval($_POST['market_price']);
                }
                if (trim($_POST['shop_price']) != '')
                {
                    $goods['shop_price'] = floatval($_POST['shop_price']);
                }
                if (trim($_POST['integral']) != '')
                {
                    $goods['integral'] = intval($_POST['integral']);
                }
                if (trim($_POST['give_integral']) != '')
                {
                    $goods['give_integral'] = intval($_POST['give_integral']);
                }
                if (trim($_POST['goods_number']) != '')
                {
                    $goods['goods_number'] = intval($_POST['goods_number']);
                }
                if ($_POST['brand_id'] > 0)
                {
                    $goods['brand_id'] = $_POST['brand_id'];
                }
                if (!empty($goods))
                {
                    $db->autoExecute($ecs->table('goods'), $goods, 'UPDATE', "goods_id = '$goods_id'");
                }

                // 更新会员价格
                if (!empty($_POST['rank_id']))
                {
                    foreach ($_POST['rank_id'] AS $rank_id)
                    {
                        if (trim($_POST['member_price'][$rank_id]) != '')
                        {
                            $rank = array(
                                        'goods_id'  => $goods_id,
                                        'user_rank' => $rank_id,
                                        'user_price'=> floatval($_POST['member_price'][$rank_id]),
                                        );

                            $sql = "SELECT COUNT(*) FROM " . $ecs->table('member_price') . " WHERE goods_id = '$goods_id' AND user_rank = '$rank_id'";
                            if ($db->getOne($sql) > 0)
                            {
                                if ($rank['user_price'] < 0)
                                {
                                    $db->query("DELETE FROM " . $ecs->table('member_price') . " WHERE goods_id = '$goods_id' AND user_rank = '$rank_id'");
                                }
                                else
                                {
                                    $db->autoExecute($ecs->table('member_price'), $rank, 'UPDATE', "goods_id = '$goods_id' AND user_rank = '$rank_id'");
                                }

                            }
                            else
                            {
                                if ($rank['user_price'] >= 0)
                                {
                                    $db->autoExecute($ecs->table('member_price'), $rank, 'INSERT');
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // 记录日志
    admin_log('', 'batch_edit', 'goods');

    // 提示成功
    $link[] = array('href' => 'goods_batch.php?act=select', 'text' => $_LANG['15_batch_edit']);
    sys_msg($_LANG['batch_edit_ok'], 0, $link);
}

/*------------------------------------------------------ */
//-- 下载文件
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'download')
{
    /* 检查权限 */
    admin_priv('goods_batch');

    // 文件标签
    // Header("Content-type: application/octet-stream");
    header("Content-type: application/vnd.ms-excel; charset=utf-8");
    Header("Content-Disposition: attachment; filename=goods_list.csv");

    // 下载
    if ($_GET['charset'] != $_CFG['lang'])
    {
        $lang_file = '../languages/' . $_GET['charset'] . '/admin/goods_batch.php';
        if (file_exists($lang_file))
        {
            unset($_LANG['upload_goods']);
            require($lang_file);
        }
    }
    if (isset($_LANG['upload_goods']))
    {
        /* 创建字符集转换对象 */
        if ($_GET['charset'] == 'zh_cn' || $_GET['charset'] == 'zh_tw')
        {
            $to_charset = $_GET['charset'] == 'zh_cn' ? 'GB2312' : 'BIG5';
            echo ecs_iconv(EC_CHARSET, $to_charset, join(',', $_LANG['upload_goods']));
        }
        else
        {
            echo join(',', $_LANG['upload_goods']);
        }
    }
    else
    {
        echo 'error: $_LANG[upload_goods] not exists';
    }
}

/*------------------------------------------------------ */
//-- 取得商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'get_goods')
{
    $filter = new stdclass;

    $filter->cat_id = intval($_GET['cat_id']);
    $filter->brand_id = intval($_GET['brand_id']);
    $filter->real_goods = -1;
    $arr = get_goods_list($filter);

    make_json_result($arr);
}



function readCSV($filename='', $delimiter=',')
{
    if(!file_exists($filename) || !is_readable($filename))
        return FALSE;

    $header = NULL;
    $data = array();
    if (($handle = fopen($filename, 'r')) !== FALSE)
    {
        while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
        {
            if(!$header)
                $header = $row;
            else
                $data[] = array_combine($header, $row);
        }
        fclose($handle);
    }
    return $data;
}

/*
|--------------------------------------------------------------------------
| Excel To Array
|--------------------------------------------------------------------------
| Helper function to convert excel sheet to key value array
| Input: path to excel file, set wether excel first row are headers
| Dependencies: PHPExcel.php include needed
*/
function excelToArray($filePath, $header=true){
        //Create excel reader after determining the file type
        $inputFileName = $filePath;
        /**  Identify the type of $inputFileName  **/
        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        /**  Create a new Reader of the type that has been identified  **/
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        /** Set read type to read cell data onl **/
        $objReader->setReadDataOnly(true);
        /**  Load $inputFileName to a PHPExcel Object  **/
        $objPHPExcel = $objReader->load($inputFileName);
        //Get worksheet and built array with first row as header
        $objWorksheet = $objPHPExcel->getActiveSheet();
        //excel with first row header, use header as key
        if($header){
            $highestRow = $objWorksheet->getHighestRow();
            $highestColumn = $objWorksheet->getHighestColumn();
            $headingsArray = $objWorksheet->rangeToArray('A1:'.$highestColumn.'1',null, true, true, true);
            $headingsArray = $headingsArray[1];
            $r = -1;
            $namedDataArray = array();
            for ($row = 2; $row <= $highestRow; ++$row) {
                $dataRow = $objWorksheet->rangeToArray('A'.$row.':'.$highestColumn.$row,null, true, true, true);
                if ((isset($dataRow[$row]['A'])) && ($dataRow[$row]['A'] > '')) {
                    ++$r;
                    foreach($headingsArray as $columnKey => $columnHeading) {
                        $namedDataArray[$r][$columnHeading] = $dataRow[$row][$columnKey];
                    }
                }
            }
        }
        else{
            //excel sheet with no header
            $namedDataArray = $objWorksheet->toArray(null,true,true,true);
        }
        return $namedDataArray;
}

?>
