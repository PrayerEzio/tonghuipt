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
use Muxiangdao\Emoji;

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
		$this->mid = session('member_id');
		/*$admin_id = array(36,37,89);
		if(!in_array($this->mid,$admin_id)){
			echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
			echo $this->web_stting['closed_reason'];
			exit;
		}*/
		if($web_stting['site_status'] != 1){
		   echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		   echo $web_stting['closed_reason'];
		   exit;	
		}else {
			$this->assign('seo',seo());
			//JS-SDK
			$signPackage = wx_js_sdk();
			$this->assign('signPackage',$signPackage);
			$seo = M('Seo');
			$module = MODULE_NAME;
			$controller = CONTROLLER_NAME;
			$action = ACTION_NAME;
			$cavalue = $module.'/'.$controller.'/'.$action;
			$seo_info = $seo->where(array('cavalue'=>$cavalue))->find();
			if (empty($seo_info)) {
				$cavalue = $module.'/'.$controller.'/*';
				$seo_info = $seo->where(array('cavalue'=>$cavalue))->find();
				if (empty($seo_info)) {
					$cavalue = $module.'/*';
					$seo_info = $seo->where(array('cavalue'=>$cavalue))->find();
				}
				if (empty($seo_info)) {
					$seo_info = $seo->where(array('type'=>1))->find();
				}
			}
			$shareConfig['title'] = $seo_info['title'];
			$shareConfig['desc'] = $seo_info['description'];
			$shareConfig['linkUrl'] = C('SiteUrl').U('',I());
			$shareConfig['imgUrl'] = C('SiteUrl').'/Uploads/'.MSC('wx_share_img');
			$this->shareConfig = $shareConfig;
		}
	}

	public function check_login(){
		if(session('member_id'))
		{
			$this->mid = session('member_id');
			$member = M('Member')->where(array('member_id'=>$this->mid))->find();
			if ($member['member_status'] != 1)
			{
				session('member_id',null);
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
	 * @param $order_id
	 * @param $level
	 */
	protected function giveDistributionRedPacket($order_id,$level)
	{
		$order = M('Order')->where(array('order_id'=>$order_id,'order_type'=>4))->find();
		if (empty($order))
		{
			return '';
		}
		if ($level == 2)
		{
			$where['reward_type'] = 'gift_distribution';
		}else {
			$where['reward_type'] = 'distribution';
		}
		$where['level'] = array('elt',$level);
		$red_packet_list = M('RedPacket')->where($where)->order('level desc')->select();
		$mid = $order['member_id'];
		$member_info = M('Member')->where(array('member_id'=>$mid))->find();
		$agent_level = M('AgentInfo')->where(array('agent_id'=>$member_info['agent_id']))->getField('agent_level');
		$agent_name = get_agent_level($agent_level);
		$member_nickname = get_member_nickname($mid);
		foreach ($red_packet_list as $key => $item) {
			$member_where['member_id'] = $mid;
			$member = M('Member')->where($member_where)->field('parent_member_id')->find();
			if ($member['parent_member_id'])
			{
				$agent_id = M('Member')->where(array('member_id'=>$member['parent_member_id']))->getField('agent_id');
				$agent_level = M('AgentInfo')->where(array('agent_id'=>intval($agent_id)))->getField('agent_level');
				if ($key < intval($agent_level))
				{
					$res = M('Member')->where(array('member_id'=>$member['parent_member_id']))->setInc('predeposit',$item['reward_price']);
					if ($res)
					{
						$open_id = M('Member')->where(array('member_id'=>$member['parent_member_id']))->getField('openid');
						$member_level_ch = ch_num($key+1);
						if ($open_id)
						{
							$data['touser'] = $open_id;
							$data['template_id'] = trim('kJSVapgJEGqrE5XoePDKpe0b2mq1_vv1AZjbN2_SI_Y');
							$data['url'] = C('SiteUrl').U('Member/bill',array('bill_type'=>1));
							$data['data']['first']['value'] = '亲，您的'.$member_level_ch.'级会员-'.$member_nickname.'已购买成功！您的分成如下：';
							$data['data']['first']['color'] = '#173177';
							$data['data']['keyword1']['value'] = $order['order_sn'];
							$data['data']['keyword1']['color'] = '#173177';
							$data['data']['keyword2']['value'] = price_format($order['order_amount']).'元';
							$data['data']['keyword2']['color'] = '#173177';
							$data['data']['keyword3']['value'] = price_format($item['reward_price']).'元';
							$data['data']['keyword3']['color'] = '#173177';
							$data['data']['keyword4']['value'] = date('Y年m月d日 H:i',time());
							$data['data']['keyword4']['color'] = '#173177';
							$data['data']['remark']['value'] = '【泰鑫国际】感谢有您，客服：894916947';
							$data['data']['remark']['color'] = '#173177';
							sendTemplateMsg($data);
						}
						$bill['member_id'] = $member['parent_member_id'];
						$bill['bill_log'] = '来自'.$member_level_ch.'级会员-'.$member_nickname.'['.$agent_name.']的分销红包收益';
						$bill['amount'] = $item['reward_price'];
						$bill['balance'] = M('Member')->where(array('member_id'=>$member['parent_member_id']))->getField('predeposit');
						$bill['addtime'] = NOW_TIME;
						$bill['bill_type'] = 1;
						$bill['channel'] = 5;
						M('MemberBill')->add($bill);
					}
				}
				$mid = $member['parent_member_id'];
			}else {
				break;
			}
		}
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
				//返还预先扣除的积分
				if ($order['cost_points'])
				{
					$point_res = M('Member')->where(array('member_id'=>$order['member_id']))->setInc('point',$order['cost_points']);
					if (!$point_res)
					{
						system_log('超时订单自动取消,但为返还用户动态.','超时订单自动取消,但为返还用户积分.订单id:'.$order['order_id'],10);
					}
				}
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

	public function getWechatInfo()
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
			}else{
				//未关注
				if($state == 'STATEuserinfo')
				{
					$get_userinfo_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$web_token.'&openid='.$openid.'&lang=zh_CN';
					$user = json_decode(get_url($get_userinfo_url));
				}else{
					//已关注
					$access_token = get_wx_AccessToken(1);
					$get_user_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
					$user = json_decode(get_url($get_user_url));
				}

				if($user->nickname)
				{
					$member = M('Member')->where(array('member_id'=>$this->mid))->find();
					$data = array();
					//转义emoji
					$emoji = new Emoji();
					$data['nickname'] = $member['nickname'] ? $member['nickname'] : $emoji->emoji_unified_to_html($user->nickname);
					$data['wechat'] = $member['wechat'] ? $member['wechat'] : $emoji->emoji_unified_to_html($user->nickname);
					$data['openid'] = $user->openid;
					$data['gender'] = $member['gender'] ? $member['gender'] : $user->sex;
					$data['country'] = $member['country'] ? $member['country'] : $user->country;
					$data['province'] = $member['province'] ? $member['province'] : $user->province;
					$data['city'] = $member['city'] ? $member['city'] : $user->city;
					$data['usercity'] = $member['usercity'] ? $member['usercity'] : $user->city;
					$data['avatar'] = $member['avatar'] ? $member['avatar'] : $user->headimgurl;
					$data['unionid'] = $user->unionid;
					$data['web_token'] = $web_token;
					$data['refresh_token'] = $refresh_token;
					$data['register_time'] = NOW_TIME;
					//$return = M('Member')->add($data);
					$return = M('Member')->where(array('member_id'=>$this->mid))->save($data);
					session('member_id',$member['member_id']);
					session('wechat',true);
				}
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

}