<?php

if ((DEBUG_MODE & 2) != 2 && $ecsvn_iscached == true )
{
    $smarty->caching = false;
}
$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
$sync = isset($_REQUEST['sync']) ? intval($_REQUEST['sync']) : 0;

if($act == 'gallery'){
	define('IS_AJAX', true);
    $result = array('error' => 0, 'message' => '', 'content' => '');
    $goods_id = intval($_REQUEST['goods_id']);
    
    $goods_img = $db->getOne('SELECT goods_img FROM '.$ecs->table('goods')." WHERE goods_id = $goods_id");

    $smarty->assign('goods_img', $goods_img);

    $smarty->assign('pictures',  get_goods_gallery($goods_id));

    $result['content'] = $smarty->fetch('library/quick_order_gallery.lbi');
    die(json_encode($result));

}elseif ($act == 'desc') {
	define('IS_AJAX', true);
    $result = array('error' => 0, 'message' => '', 'content' => '');
    $goods_id = intval($_REQUEST['goods_id']);
     $goods_desc = $db->getOne('SELECT goods_desc FROM '.$ecs->table('goods')." WHERE goods_id = $goods_id");

    $smarty->assign('goods_desc', $goods_desc);
     $result['content'] = $smarty->fetch('library/quick_order_goods_desc.lbi');
    die(json_encode($result));
}
elseif ($act == 'clearproducts' ) {
    define('IS_AJAX', true);
    $result = array('error' => 0, 'message' => '', 'content' => '');
    
    if($_SERVER["REQUEST_METHOD"] == "POST"){
        unset($_SESSION['quick_order']);
        die(json_encode($result));
    }
    $result = array('error' => 1, 'message' => 'Method invalid', 'content' => '');
    die(json_encode($result));
}
elseif ($act == 'getproducts') {
    define('IS_AJAX', true);
    $result = array('error' => 0, 'message' => '', 'content' => '');
    
    if(!isset($_SESSION['quick_order'])){
       $result = array('error' => 1, 'message' => 'Data không tồn tại', 'content' => '<li class="empty">No record !</li>');
        die(json_encode($result));
    }

    /* update số lượng */
    if(isset($_POST['type']) && $_POST['type'] == 'update'){
        $goods_id = intval($_POST['goods_id']);
        if(isset($_SESSION['quick_order'][$goods_id])){
            $_SESSION['quick_order'][$goods_id]['quantity'] = intval($_POST['quantity']);
        }
    }elseif (isset($_POST['type']) && $_POST['type'] == 'remove') {
        $goods_id = intval($_POST['goods_id']);
        if(isset($_SESSION['quick_order'][$goods_id])){
           unset($_SESSION['quick_order'][$goods_id]);
        }
    }elseif(isset($_POST['type']) && $_POST['type'] =='update_note'){
        $goods_id = intval($_POST['goods_id']);
        if(isset($_SESSION['quick_order'][$goods_id])){
            $_SESSION['quick_order'][$goods_id]['note'] = addslashes(strip_tags($_POST['note']));
        }
    }

    $total_price = 0;
    $arr = [];
    foreach ($_SESSION['quick_order'] as $goods_id => $row) {
     $total_price = $total_price + ($row['price']*$row['quantity']);
        $arr[$goods_id]['goods_name'] = $row['goods_name'];
        $arr[$goods_id]['price'] = $row['price'];
        $arr[$goods_id]['quantity'] = $row['quantity'];
        $arr[$goods_id]['note'] = $row['note'];
        $arr[$goods_id]['goods_id'] = $goods_id;
    }

    $smarty->assign('goods_list', $arr);

    $result['total_cart'] = count($_SESSION['quick_order']);
    $result['total_price'] = price_format($total_price);
    $result['content'] = $smarty->fetch('library/quick_order_selected.lbi');
    $result['data'] = $_SESSION['quick_order'];
     die(json_encode($result));
    
   
}
elseif ($act == 'addtocard') {
	define('IS_AJAX', true);
    $result = array('error' => 0, 'message' => '', 'content' => '');

    $product = [
    	'goods_id' => intval($_REQUEST['goods_id']),
    	'note' => addslashes(strip_tags($_REQUEST['note'])),
    	'quantity' => intval($_REQUEST['quantity']),
        'goods_name' => addslashes(strip_tags($_REQUEST['goods_name'])),
        'price' => intval($_REQUEST['price'])
    ];
    /* nếu chưa tồn tại */
    if(isset($_SESSION['quick_order']) && !isset($_SESSION['quick_order'][$product['goods_id']])){
    	
    	$_SESSION['quick_order'][$product['goods_id']] = $product;

    }
    /* nếu tồn tại */
    elseif(isset($_SESSION['quick_order']) && isset($_SESSION['quick_order'][$product['goods_id']])){
    	$_SESSION['quick_order'][$product['goods_id']]['quantity'] = $_SESSION['quick_order'][$product['goods_id']]['quantity'] +intval($_REQUEST['quantity']);
    	$_SESSION['quick_order'][$product['goods_id']]['note'] = addslashes(strip_tags($_REQUEST['note'])); 
    }
     /* lần đầu */
    else{
    	$arr = [];
    	$arr[$product['goods_id']] = $product;
    	$_SESSION['quick_order'] = $arr;
    }
   
    $result['quick_order'] = count($_SESSION['quick_order']);
    die(json_encode($result));
}
elseif ($act == 'addmultitocard') {
    define('IS_AJAX', true);
    $result = array('error' => 0, 'message' => '', 'content' => '');

    $goods = $_POST['goods'];

    $_SESSION['quick_order'] = isset($_SESSION['quick_order']) ? $_SESSION['quick_order'] : [];
   
    foreach ($goods as $key => $row) {

        $product = [
                    'goods_id' => intval($row['goods_id']),
                    'note' => addslashes(strip_tags($row['note'])),
                    'quantity' => intval($row['quantity']),
                    'goods_name' => addslashes(strip_tags($row['goods_name'])),
                    'price' => intval($row['price'])
                ];
        
        if(!isset($_SESSION['quick_order'][ $row['goods_id']])){
            $_SESSION['quick_order'][ $row['goods_id']] = $product;
        }else{
            $_SESSION['quick_order'][$row['goods_id']]['quantity'] = $_SESSION['quick_order'][$row['goods_id']]['quantity'] +intval($row['quantity']);
            $_SESSION['quick_order'][$row['goods_id']]['note'] = addslashes(strip_tags($row['note'])); 
        }
        



    }

    $result['data'] = $_SESSION['quick_order'];;
    die(json_encode($result));
}
elseif($act == 'checkout'){
        if(!isset($_SESSION['quick_order']) || empty($_SESSION['quick_order'])){
             show_message('Bạn chưa tạo danh sách đặt hàng');
        }
        require(ROOT_PATH . 'includes/lib_order.php');
        require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');
        require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');

        $msg = '';
        $is_error = 0;
        foreach ($_SESSION['quick_order'] as $goods_id => $row) {
            /**
             * addto_cart
             *
             * @access  public
             * @param   integer $goods_id  
             * @param   integer $num      
             * @param   array   $spec       
             * @param   integer $parent
             * @param   integer $rec_type CART_GENERAL_GOODS
             * @param   array   $attr    
             * @return  boolean
             */
           if(!addto_cart($goods_id, $row['quantity'], array(), 0, CART_GENERAL_GOODS, [$row['note']])){
                $msg .= $err->last_message();
                $is_error = 1;
           }
        }
        unset($_SESSION['quick_order']);
        unset($_SESSION['goods_list']);
        if($is_error == 1){
            show_message($msg);
        }else{
            ecs_header("Location: gio-hang\n");
        }
        exit;
    }
