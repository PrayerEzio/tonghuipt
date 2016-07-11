<?php 
/**
 * 头部挂件
 * @copyright  Copyright (c) 2014-2015 muxiangdao-com Inc.(http://www.muxiangdao.com)
 * @license    http://www.muxiangdao.com
 * @link       http://www.muxiangdao.com
 * @author     muxiangdao-com Team Prayer (283386295@qq.com)
 */
namespace Home\Widget;
use Home\Controller\BaseController;
class TopWidget extends BaseController{
	public function index(){
		$this->display('Widget:Top:index');
	}
}