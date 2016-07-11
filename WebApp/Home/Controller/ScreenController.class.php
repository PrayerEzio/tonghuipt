<?php
/**
 * 大屏幕
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Home\Controller;
use Home\Controller\BaseController;
class ScreenController extends BaseController{
	public function __construct(){
		parent::__construct();
	}
	public function index(){
		$notice_where['notice_status'] = 1;
		$notice_where['notice_type'] = 5;
		$this->notice = M('Notice')->where($notice_where)->order('notice_sort desc')->select();
		$this->display();
	}
}