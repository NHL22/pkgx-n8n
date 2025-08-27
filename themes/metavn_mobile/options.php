<?php
$_CFG['static_path'] = $ecsvn_request['getBaseUrl'].'/cdn/themes/'.$_CFG['template'];
$_CFG['logo'] = 'img/logo.gif';
$_CFG['links_enabled'] = true;
$_CFG['tags_enabled'] = true;
$_CFG['compare_enabled'] = true;
$_CFG['goods_popup_menu_enabled'] = true;
$_CFG['gallery_thumbnails_enabled'] = true;

$_CFG['slider_banner_height'] = false;
$_CFG['top_navigator_number'] = '6';
$_CFG['main_navigator_number'] = '9';
$_CFG['bottom_navigator_number'] = '12';
$_CFG['hide_category_extra'] = false;
$_CFG['cat_promotion_number'] = '20';
$_CFG['cat_brands_number'] = '20';
$_CFG['gallery_mode'] = 'cloud_zoom'; // ：'default','flash','color_box','cloud_zoom'。
$_CFG['display_mode_enabled'] = true;
$_CFG['display_list_enabled'] = true;
$_CFG['display_text_enabled'] = true;
$_CFG['goods_click_count_enabled'] = true;
$_CFG['no_picture'] = 'images/no_picture.gif';
$_CFG['sales_ranking_number'] = '5';
$_CFG['index_brands_number'] = '10';
$_CFG['price_zero_format'] = sprintf($GLOBALS['_CFG']['currency_format'], '0');

?>