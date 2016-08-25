<?php
/**
 * 手机模块设置
 * @copyright  Copyright (c) 2014-2015 muxiangdao-com Inc.(http://www.muxiangdao.com)
 * @license    http://www.muxiangdao.com
 * @link       http://www.muxiangdao.com
 * @author     muxiangdao-com Team Prayer (283386295@qq.com)
 */
return array(
	
    /* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
	    '__UPLOADS__'=> __ROOT__ . '/Uploads',
		'__THUMB__' => __ROOT__ . '/Uploads/thumb',
        '__STATIC__' => __ROOT__ . '/Public/static',
        '__IMG__'    => __ROOT__ . '/Public/'.MODULE_NAME.'/images',
        '__CSS__'    => __ROOT__ . '/Public/'.MODULE_NAME.'/css',
        '__JS__'     => __ROOT__ . '/Public/'.MODULE_NAME.'/js',
    	'__FONT__'	 => __ROOT__ . '/Public/'.MODULE_NAME.'/font',
    ),

	'TMPL_ACTION_ERROR'     =>  MODULE_PATH.'/View/Public/dispatch_jump.html', // 默认错误跳转对应的模板文件
	'TMPL_ACTION_SUCCESS'   =>  MODULE_PATH.'/View/Public/dispatch_jump.html', // 默认成功跳转对应的模板文件
	
);