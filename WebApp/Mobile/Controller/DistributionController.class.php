<?php
/**
 * 分销
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Mobile\Controller\BaseController;
use Think\Page;
use Muxiangdao\DesUtils;
class DistributionController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->check_login();
	}

	/**
	 *
	 */
	public function index()
	{
		$user_info = M('member')->where(array('uid'=>$this->mid))->find();
		$this->user_info = $user_info;
		$this->display();
	}
}