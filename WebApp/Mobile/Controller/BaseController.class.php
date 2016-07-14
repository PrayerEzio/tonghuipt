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
	 * 发放分销红包
	 * @param $child_member_id
	 * @param $level
	 */
	private function giveDistributionRedPacket($child_member_id,$level)
	{
		$where['reward_type'] = 'distribution';
		$where['level'] = array('elt',$level);
		$red_packet_list = M('RedPacket')->where($where)->order('level asc')->select();
		$mid = $child_member_id;
		foreach ($red_packet_list as $key => $item) {
			$member_where['member_id'] = $mid;
			$member = M('Member')->where($member_where)->field('parent_member_id')->find();
			if ($member['parent_member_id'])
			{
				$res = M('Member')->where(array('member_id'=>$member['parent_member_id']))->setInc('predeposit',$item['reward_price']);
				if ($res)
				{
					//TODO:资金日志
				}
				$mid = $member['parent_member_id'];
			}else {
				break;
			}
		}
	}

	/**
	 * 发放公牌奖励
	 */
	private function giveBoardReward()
	{
		$board_reward = MSC('board_reward');
		$where['board_status'] = 0;
		$where['differ_num'] = array('neq',0);
		$where['finish_time'] = 0;
		$board_info = M('Board')->where($where)->order('create_time')->find();
		$res = M('Member')->where(array('member_id'=>$board_info['member_id']))->setInc('predeposit',$board_reward);
		if ($res)
		{
			//TODO:资金日志
			//更新公牌数据库
			$data['finish_num'] = $board_info['finish_num']++;
			$data['differ_num'] = $board_info['differ_num']--;
			if ($board_info['expect_num'] == $data['finish_num'] || $data['differ_num'] == 0)
			{
				$data['board_status'] = 1;
				$data['finish_time'] = time();
			}
			$update_res = M('Board')->where(array('board_id'=>$board_info['board_id']))->save($data);
			if ($update_res)
			{
<<<<<<< HEAD
				//TODO:公牌日志
=======
				//TODO:公牌日志.
>>>>>>> a4f8b91265a06179c658f6a0b128a34bb05da76b
			}
		}
	}
}