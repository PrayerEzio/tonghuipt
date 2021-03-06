<?php
/**
 * 入口文件
 * @package    Index
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
// 应用入口文件
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',false);

// 定义应用目录
define('APP_PATH','./WebApp/');

//绝对路径
define('BasePath',str_replace('\\','/',dirname(__FILE__)));

// 引入Tp入口文件
require './ThinkYun/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单