<?php
/**
 * 基类
 * @package    Base
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Mobile\Controller;
use Think\Controller;

class BaseController extends Controller{
	public function __construct()
	{
		parent::__construct();
		//读取配置信息
		$web_stting = F('setting');
		if($web_stting === false) 
		{
			$params = array();
			$list = M('Setting')->getField('name,value');
			foreach ($list as $key=>$val) 
			{
				$params[$key] = unserialize($val) ? unserialize($val) : $val;
			}
			F('setting', $params); 				
			$web_stting = F('setting');
		}
		$this->assign('web_stting',$web_stting);
		//站点状态判断
		if($web_stting['site_status'] != 1){
		   echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		   echo $web_stting['closed_reason'];
		   exit;	
		}else {
			/* $link = M('Link')->where(array('status'=>1))->order('sort DESC')->select();
			$this->assign('link',$link); */
			$this->mid = session('member_id');
			$this->assign('seo',seo());
			$this->about = M('SystemArticle')->where(array('ac_type'=>'about'))->order('article_sort desc')->select();
			$this->service = M('SystemArticle')->where(array('ac_type'=>'service'))->order('article_sort desc')->select();
			$this->article_class = M('ArticleClass')->where(array('ac_parent_id'=>0))->order('ac_sort desc')->select();
		}
	}

	public function check_login(){
		if(session('member_id') || cookie('autologin'))
		{
			$this->mid = session('member_id');
			$member = M('Member')->where(array('member_id'=>$this->mid))->find();
			$this->assign('member',$member);
			if (CONTROLLER_NAME == 'Login') {
				$this->redirect('Member/index',$_GET);//已经登录直接跳转会员中心
				exit();
			}
		}else {
			if (CONTROLLER_NAME != 'Login') {
				$this->error('您还未登录,请先进行登录操作.',U('Login/index'));
// 				$this->redirect('Index/index',$_GET);//已经登录直接跳转会员中心
				exit();
			}
		}
	}

	/**
	 * 获取下级城市
	 */
	public function getDisctrict(){
		if (IS_AJAX) {
			$where['upid'] = intval($_POST['id']);
			$where['status'] = 1;
			$list = M('District')->where($where)->order('d_sort')->select();
			$data['city'] = $list;
			if ($list[0]['level'] == 2) {
				$data['level'] = 'city';
			}elseif ($list[0]['level'] == 3){
				$data['level'] = 'area';
			}
			echo json_encode($data);
		}
	}
	/**
	 * 添加新的收货地址
	 */
	public function curdAddress(){
		if (IS_POST) {
			$id = intval($_POST['addr_id']);
			$data['member_id'] = $this->mid;
			$data['name'] = str_rp($_POST['name'],1);
			$data['province_id'] = intval($_POST['province']);
			$data['city_id'] = intval($_POST['city']);
			$data['area_id'] = intval($_POST['area']);
			$data['addr'] = str_rp($_POST['addr'],1);
			$data['zip'] = intval($_POST['zip']);
			$data['mobile'] = str_rp($_POST['mobile'],1);
			$data['addr_tag'] = str_rp($_POST['addr_tag'],1);
			if ($id) {
				$rc = M('MemberAddrs')->where(array('addr_id'=>$id))->save($data);
			}else {
				$rc = M('MemberAddrs')->add($data);
			}
		}
		if (IS_AJAX) {
			$res['addr_id'] = $rc;
			$res['province'] = getDistrictName($data['province_id']);
			$res['city'] = getDistrictName($data['city_id']);
			$res['area'] = getDistrictName($data['area_id']);
			echo json_encode($res);
		}elseif (IS_POST) {
			$this->redirect('Member/address');
		}
	}
}