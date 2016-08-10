<?php
/**
 * 定时任务
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Crontab\Controller;
use Think\Controller;
class CrontabController extends BaseController{
	public function __construct(){
		parent::__construct();
	}

	public function test()
	{
		system_log('来自Linux的curl访问','来自Linux的curl访问,时间:'.date('Y-m-d H:i:s',NOW_TIME).',Ip:'.get_client_ip());
	}
}