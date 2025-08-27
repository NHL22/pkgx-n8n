<?php
if($_url == ''){
 $_module = 'index';
}elseif($_url == 'tin-tuc'){
    $_module = 'article_cat';$slug = $active_url = $_url;  $cat_id = 8;
}elseif($_url == 'tags'){
    $_module = 'article_cat';$slug = $active_url = $_url;
}elseif($_url == 'tuyen-dung'){
    $_module = 'article_cat'; $slug  = $_url;
}elseif($_url == 'tin-khuyen-mai'){
    $_module = 'article_cat'; $slug  = $_url;
}elseif($_url == 'lien-he'){
    $_module = 'message'; $slug = $_url;
}elseif($_url == 'thanh-vien'){
    $_module = 'user'; $slug = $_url;
}elseif($_url == 'ajax'){
    $_module = 'ajax';
}elseif($_url == 'gio-hang'){
    $_module = 'flow'; $slug = $_url;
}elseif($_url == 'tim-kiem'){
    $_module = 'search';
}elseif($_url == 'khuyen-mai'){
    $_module = 'topic';
}elseif($_url == 'tracking'){
    $_module = 'affiche';
}elseif($_url == 'thuong-hieu'){
    $_module = 'brand'; $slug = '';
}elseif($_url == 'so-sanh'){
    $_module = 'compare';
}elseif($_url == 'callback_payment'){
    $_module = 'respond';
}elseif($_url == 'callback_shipping'){
    $_module = 'receive';
}elseif($_url == 'tag-cloud'){
    $_module = 'tag_cloud';
}
elseif($_url == 'region'){
    $_module = 'region';
}
elseif($_url == 'danh-muc'){
    $_module = 'catalog';
}
elseif($_url == 'webhooks'){
    $_module = 'webhooks';
}elseif($_url == 'test'){
    $_module = 'test';
}
elseif($_url == 'dat-hang-nhanh'){
    $_module = 'quick_order';
}
/* Dynamic Sitemap  */
elseif($_url == 'sitemap.xml'){
    ecvn_withRedirect($ecsvn_request['getBaseUrl'].'sitemap-index.xml');
}
elseif(preg_match("/^sitemap-([a-z0-9_-]+).xml$/", $_url, $match)){
   $_module = 'sitemap'; $types = $match[1];
}
/**
 * Dynamic Route - Route động thay đổi theo slug
 * Trật tự bên dưới là có tính toán, xếp trước là match trước
 * Luôn đặt sau Static Route
 */
elseif(preg_match("/^tin-tuc\/([a-z0-9_-]+)\/([0-9]+)$/", $_url, $match)){
   $_module = 'article_cat'; $cat_id = $match[2];
}
elseif(preg_match("/^(tin-tuc|thong-tin)\/([a-z0-9_-]+)-([0-9]+).html$/", $_url, $match)){
    $_module = 'article'; $amp = false; $article_id = $match[3];
}

elseif(preg_match("/^khuyen-mai\/([a-z0-9_-]+).html$/", $_url, $match)){
   $_module = 'topic'; $slug = $match[1];
}
elseif(preg_match("/^thuong-hieu\/([a-z0-9_-]+).html$/", $_url, $match)){
   $_module = 'brand'; $slug = $match[1]; $slug_brand = '';
}
elseif(preg_match("/^([a-z0-9_-]+)\/thuong-hieu-([a-z0-9_-]+).html$/", $_url, $match)){
   $_module = 'brand'; $slug = $match[2]; $slug_brand = $match[1];
}
elseif(preg_match("/^([a-z0-9_-]+)\/hang-([a-z0-9_-]+).html$/", $_url, $match)){
    $_module = 'category'; $slug = $match[1]; $active_url = $match[1]; $slug_brand = $match[2];
}
elseif(preg_match("/^([a-z0-9_-]+).html$/", $_url, $match)){
   $_module = 'category'; $slug = $match[1]; $active_url = $match[0];
}
elseif(preg_match("/^([a-z0-9_-]+)\/([a-z0-9_-]+)$/", $_url, $match)){
    $_module = 'goods'; $slug = $match[2]; $tragop = 0;
}
elseif(preg_match("/^([a-z0-9_-]+)\/([a-z0-9_-]+)\/tra-gop$/", $_url, $match)){
    $_module = 'goods'; $slug = $match[2]; $tragop = 1;
}
else{
    $_module = '404';
}
?>