<?php
/**
 * 会员中心
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Muxiangdao\DesUtils;
use Muxiangdao\Emoji;
use Think\Page;

class MemberController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->check_login();
        //检查登录
        //session('wechat_openid',null);
        $this->m_info = M('Member')->where('member_id='.$this->mid)->find();
		if (empty($this->m_info['openid']))
		{
			$this->getWechatInfo();
		}
        $this->autoFinishOrder();
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

	/**
	 * 会员中心.
	 */
	public function index()
	{
		$where['member_id'] = $this->mid;
		$user_info = D('Member')->relation(true)->where($where)->find();
		$this->user_info = $user_info;
		$this->display();
	}

	//登出
	public function logout()
	{
		session('member_id',null);
		session('wechat_openid',null);
		session('wechat',null);
		redirect(U('Index/index'));
	}

	//个人资料
	public function info()
	{
		if (IS_POST)
		{
			$data['nickname'] = trim($_POST['nickname']);
			$data['member_name'] = trim($_POST['member_name']);
			$data['gender'] = intval($_POST['gender']);
			$data['province'] = trim($_POST['province']);
			$data['city'] = trim($_POST['city']);
			$data['area'] = trim($_POST['area']);
			if(!empty($_FILES['avatar']['size'])){
				$arc_img = 'mid_avatar_'.$this->mid;
				$param = array('savePath'=>'member/','subName'=>'','files'=>$_FILES['avatar'],'saveName'=>$arc_img,'saveExt'=>'');
				$up_return = upload_one($param);
				if($up_return == 'error'){
					$this->error('图片上传失败');
					exit;
				}else{
					$data['avatar'] = C('SiteUrl').'/Uploads/'.$up_return;
				}
			}
			$data['wechat'] = trim($_POST['wechat']);
			$data['alipay'] = trim($_POST['alipay']);
			$res = M('Member')->where(array('member_id'=>$this->mid))->save($data);
			$this->success('修改资料成功');
		}elseif (IS_GET) {
			$user_info = M('Member')->where(array('member_id'=>$this->mid))->find();
			$province = M('District')->where(array('level'=>1,'status'=>1))->order('d_sort')->select();
			$this->province = $province;
			$this->user_info = $user_info;
			$this->display();
		}
	}

	//我的团队
	public function branch()
	{
		if (IS_POST)
		{

		}elseif (IS_GET){
			$all_list = M('Member')->select();
			$where['parent_member_id'] = $this->mid;
			$list = M('Member')->where($where)->order('register_time')->select();
			$field = 'member_id,parent_member_id';
			$loop = 9;
			foreach ($list as $key => $item)
			{
				$list[$key]['branch_num'] = count($this->getChildsMember($item['member_id'],$field,$loop));//count(getChildsId($all_list,$item['member_id'],'member_id','parent_member_id',$loop));//
			}
			$this->branch_num = count($this->getChildsMember($this->mid,$field,$loop));//count(getChildsId($all_list,$this->mid,'member_id','parent_member_id',$loop));//
			$this->list = $list;
			$this->display();
		}
	}

	//获取子级id
	private function getChildsMember($parent_member_id,$field = '*',$loop = 9999)
	{
		$array = array();
		if (!$loop)
		{
			return $array;
		}
		$loop--;
		$array = M('Member')->where(array('parent_member_id'=>$parent_member_id))->field($field)->select();
		if (!empty($array))
		{
			$child_array = array();
			foreach ($array as $v){
				$child = $this->getChildsMember($v['member_id'],$field,$loop);
				if ($child)
				{
					$child_array = array_merge($child_array,$child);
				}
			}
			$array = array_merge($array, $child_array);
			return $array;
		}else {
			return $array;
		}
	}

	//账单
	public function bill()
	{
		$bill_type = intval($_GET['type']);
		$where['bill_type'] = $bill_type;
		$where['member_id'] = $this->mid;
		$count = M('MemberBill')->where($where)->count();
		$page = new Page($count,10);
		$list = M('MemberBill')->where($where)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();
		$this->list = $list;
		$this->display();
	}

	//站内信
	public function letter()
	{
		$where['member_id'] = $this->mid;
		$count = M('MemberLetter')->where($where)->count();
		$page = new Page($count,10);
		$list = M('MemberLetter')->where($where)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();
		$this->list = $list;
		$this->display();
	}

	public function agent()
	{
		if (IS_POST)
		{
			if ($this->mid != 36 && $this->mid != 37)
			{
				$this->error('公排系统即将开放,敬请期待.'.$this->mid);
			}
			$where['agent_id'] = intval($_POST['radio1']);
			$where['agent_status'] = 1;
			$agent_info = M('AgentInfo')->where($where)->find();
			$max_level = M('AgentInfo')->Max('agent_level');
			//加入判断
			if ($agent_info['agent_level'] == $max_level)
			{
				$count = M('Board')->where(array('board_status'=>0,'member_id'=>$this->mid))->count();
				if ($count)
				{
					$this->error('您的公排系统还在结算,请勿重复购买.');
				}
			}else {
				$my_max_level_where['member_id'] = $this->mid;
				$my_max_level = M('Agent')->where($my_max_level_where)->Max('agent_level');
				if ($my_max_level != $max_level && $my_max_level >= $agent_info['agent_level'])
				{
					$this->error('您不能购买更低级的代理级别.');
				}
				if ($my_max_level == $max_level && $my_max_level > $agent_info['agent_level'])
				{
					$this->error('您不能购买更低级的代理级别.');
				}
			}
			if ($agent_info)
			{
				//生成订单并跳转
				$order['order_sn'] = order_sn();
				$order['member_id'] = $this->mid;
				$order['order_type'] = 4;
				$order['order_param'] = $agent_info['agent_id'];
				$order['payment_id'] = 4;
				switch (intval($_POST['pay_type'])){
					case 1:$order['payment_name'] = 'alipay';break;
					case 2:$order['payment_name'] = 'bdpay';break;
					case 3:$order['payment_name'] = 'wxpay';break;
					default : $order['payment_name'] = 'undefine';break;
				}
				$order['order_points'] = $agent_info['get_points'];
				$order['cost_points'] = $agent_info['cost_points'];
				$order['goods_amount'] = 0.01;//$agent_info['price'];
				$order['discount'] = 0;
				$order['order_amount'] = 0.01;//$agent_info['price'];
				$order['order_state'] = 10;
				$order['add_time'] = NOW_TIME;
				$res = M('Order')->add($order);
				if ($res)
				{
					//进行支付跳转
					switch (intval($_POST['pay_type'])){
						case 1:$this->success('订单生成成功',U('Pay/alipay',array('order_sn'=>$order['order_sn'])));break;
						case 2:$this->success('订单生成成功',U('Pay/bdpay',array('order_sn'=>$order['order_sn'])));break;
						case 3:$this->success('订单生成成功',U('Pay/wxpay',array('order_sn'=>$order['order_sn'])));break;
					}
				}else {

				}
			}else {
				//报错
			}
		}elseif (IS_GET)
		{
			$this->list = M('AgentInfo')->where(array('agent_status'=>1))->order('agent_sort desc,agent_level desc')->select();
			$where['member_id'] = $this->mid;
			$user_info = D('Member')->relation(true)->where($where)->find();
			$this->user_info = $user_info;
			$this->display();
		}
	}

	//订单自动完成
	private function autoFinishOrder()
	{
		$where['auto_finish_time'] = array('between',array(1,time()));
		$where['order_state'] = array('between',array(31,49));
		$order_list = M('Order')->where($where)->select();
		foreach ($order_list as $key => $order)
		{
			$where['order_id'] = $order['order_id'];
			$order = D('Order')->relation('OrderGoods')->where($where)->find();
			//变更交易状态
			$res = M('Order')->where($where)->setField('order_state',50);
			if ($res)
			{
				//赠送商品积分 扣除所需积分
				$get_point_amount = 0;
				$cost_point_amount = 0;
				foreach ($order['OrderGoods'] as $k => $goods)
				{
					$get_point_amount += $goods['goods_point'];
					$cost_point_amount += $goods['cost_point'];
				}
				M('Member')->where(array('member_id'=>$order['member_id']))->setInc('point',$get_point_amount);
				M('Member')->where(array('member_id'=>$order['member_id']))->setDec('point',$cost_point_amount);
				//TODO:积分日志
				//订单日志
				$log_data['order_id'] = $order['order_id'];
				$log_data['order_state'] = get_order_state_name(40);
				$log_data['change_state'] = get_order_state_name(50);
				$log_data['state_info'] = '系统自动完成订单';
				$log_data['log_time'] = $order['auto_finish_time'];//NOW_TIME;
				$log_data['operator'] = '系统';
				M('OrderLog')->add($log_data);
				//进行三级分润
				$this->orderShareProfit($order['order_id']);
			}
		}
	}
	
	//充值
	public function recharge()
	{
		if(IS_POST)
		{

		}elseif (IS_GET)
		{
			$this->display();
		}
	}

	//提现
	public function withdraw()
	{
		if(IS_POST)
		{

		}elseif (IS_GET)
		{
			$this->display();
		}
	}
}