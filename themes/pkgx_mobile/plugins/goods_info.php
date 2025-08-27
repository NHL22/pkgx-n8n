<?php
/*
插件名称: 商品信息
描    述: 获取指定商品信息
作    者: David@Shopiy.com
版    本: 1.0
作者网站: http://www.shopiy.com/
*/

/*
参数说明:
id        商品id
type      url 商品链接； original_img 原始图片； goods_img 普通尺寸； goods_thumb 缩略图
*/

function siy_goods_info($atts) {
    $id = !empty($atts['id']) ? intval($atts['id']) : 0;
    $type = $atts['type'];
    if (empty($type) or $id == '0') return false;
    $item = ($type == 'url') ? 'goods_name' : $type;

    if($item == 'goods_gifts'){
        $sql = 'SELECT goods_gifts, gift_start_date, time_gift, gift_end_date  FROM '.$GLOBALS['ecs']->table('goods').' WHERE goods_id = '.$id;
        $res = $GLOBALS['db']->getRow($sql);
    }else{
        $sql = 'SELECT '.$item.' FROM '.$GLOBALS['ecs']->table('goods').' WHERE goods_id = '.$id;
        $res = $GLOBALS['db']->getOne($sql);
    }

    switch ($type) {
        case 'url':
            $str = build_uri('goods', array('gid'=>$id), $res);
            break;
        case 'goods_gifts';
            /* qua tang theo thoi han */
            $time = time();
            if($res['time_gift'] == 1){
                if($time >= $res['gift_start_date'] && $time <= $res['gift_end_date']){
                    $str   = nl2p($res['goods_gifts']);
                }else{
                    $str = '';
                }
            }else{
                $str   = nl2p($res['goods_gifts']);
            }
            break;
        case 'goods_thumb':
        case 'goods_img':
        case 'original_img':
            $str = get_image_path($id, $res);
            break;
        default:
            $str = $res;
    }
    return $str;
}
