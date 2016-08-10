<?php
/**
 * 基类
 * @package    Base
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Crontab\Controller;
use Think\Controller;

class BaseController extends Controller{
	public function __construct()
	{
		parent::__construct();
		//权限认证
		$client_ip = get_client_ip();
		if (empty($client_ip) || $client_ip !== C('CrontabServerIp'))
		{
			echo get_client_ip();die;
		}
	}
}