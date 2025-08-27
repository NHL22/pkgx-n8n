<?php
/**
 * Auth: Ecshop Vietnam
 */

/**
 * getRequest Lấy thông tin request gần như Slim Framework
 * @param  string $uri $_SERVER['REQUEST_URI']
 * @param  string $currentPath $_SERVER['PHP_SELF']
 * @return array
 */
function ecsvn_getRequest(){
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $uri = filter_var($url, FILTER_SANITIZE_URL);
    $parse_url = parse_url($url);
    $pathInfo = pathinfo($_SERVER['PHP_SELF']);
    $dir_root = $pathInfo['dirname'];
    $ip = $_SERVER['REMOTE_ADDR'];

    //var_dump($dir_root);
    /* localhost */
    if($ip == '127.0.0.1' && $dir_root=='/'){
        $path = str_replace($dir_root, '',$parse_url['path']);
    }else{
        $path = $dir_root=='/' ? $parse_url['path'] : str_replace($dir_root, '',$parse_url['path']);
    }

    return array(
                'getBaseUrl'=> $parse_url['scheme'].'://'.$parse_url['host'].$dir_root,
                'getBasePath' => $dir_root,
                'getPath' => $path,
                'getUrl' => trim($path,'/'),
                'getQuery' => isset($parse_url['query']) ? $parse_url['query'] : '',
                'request_uri'=> $parse_url['scheme'].'://'.$parse_url['host'].($dir_root=='/' ? '' : $dir_root) .$path
                );
}
/**
 * get the query parameters as an associative array
 * @param  string $request getRequest['getQuery']
 * @return array
 */
function ecsvn_getQueryParams($request){
    if($request == ''){
        return [];
    }
    $params = array();
    if(strpos($request, '&')){
        $arr =  explode('&',$request);
        for ($i=0; $i < count($arr); $i++) {
            $arrs =  explode('=',$arr[$i]);
            if(!empty($arrs[1])){
                $params[$arrs[0]] = $arrs[1];
            }
        }
    }else{
        $arr =  explode('=',$request);
        if(!empty($arr[1])){
            $params[$arr[0]] = $arr[1];
        }
    }
    return $params;
}
/**
 * withRedirect response with status 301
 * @param  string  $uri
 * @param  integer $status
 */
function ecvn_withRedirect($uri){
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: {$uri}");
    exit;
}