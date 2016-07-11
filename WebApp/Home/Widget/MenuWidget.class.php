<?php 
/**
 * 菜单挂件
 * @copyright  Copyright (c) 2014-2015 muxiangdao-com Inc.(http://www.muxiangdao.com)
 * @license    http://www.muxiangdao.com
 * @link       http://www.muxiangdao.com
 * @author     muxiangdao-com Team Prayer (283386295@qq.com)
 */
namespace Home\Widget;
use Home\Controller\BaseController;
class MenuWidget extends BaseController{
	public function index(){
		$this->member_type = M('Member')->where(array('member_id'=>$this->mid))->getField('member_type');
		$this->member = M('Member')->where(array('member_id'=>$this->mid))->find();
		$this->member_sevice_status = M('Notice')->where(array('notice_type'=>4))->getField('notice_status');
		$this->display('Widget:Menu:index');
	}
}