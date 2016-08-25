<?php
/**
 * 首页模块设置
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
	
);