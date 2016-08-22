<?php 
/**
 * 菜单挂件
 * @copyright  Copyright (c) 2014-2015 muxiangdao-com Inc.(http://www.muxiangdao.com)
 * @license    http://www.muxiangdao.com
 * @link       http://www.muxiangdao.com
 * @author     muxiangdao-com Team Prayer (283386295@qq.com)
 */
namespace Mobile\Widget;
use Mobile\Controller\WechatController;
class MenuWidget extends WechatController{
	public function footer_nav(){
		$this->display('Widget:Menu:footer_nav');
	}

	public function order_nav()
	{
		$this->display('Widget:Menu:order_nav');
	}
}