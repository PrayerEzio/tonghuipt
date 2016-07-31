<?php
/**
 * 首页
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Mobile\Controller\BaseController;
class IndexController extends BaseController{
	public function __construct(){
		parent::__construct();
	}
	public function index(){
		redirect(U('Shop/index'));
	}

	public function test()
	{
		$uid = intval($_GET['uid']);
		$loop = intval($_GET['loop']);
		$list = $this->getParentsMember($uid,'*',$loop);
		p($list);
	}
}