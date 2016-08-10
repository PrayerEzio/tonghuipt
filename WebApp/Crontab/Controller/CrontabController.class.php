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
class CrontabController extends Controller{
	public function __construct(){
		parent::__construct();
		//权限认证
		$client_ip = get_client_ip();
		if ($client_ip !== '120.24.168.80')
		{
			echo get_client_ip();die;
		}
	}

	public function test()
	{
		system_log('来自Linux的curl访问','来自Linux的curl访问,时间:'.date('Y-m-d H:i:s',NOW_TIME).',Ip:'.get_client_ip());
		echo '123';
	}
}