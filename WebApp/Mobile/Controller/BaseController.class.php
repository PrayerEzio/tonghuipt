<?php
/**
 * 基类
 * @package    Base
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team.
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
			$this->mid = session('member_id');
			$this->assign('seo',seo());
		}
	}

	public function check_login(){
		if(session('member_id') || cookie('autologin'))
		{
			$this->mid = session('member_id');
			$member = M('Member')->where(array('member_id'=>$this->mid))->find();
			if ($member['member_status'] != 1)
			{
				$this->error('抱歉,您的账户已被锁定,请联系网站管理员解锁.');
			}
			$this->assign('member',$member);
			if (CONTROLLER_NAME == 'Login') {
				$this->redirect('/Mobile/Member/index',$_GET);//已经登录直接跳转会员中心
				exit();
			}
		}else {
			if (CONTROLLER_NAME != 'Login') {
				$this->error('您还未登录,请先进行登录操作.',U('Mobile/Login/index'));
// 				$this->redirect('Index/index',$_GET);//已经登录直接跳转会员中心
				exit();
			}
		}
	}

	/**
	 * 获取下级城市
	 */
	public function getDisctrict(){
		$where['upid'] = intval(I('id'));//intval($_POST['id']);
		$where['status'] = 1;
		$list = M('District')->where($where)->order('d_sort')->select();
		if ($list[0]['level'] == 2) {
			$data['city'] = $list;
		}elseif ($list[0]['level'] == 3){
			$data['area'] = $list;
		}else {
			$data['province'] = $list;
		}
		echo json_encode($data);
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
				//TODO:公牌日志
			}
		}
	}

	/**
	 * 订单分润
	 */
	private function orderShareProfit($order_id)
	{
		$where['order_id'] = $order_id;
		$where['order_type'] = 1;
		$where['order_state'] = 50;
		$order = D('Order')->relation(true)->where($where)->find();
		if ($order && $order['OrderGoods'])
		{
			$profit = 0;
			foreach($order['OrderGoods'] as $key => $goods)
			{
				$profit += $goods['goods_price']-$goods['goods_cost'];
			}
			if ($profit)
			{
				$parents_member_list = $this->getParentsMember($order['member_id'],'*',3);
				foreach ($parents_member_list as $key => $parents_member)
				{
					//执行商品分润
					$rate = '';//查表
					$result = M('Member')->where(array('member_id'=>$parents_member['member_id']))->setInc('predeposit',$profit*$rate);
				}
			}
		}
	}

	public function getParentsMember($member_id,$field = '*',$loop = 9999)
	{
		if (!is_array($field))
		{
			$field = explode(',',$field);
		}
		if (!in_array('parent_member_id',$field))
		{
			$field[] = 'parent_member_id';
		}
		$array = array();
		if (!$loop)
		{
			return $array;
		}
		$loop--;
		$user = M('Member')->where(array('member_id'=>$member_id))->field($field)->find();
		$parents_member = M('Member')->where(array('member_id'=>$user['parent_member_id']))->field($field)->find();
		$parents_member = array_merge($parents_member,$this->getParentsMember($user['parent_member_id'],$field,$loop));
		return $parents_member;
	}
}