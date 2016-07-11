<?php
/**
 * 会员中心
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Mobile\Controller\BaseController;
use Think\Page;
use Muxiangdao\DesUtils;
class MemberController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->check_login();
	}

	/**
	 * 会员中心
	 */
	public function index()
	{
		$user_info = M('member')->where(array('uid'=>$this->mid))->find();
		$this->user_info = $user_info;
		$this->display();
	}

	public function member()
	{
		if (IS_POST)
		{

		}elseif (IS_GET) {
			$user_info = M('member')->where(array('uid'=>$this->mid))->find();
			$this->user_info = $user_info;
			$this->display();
		}
	}

}