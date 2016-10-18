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
use Muxiangdao\Emoji;

class WechatController extends Controller{
	public function __construct()
	{
		parent::__construct();
		$this->assign('controller',CONTROLLER_NAME);
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
		$this->web_stting = $web_stting;
		//站点状态判断
		if($this->web_stting['site_status'] != 1){
		   echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		   echo $this->web_stting['closed_reason'];
		   exit;	
		}else {
			$this->assign('seo',seo());
		}
		//检查登录
		//检查登录
		/* session('member_id',null);
		if(!session('member_id'))
		{
			$this->wx_auto_login(); //自动登录
		}
		$this->mid = session('member_id'); */
		$this->mid = 10;
		$this->m_info = M('Member')->where('member_id='.$this->mid)->find();	
				
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
					$data = array();
					//转义emoji
					$emoji = new Emoji();
					$data['nickname'] = $emoji->emoji_unified_to_html($user->nickname);
					$data['openid'] = $user->openid;
					$data['gender'] = $user->sex;
					$data['country'] = $user->country;
					$data['province'] = $user->province;
					$data['city'] = $user->city;
					$data['usercity'] = $user->city;
					$data['avatar'] = $user->headimgurl;
					$data['unionid'] = $user->unionid;
					$data['web_token'] = $web_token;
					$data['refresh_token'] = $refresh_token;
					$data['register_time'] = NOW_TIME;
					$inviter = explode('_', trim($_GET['uid']));
					$data['inviter_type'] = intval($inviter[0]);
					$data['inviter_id'] = intval($inviter[1]);
					$return = M('Member')->add($data);
					if($return)
					{
						//推广日志
						if (!empty($inviter)) {
							$spread_log['inviter_id'] = $data['inviter_id'];
							$spread_log['invited_id'] = $return;
							$spread_log['inviter_type'] = 2;
							$spread_log['invited_type'] = $data['inviter_type'];
							$spread_log['spread_stage'] = 0;
							$spread_log['spread_time'] = NOW_TIME;
							M('SpreadLog')->add($spread_log);
						}
						session('member_id',$return);							
					}
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