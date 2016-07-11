<?php
/**
 * 首页
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Home\Controller;
use Home\Controller\BaseController;
class IndexController extends BaseController{
	public function __construct(){
		parent::__construct();
	}
	public function index(){
		$gc = I('get.gc','0','int');
		$fgc = M('GoodsClass')->where(array('gc_parent_id'=>0))->order('gc_sort desc')->getField('gc_id');
		if ($gc == 0) {
			$gc = $fgc;
		}
		$this->gc = $gc;
		$this->brand = M('GoodsBrand')->where(array('brand_status'=>1,'gc_id'=>array('in','0,'.$gc)))->order('brand_sort desc')->select();
		if ($fgc == $gc) {
			$notice_where['notice_status'] = 1;
			$notice_where['notice_type'] = 1;
			$this->notice = M('Notice')->where($notice_where)->order('notice_sort desc')->select();
			$this->display();
		}else {
			$this->display('theatre');
		}
	}
	public function fitting(){
		$this->display();
	}
}