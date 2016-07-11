<?php 
/**
 * Banner
 * @copyright  Copyright (c) 2014-2015 muxiangdao-com Inc.(http://www.muxiangdao.com)
 * @license    http://www.muxiangdao.com
 * @link       http://www.muxiangdao.com
 * @author     muxiangdao-com Team Prayer (283386295@qq.com)
 */
namespace Home\Widget;
use Home\Controller\BaseController;
class BannerWidget extends BaseController{
	public function index(){
		$this->brand = M('GoodsBrand')->where(array('brand_status'=>1))->order('brand_sort desc')->select();
		$this->display('Widget:Banner:index');
	}
}