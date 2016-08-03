<?php
/**
 * 首页
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
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

	//二维码
	public function myqrcode()
	{
		$phone = trim($_GET['phone']);
		if (empty($phone))
		{
			$where['member_id'] = $this->mid;
		}else {
			$where['phone'] = $phone;
		}
		$user = M('Member')->where($where)->field('mobile')->find();
		$phone = $user['mobile'];
		$url = U('Login/register',array('invite_phone'=>$phone),true,true); //二维码内容
		$this->qrcode_img = qrcode($url,'');
		$this->display();
	}
}