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
		$this->autoCancelOvertimeOrder();
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
		if(session('member_id'))
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
			$this->wx_auto_login();
			if (CONTROLLER_NAME != 'Login') {
				$this->error('您还未登录,请先进行登录操作.',U('Mobile/Login/index'));
// 				$this->redirect('Index/index',$_GET);//已经登录直接跳转会员中心
				exit();
			}
		}
	}

	//检查微信自动登录
	public function wx_auto_login()
	{
		$code = trim($_GET['code']);
		$state = trim($_GET['state']);
		if($code && $state)
		{
			//通过code获取用户信息
			$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.Wx_C('wx_appid').'&secret='.Wx_C('wx_secret').'&code='.$code.'&grant_type=authorization_code';
			$info = json_decode(get_url($url));
			$web_token = $info->access_token;
			$refresh_token = $info->refresh_token;
			$openid = $info->openid;
			session('wechat_openid',encrypt($openid));
			$unionid = $info->unionid;

			//检查此用户是否已经注册过
			$member_data = M('Member')->where('openid=\''.$openid.'\'')->find();
			if(is_array($member_data) && !empty($member_data))
			{
				//更新用户微信网页授权access_token
				M('Member')->where('member_id='.$member_data['member_id'])->save(array('web_token'=>$web_token,'refresh_token'=>$refresh_token));
				//授权
				session('member_id',$member_data['member_id']);
				redirect(U('Member/index'));
			}
		}else{
			$c_url = U('',$_GET,'',true); //当前地址  ERROR:该地址没有生成当前地址的参数项   导致授权之后跳转页面没有传参 已解决:2015-6-27 17:35:58
			$scope = 'snsapi_userinfo';
			$re_url = urlencode($c_url);
			$sq_url ='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.Wx_C('wx_appid').'&redirect_uri='.$re_url.'&response_type=code&scope='.$scope.'&state=STATEuserinfo#wechat_redirect';
			redirect($sq_url);
			//get_url($sq_url);
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
	protected function giveDistributionRedPacket($child_member_id,$level)
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
	protected function giveBoardReward()
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
	protected function orderShareProfit($order_id)
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
					$where['level'] = $key+1;
					$where['status'] = 1;
					$OrderShareProfit = M('OrderShareProfit')->where($where)->find();
					$rate = $OrderShareProfit['profit_rate']/100;
					//执行分润
					$result = M('Member')->where(array('member_id'=>$parents_member['member_id']))->setInc('predeposit',$profit*$rate);
					if ($result)
					{
						//TODO:写入资金日志
					}
				}
			}
		}
	}

	public function getParentsMember($member_id,$field = '*',$loop = 9999)
	{
		$array = array();
		if (!$loop)
		{
			return $array;
		}
		$loop--;
		if (!is_array($field))
		{
			$field = explode(',',$field);
		}
		if (!in_array('parent_member_id',$field))
		{
			$field[] = 'parent_member_id';
		}
		$user = M('Member')->where(array('member_id'=>$member_id))->field($field)->find();
		$parents_member[] = M('Member')->where(array('member_id'=>$user['parent_member_id']))->field($field)->find();
		$next_parents_member = $this->getParentsMember($user['parent_member_id'],$field,$loop);
		$array = array_merge($parents_member,$next_parents_member);
		return $array;
	}

	//取消超时订单
	private function autoCancelOvertimeOrder()
	{
		//取消未支付的超时订单
		$where['order_state'] = 10;
		$where['add_time'] = array('lt',time()-MSC('nopay_order_overtime'));
		$list = D('Order')->relation(true)->where($where)->select();
		foreach ($list as $key => $order)
		{
			//订单取消
			$res = M('Order')->where(array('order_id'=>$order['order_id']))->setField('order_state',60);
			if ($res)
			{
				//写入订单日志
				$log_data['order_id'] = $order['order_id'];
				$log_data['order_state'] = get_order_state_name(10);
				$log_data['change_state'] = get_order_state_name(60);
				$log_data['state_info'] = '订单超时未支付,系统自动取消.';
				$log_data['log_time'] = time()-MSC('nopay_order_overtime');//NOW_TIME;
				$log_data['operator'] = '系统';
				M('OrderLog')->add($log_data);
				//商品回库存
				foreach ($order['OrderGoods'] as $good)
				{
					M('Goods')->where(array('goods_id'=>$good['goods_id']))->setDec('goods_storage',$good['goods_num']);
					M('Goods')->where(array('goods_id'=>$good['goods_id']))->setInc('goods_freez',$good['goods_num']);
				}
			}
		}
	}

}