elseif ($act=='getnumber') {
	define('IS_AJAX', true);
    $result = array('error' => 0, 'message' => '', 'content' => '');
    $result['quick_order'] = isset($_SESSION['quick_order']) ? count($_SESSION['quick_order']) : 0;
    die(json_encode($result));
}

elseif($act == 'export_xls'){
        include ROOT_PATH.ADMIN_PATH.'/lib/PHPExcel.php';
        $objPHPExcel = new PHPExcel();

        /* đếm tổng số lượng để xác định tổng số dòng */
        $total_records = count($_SESSION['goods_list']);
        /* định vị dòng bắt đầu ghi dữ liệu danh sách sản phẩm */
        $start_row = 10;
        /* dòng kết thúc */
        $end_row = $start_row+$total_records;


        /* ======================================= */
    
        $objPHPExcel->setActiveSheetIndex(0);
        
        /* thông tin file */
        $objPHPExcel->getProperties()->setCreator("Ecshopvietnam.com")
                                 ->setLastModifiedBy("Ecshopvietnam")
                                 ->setTitle("Báo giá sỉ phụ kiện giá xưởng")
                                 ->setSubject("Báo giá sỉ phụ kiện giá xưởng")
                                 ->setDescription("Báo giá sỉ phụ kiện giá xưởng")
                                 ->setKeywords("Báo giá sỉ phụ kiện giá xưởng");

        /* Set Logo ở đầu file cho ô A2 */
        $objDrawing = new PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('Logo');
        $objDrawing->setDescription('Logo');
        $objDrawing->setPath(ROOT_PATH.'cdn/upload/files/profile/logo-phukiengiaxuong.png');
        $objDrawing->setHeight(50);
        $objDrawing->setCoordinates('A2');
        $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());


        /* Set thông tin công ty từ ô D2 */
        $objPHPExcel->getActiveSheet()
                                ->setCellValue("D2", $company_name)
                                ->setCellValue("D3", "Địa chỉ: ".$_CFG['shop_address'])
                                ->setCellValue("D4", "Hotline: ".$_CFG['service_phone']. ' - Email: '.$_CFG['service_email'])
                                ->setCellValue("D5", "Website: ".rtrim($base_path, '/'));

        $objPHPExcel->getActiveSheet()->getStyle('D2')->getFont(16)->setBold(true);
        /* Viền dưới từ ô A6-H6 */
        $objPHPExcel->getActiveSheet()->getStyle('A6:H6')->applyFromArray(
                array(
                    'borders' => array(
                        'bottom'     => array(
                            'style' => PHPExcel_Style_Border::BORDER_DOUBLE,
                            'color' => array('argb' => 'FF000000')
                        )
                    )
                )
        );

        /* Thiết lập h1 báo giá */
        $objPHPExcel->getActiveSheet()->setCellValue('C8', 'BÁO GIÁ SỈ');
        $objPHPExcel->getActiveSheet()->getStyle('C8')->getFont()->setSize(20)->setBold(true);

        /* Thiết lập tiêu đề cho bảng dữ liệu báo giá */
        $objPHPExcel->getActiveSheet()
                                ->setCellValue("A$start_row", "STT")
                                ->setCellValue("B$start_row", "Hình ảnh")
                                ->setCellValue("C$start_row", "Tên sản phẩm")
                                ->setCellValue("D$start_row", "Mã sản phẩm")
                                ->setCellValue("E$start_row", "Thương hiệu")
                                ->setCellValue("F$start_row", "Đơn giá")
                                ->setCellValue("G$start_row", "SL")
                                ->setCellValue("H$start_row", "Thành tiền");


        /*chiều rộng các cột cho hợp lý A4 ngang*/
        $objPHPExcel->getActiveSheet()->getColumnDimension("A")->setWidth(8); /* STT */
        $objPHPExcel->getActiveSheet()->getColumnDimension("B")->setWidth(12); /* thumb */
        $objPHPExcel->getActiveSheet()->getColumnDimension("C")->setWidth(46);  /* name */
        $objPHPExcel->getActiveSheet()->getColumnDimension("D")->setWidth(12); /* bao hanh */
        $objPHPExcel->getActiveSheet()->getColumnDimension("E")->setWidth(15); /* brand */
        $objPHPExcel->getActiveSheet()->getColumnDimension("F")->setWidth(12); /* don gia */
        $objPHPExcel->getActiveSheet()->getColumnDimension("G")->setWidth(8); /* So luong */
        $objPHPExcel->getActiveSheet()->getColumnDimension("H")->setWidth(12); /* Thanh tien */


        /* Bôi đậm tiêu đề bảng */
        $objPHPExcel->getActiveSheet()->getStyle("A$start_row:H$start_row")->getFont()->setBold(true);
        /* Tô nền tiêu đề bảng */
        $objPHPExcel->getActiveSheet()->getStyle("A$start_row:H$start_row")->applyFromArray(
                array(
                    'fill' => array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => 'F5F5F5')
                    )
                )
        );
        /* Canh giữa tiêu đề bảng */
        $objPHPExcel->getActiveSheet()->getStyle("A$start_row:H$start_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        /* Lặp qua các dòng dữ liệu trong mảng $array_data và tiến hành ghi dữ liệu vào file excel */
        $i = $start_row+1;
        $total = 0;
        // echo '<pre>';
        // var_dump($_SESSION['goods_list']);
        // exit;
        foreach ($_SESSION['goods_list'] as $key => $value) {
            $stt = $key+1;

            $total =  $total+$value['quantity']*$value['price'];

            $objPHPExcel->getActiveSheet()
                                        ->setCellValue("A$i", $value['stt'])
                                        ->setCellValue("C$i", $value['goods_name']."\n".strip_tags($value['note']))
                                        ->setCellValue("D$i", $value['goods_sn'])
                                        ->setCellValue("E$i", $value['brand_name'])
                                        ->setCellValue("F$i", $value['price'])
                                        ->setCellValue("G$i", $value['quantity'])
                                        ->setCellValue("H$i", $value['subtotal']);
            /* ghi hình sản phẩm */
            $objDrawing = new PHPExcel_Worksheet_Drawing();
            $objDrawing->setName('name');
            $objDrawing->setDescription($value['goods_name']);
            $objDrawing->setPath(ROOT_PATH.CDN_PATH.'/'.$value['goods_thumb']);
            $objDrawing->setWidth(50);
            $objDrawing->setOffsetY(5);
            $objDrawing->setOffsetX(15);
            $objDrawing->setCoordinates("B$i");
            $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

            $i++;
        }


    $discount_number = $i+1;
    $amount_number = $i+1;

    /* Set xuống hàng cho ô tên */
    $data_number = $start_row+1;
    $objPHPExcel->getActiveSheet()->getStyle("C$data_number:C$i")->getAlignment()->setWrapText(true)->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
    
    /* Canh giữa các ô còn lại */
    $objPHPExcel->getActiveSheet()->getStyle("A$data_number:B$i")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle("D$data_number:H$i")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);


    /* Dòng tạm tính canh phải tô đậm */
    $objPHPExcel->getActiveSheet()->setCellValue("A$i",'Tổng cộng');
    $objPHPExcel->getActiveSheet()->getStyle("A$i")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->getStyle("A$i")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
    $objPHPExcel->getActiveSheet()->mergeCells("A$i:G$i");
    $objPHPExcel->getActiveSheet()->setCellValue("H$i",$total);
    $objPHPExcel->getActiveSheet()->getStyle("H$i")->getFont()->setBold(true);



    /* Dòng quà tặng, khuyến mãi */
    $end_table = $i;
    

    /* Đóng viền cho bảng từ ô tiêu đề đến ô Tổng tiền */
    
    $objPHPExcel->getActiveSheet()->getStyle("A$start_row:H$end_table")->applyFromArray(
            array(
                'borders' => array(
                    'allborders'     => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('argb' => 'FF000000')
                    )
                )
            )
    );

    /* Dòng nhân viên */
    $admin_number = $i+2;
    $adminname_number = $admin_number+1;


    $objPHPExcel->getActiveSheet()->setCellValue("A$admin_number",'Ngày in: '.date('d-m-Y  H:i:s'));
    $objPHPExcel->getActiveSheet()->setCellValue("F$admin_number",'Người lập');
    $objPHPExcel->getActiveSheet()->getStyle("F$admin_number")->getFont()->setBold(true);

    /* Dòng ngày in */
    $printdate_number = $adminname_number+2;
    $objPHPExcel->getActiveSheet()->setCellValue("A$printdate_number"," Quý khách lưu ý: Giá bán, khuyến mại của sản phẩm và tình trạng còn hàng có thể bị thay đổi bất cứ lúc nào mà không kịp báo trước");

    /* Quy định giá sỉ */
    $dealler_number = $printdate_number + 2;
    $objPHPExcel->getActiveSheet()->setCellValue("A$dealler_number"," QUY ĐỊNH MUA SỈ");
    $objPHPExcel->getActiveSheet()->getStyle("A$dealler_number")->getFont()->setBold(true);
    $objPHPExcel->getActiveSheet()->setCellValue("A".($dealler_number+1),"» Đơn hàng tối thiểu 1 triệu / đơn hàng");
    $objPHPExcel->getActiveSheet()->setCellValue("A".($dealler_number+2),"» Hàng phụ kiện tối thiểu 5c / loại");
    $objPHPExcel->getActiveSheet()->setCellValue("A".($dealler_number+3),"» Hàng ốp lưng tối thiểu 5c / mã máy");
    $objPHPExcel->getActiveSheet()->setCellValue("A".($dealler_number+4),"» Sản phẩm trên 100k tối thiểu 3c");
    $objPHPExcel->getActiveSheet()->setCellValue("A".($dealler_number+5),"» Gửi đơn hàng sỉ khách chịu phí ship");
    $objPHPExcel->getActiveSheet()->setCellValue("A".($dealler_number+6),"» Thời gian nhận hàng có thể từ 3-5 ngày tuỳ địa điểm của khách");
    $objPHPExcel->getActiveSheet()->setCellValue("A".($dealler_number+7),"» Giao hàng toàn quốc COD ( Lần đầu tiên giao dịch yêu cầu khách chuyển cọc 500k )");

    /* Viền dưới từ ô ngày in */
    $border_number = $dealler_number+8;
    $objPHPExcel->getActiveSheet()->getStyle("A$border_number:H$border_number")->applyFromArray(
            array(
                'borders' => array(
                    'bottom'     => array(
                        'style' => PHPExcel_Style_Border::BORDER_DOUBLE,
                        'color' => array('argb' => 'FF000000')
                    )
                )
            )
    );

    /* Dòng hotline cảm ơn */
    $hotline_number = $border_number+2;
    $objPHPExcel->getActiveSheet()->setCellValue("A$hotline_number","Để biết thêm chi tiết, vui lòng liên hệ Hotline (8h00-17h30 hàng ngày)");
    $thank_number = $hotline_number+1;
    $objPHPExcel->getActiveSheet()->setCellValue("A$thank_number","Trân Trọng Cảm Ơn Quý Khách !");

        


    header('Content-Type: application/vnd.ms-excel'); //mime type
    header("Content-Disposition: attachment;filename=export-bao-gia-phu-kien-".date('d-m-Y').".xlsx"); 
    header('Cache-Control: max-age=0'); //no cache
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');  
    $objWriter->save('php://output');
    die();
  
    
}
elseif ($act=='view') {

	

	if(!isset($_SESSION['quick_order']) || empty($_SESSION['quick_order'])){
		 show_message('Bạn chưa tạo danh sách đặt hàng');
	}

	/**
	 * Update thông tin
	 */
	if($sync === 2){
		$goods_id = intval($_POST['goods_id']);
	    if(isset($_SESSION['quick_order'][$goods_id])){
	    	$_SESSION['quick_order'][$goods_id]['quantity'] = intval($_POST['quantity']);
	    }
	}
    elseif($sync === 3){
        $goods_id = intval($_POST['goods_id']);
        if(isset($_SESSION['quick_order'][$goods_id])){
            unset($_SESSION['quick_order'][$goods_id]);
        }
    }elseif ($sync === 4) {
        $goods_id = intval($_POST['goods_id']);
        if(isset($_SESSION['quick_order'][$goods_id])){
            $_SESSION['quick_order'][$goods_id]['note'] = addslashes(strip_tags($_POST['note']));
        }
    }


	$goods_id = [];
	foreach ($_SESSION['quick_order'] as $key => $value) {
		$goods_id[] = $key;
	}

	$where = db_create_in($goods_id, 'g.goods_id');
    /* lấy danh sách sản phẩm theo ID đã chọn */

    $sql = 'SELECT g.goods_id, g.goods_sn, g.cat_id, g.brand_id, g.goods_name, g.seller_note, g.deal_price, g.partner_price, g.market_price, g.is_new,g.goods_number, g.is_best, g.is_hot, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, " .
                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('member_price') . " AS mp ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
            "WHERE $where GROUP BY g.goods_id";
    $res = $db->getAll($sql);
    $arr = array();

    $total = 0;
    $stt = 1;
    foreach ($res as $key => $row) {
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

        $arr[$row['goods_id']]['goods_id']         = $row['goods_id'];
        

         $arr[$row['goods_id']]['cat_name']  = $GLOBALS['db']->getOne('SELECT cat_name FROM '.$GLOBALS['ecs']->table('category').' WHERE cat_id = '.$row['cat_id']);

         $arr[$row['goods_id']]['brand_name']  = $GLOBALS['db']->getOne('SELECT brand_name FROM '.$GLOBALS['ecs']->table('brand').' WHERE brand_id = '.$row['brand_id']);

        $arr[$row['goods_id']]['seller_note']  = nl2p(strip_tags($row['seller_note']));
        $arr[$row['goods_id']]['goods_name']             = $row['goods_name'];
        $arr[$row['goods_id']]['goods_sn']             = $row['goods_sn'];
        $arr[$row['goods_id']]['is_hot']      = $row['is_hot'];
        $arr[$row['goods_id']]['is_new']      = $row['is_new'];
        $arr[$row['goods_id']]['is_best']      = $row['is_best'];

        $arr[$row['goods_id']]['market_price']     = price_format($row['market_price']);
        $arr[$row['goods_id']]['shop_price']       = price_format($row['shop_price']);
        $arr[$row['goods_id']]['deal_price'] =  price_format($row['deal_price']);
        $arr[$row['goods_id']]['partner_price'] =  price_format($row['partner_price']);

        $arr[$row['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';
        $arr[$row['goods_id']]['goods_thumb']      = get_image_path($row['goods_id'], $row['goods_thumb'], true);
        $arr[$row['goods_id']]['url']              = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

        /* webp cho pc */
        $thumb_webp = convertExtension($row['goods_thumb'], 'webp');
        if(file_exists(ROOT_PATH.CDN_PATH.'/'.$thumb_webp)){
            $arr[$row['goods_id']]['thumb_webp'] = $thumb_webp;
        }else{
            $arr[$row['goods_id']]['thumb_webp'] = '';
        }

        $final_price = $promote_price > 0 ? $promote_price : $row['shop_price'];
        $final_price = round($final_price,0);
        $arr[$row['goods_id']]['final_price']      = round($final_price,0);
        $arr[$row['goods_id']]['price']      = $final_price;

        $sl = intval($_SESSION['quick_order'][$row['goods_id']]['quantity']);
        $arr[$row['goods_id']]['quantity']    = $sl;
        $arr[$row['goods_id']]['note']    = $_SESSION['quick_order'][$row['goods_id']]['note'];

        $arr[$row['goods_id']]['subtotal']   = $sl*$final_price;
        $total =  $total+$sl*$final_price;

        $arr[$row['goods_id']]['stt'] = $stt++;
    }

    /** để xuất excel */
    $_SESSION['goods_list'] = $arr;

    $smarty->assign('goods_list', $arr);
    $smarty->assign('total', !empty($arr) ? $total : 0);

    if($sync === 2 || $sync === 3 || $sync === 4){
    	define('IS_AJAX', true);
    	$result = array('error' => 0, 'message' => '', 'content' => '');
    	$result['content']   =  $smarty->fetch('library/quick_order_cart.lbi');
    	die(json_encode($result));
    }

    /* render */
    assign_template();
	$position = assign_ur_here(0, 'Sản phẩm đã chọn | Đặt hàng nhanh');
	$smarty->assign('page_title',       $position['title']);
	$smarty->assign('ur_here',          $position['ur_here']);
	$smarty->assign('keywords',     '');
	$smarty->assign('description', '');

	$smarty->display('quick_order_cart.dwt');

}
else{
	assign_template();
	$position = assign_ur_here(0, 'Đặt hàng nhanh');
	$smarty->assign('page_title',       $position['title']);
	$smarty->assign('ur_here',          $position['ur_here']);
	$smarty->assign('keywords',     '');
	$smarty->assign('description', '');

	$smarty->display('quick_order.dwt');

}


/*------------------------------------------------------ */
//-- PRIVATE FUNCTION
/*------------------------------------------------------ */



 ?>