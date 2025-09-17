<?php
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

// Nạp các hàm cần thiết từ file brand.php
require_once(ROOT_PATH . 'modules/brand.php');

// Lấy các tham số từ request AJAX
$brand_id = !empty($_REQUEST['brand_id']) ? intval($_REQUEST['brand_id']) : 0;
$cate     = !empty($_REQUEST['cate']) ? intval($_REQUEST['cate']) : 0;
$page     = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
$size     = !empty($_CFG['page_size']) ? intval($_CFG['page_size']) : 10;
$sort     = !empty($_REQUEST['sort']) ? trim($_REQUEST['sort']) : 'goods_id';
$order    = !empty($_REQUEST['order']) ? trim($_REQUEST['order']) : 'DESC';
$recommend = !empty($_REQUEST['rc']) ? trim($_REQUEST['rc']) : '';
$filter_attr_str = !empty($_REQUEST['filter_attr']) ? trim($_REQUEST['filter_attr']) : '0';
$filter_attr = empty($filter_attr_str) ? '' : explode('.', $filter_attr_str);

// Xử lý logic sắp xếp cho "Mới về"
$final_sort = $sort;
$final_order = $order;
if ($recommend == 'moi-ve') {
    $final_sort = 'goods_id';
    $final_order = 'DESC';
}

// Lấy tổng số sản phẩm để tạo phân trang
$count = goods_count_by_brand($brand_id, $cate, $recommend, $filter_attr);

// Gán các biến cần thiết cho Smarty để tạo link phân trang
$smarty->assign('brand_id',         $brand_id);
$smarty->assign('category',         $cate);
$smarty->assign('recommend',        $recommend);
$smarty->assign('filter_attr',      $filter_attr_str);

// Tạo ra Pager mới
assign_pager('brand', $cate, $count, $size, $final_sort, $final_order, $page, '', $brand_id, 0, 0, '', $recommend, $filter_attr_str);

// Lấy danh sách sản phẩm
$goods_list = brand_get_goods($brand_id, $cate, $size, $page, $final_sort, $final_order, $recommend, $filter_attr);
$smarty->assign('goods_list', $goods_list);


// Trả về kết quả dạng JSON
$result = array();
$result['goods_list'] = $smarty->fetch('library/goods_siy_list.lbi'); // Đảm bảo tên file này đúng
$result['pager'] = $smarty->fetch('library/pages.lbi');

die(json_encode($result));
?>