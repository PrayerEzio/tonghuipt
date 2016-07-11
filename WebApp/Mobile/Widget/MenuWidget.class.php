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
	public function index(){
		$this->display('Widget:Menu:index');
	}
}