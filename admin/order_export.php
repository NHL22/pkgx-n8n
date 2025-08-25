<?php

if($_REQUEST['act'] == 'order_export'){
    if(empty($_POST)){

        $smarty->assign('full_page',    true);
        $smarty->assign('ur_here',        '订单导出');
        $smarty->display('order_export.htm');
    }
    else{
        admin_priv('order_view_finished');    // 权限，查看已完成订单

        extract($_POST);
        if(! in_array($file_ext, array('csv', 'xls', 'xlsx'))) exit('Incorrect mime type of your download.');

        $time_type = in_array($time_type, array('shipping_time', 'pay_time', 'confirm_time')) ? $time_type : 'shipping_time';
        $colNames = array();

        $sql = "SELECT ";
        if(isset($col['order_sn'])){
            $sql .= ' i.order_sn,';
            $colNames[] = 'NO.';
        }
        if(isset($col['goods_amount'])){
            $sql .= ' i.goods_amount,';
            $colNames[] = 'Amount';
        }
        if(isset($col['money_paid'])){
            $sql .= ' i.money_paid,';
            $colNames[] = 'Paid';
        }

        $sql .= " 'EUR' AS currency,";
        $colNames[] = 'Currency';


        if(isset($col['is_refund'])){
            $sql .= ' IFNULL(l.is_refund, 0),';
            $colNames[] = 'Refunded';
        }
        if(isset($col['goods_name'])){
            if($sort_type == 'list_goods'){
                $sql .= ' g.goods_name, g.goods_number,';
                $colNames[] = 'Goods Name';
                $colNames[] = 'Goods Qty';
            }
            else{
                $sql .= " GROUP_CONCAT(CONCAT(g.goods_name, ' * ', g.goods_number) SEPARATOR ', '),";
                $colNames[] = 'Goods Items';
            }
        }

        if(isset($col['user_name'])){
            $sql .= ' u.user_name,';
            $colNames[] = 'User';
        }
        if(isset($col['email'])){
            $sql .= ' u.email,';
            $colNames[] = 'User Email';
        }
        if(isset($col['pay_name'])){
            $sql .= ' i.pay_name,';
            $colNames[] = 'Payment name';
        }
        if(isset($col['payer_account'])){
            $sql .= ' l.payer_account,';
            $colNames[] = 'Payer Account';
        }

        $sql .= " FROM_UNIXTIME(i.pay_time, '%Y-%m-%d, %H:%i:%s'), FROM_UNIXTIME(i.shipping_time, '%Y-%m-%d, %H:%i:%s') FROM ";
        $colNames[] = 'Pay time';
        $colNames[] = 'shipping time';

        if($sort_type == 'list_goods'){
            $sql .= $ecs->table('order_goods') . " g LEFT JOIN " . $ecs->table('order_info') . " i ON i.order_id=g.order_id ";
        }
        else{
            $sql .= $ecs->table('order_info') . " i LEFT JOIN " . $ecs->table('order_goods') . " g ON i.order_id=g.order_id ";
        }
        $sql .= " LEFT JOIN ". $ecs->table('pay_log') . " l ON l.order_id=i.order_id
                  LEFT JOIN ". $ecs->table('users') . " u ON u.user_id=i.user_id";

        // 条件：订单状态
        $osSet = array(
                'shipped'        => 'i.shipping_status=1',
                'paid'            => 'i.pay_status='. PS_PAYED,
                'refunded'        => '(i.order_status='. OS_RETURNED . " OR l.is_refund=1)",
                'unconfirmed'    => 'i.order_status='. OS_UNCONFIRMED
            );
        $order_status_str = isset($osSet[$order_status]) ? $osSet[$order_status] : $osSet['shipped'];
        $sql .= " WHERE ". $order_status_str;

        // 条件：时间区间
        $timeSince    = local_strtotime($time_since);
        $timeTo        = local_strtotime($time_to);
        $filename = 'orders';
        if($timeSince || $timeTo){
            $sql .= $timeSince > 0 ? " AND i.{$time_type}>={$timeSince}" : "";
            $sql .= $timeTo > 0 ? " AND i.{$time_type}<{$timeTo}" : "";
            $filename .= $timeSince > 0 ? date('_[Y-m-d H-i]', $timeSince) : '';
            $filename .= $timeTo > 0 ? date('_[Y-m-d H-i]', $timeTo) : '';
        }

        // 集合
        $sql .= $sort_type == 'list_goods' ? "" : " GROUP BY g.order_id";

        // 排序
        $sql .= " ORDER BY i.{$time_type} " . strtoupper($order_by);

        $result = $db->getAll($sql);
        excel_output($colNames, $result, $filename, $file_ext);
    }
}


/*
 * 输出表格文件
 * @param array $colNames        列名数组
 * @param array $datas            内容行多维数组
 * @param string $filename        文件名
 * @param string $ext            文件格式
 */
function excel_output($colNames = array(), $datas = array(), $filename = 'list', $ext = 'csv'){
    require_once ROOT_PATH . 'includes/modules/excel/PHPExcel.php';
    $excel = new PHPExcel();
    $excel->getProperties();
    $excel->setActiveSheetIndex(0);
    $sheet = & $excel->getActiveSheet();

    $cellStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    foreach ($colNames as $n => $name){
        $sheet->setCellValue($cellStr{$n} . 1, $name);
    }

    foreach ($datas as $y => $row){
        $x = 0;
        foreach($row as $val){
            $sheet->setCellValue($cellStr{$x} . ($y + 2), $val);
            $x++;
        }
    }

    if($ext == 'xlsx'){
        $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $writerType = 'Excel2007';
    }
    elseif($ext == 'csv'){
        $mime = "text/comma-separated-values";
        $writerType = 'CSV';
    }
    else{
        $mime = "application/vnd.ms-excel";
        $writerType = 'Excel5';
    }

    header("Content-Type: $mime;");
    header('Content-Disposition: attachment;filename="' . $filename . '.' . $ext . '"');
    header('Cache-Control: max-age=0');

    $objWriter = PHPExcel_IOFactory::createWriter($excel, $writerType);
    $objWriter->save('php://output');
    exit;
}

 ?>