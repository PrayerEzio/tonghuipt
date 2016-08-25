<?php
/**
 * 模块公共配置
 * @package    config
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
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