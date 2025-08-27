<?php
    /**
     * Tất cả các Request Ajax đều tập trung ở đây
     * Tách ra theo act để dễ quản lí và nâng cấp thêm bớt
     */
    define('IS_AJAX', true);
    $act = isset($_params['act']) ? $_params['act'] : '';
    $type_ajax = ROOT_PATH.'includes/ajax/'.$act.'.php';
    if(file_exists($type_ajax)){
        require_once $type_ajax;
    }else{
        die(json_encode(['error'=> 1, 'content'=>"File Ajax $act not exists."]));
    }

