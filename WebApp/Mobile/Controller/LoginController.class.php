<?php
/**
 * 登录
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Mobile\Controller\BaseController;
use Think\Verify;
use Muxiangdao\Smtp;
class LoginController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->model = D('Member');
	}
	/**
	 * 登录
	 */
	public function index(){
		if (IS_POST) {
			$account = trim($_POST['account']);
			$pwd = $_POST['pwd'];
			if(empty($account) || empty($pwd)){
				$this->error('请输入账号密码');
				exit;
			}
			if($account && $pwd)
			{
				if (is_numeric($account)) {
					$s_where['mobile'] = $account;
				}else {
					$s_where['email'] = $account;
				}
				$m_info = $this->model->where($s_where)->find();
				if(is_array($m_info) && !empty($m_info))
				{
					if ($m_info['pwd'] != re_md5($pwd))
					{
						$this->error("登录密码错误！");
						exit;
					}
					if ($m_info['member_status'] == 0) {
						$this->error('账户被冻结,请联系管理员.');
						die;
					}
					session('member_id',$m_info['member_id']);
					$login_referer_url = session('login_referer');
					session('login_referer',null);
					$domain = get_host_domain($login_referer_url);
					if ($domain == $_SERVER['SERVER_NAME']) {
						$url = $login_referer_url;
					}else {
						$url = U('Member/index');
					}
					$this->success("登录成功！",$url);
					exit;
				}else{
					$this->error("不存在此用户！");
					exit;
				}
			}
		}elseif (IS_GET) {
			$this->check_login();
			session('login_referer',$_SERVER['HTTP_REFERER']);
			$this->display();
		}
	}

	/**
	 * 注册
	 */
	public function register(){
		if (IS_POST) {
			$data = array();
			$smscode = strtolower(trim($_POST['smscode']));
			$parent_mobile = str_rp(trim($_POST['parent_mobile']));
			$parent_member = M('Member')->where(array('mobile'=>$parent_mobile))->field('member_id')->find();
			if (empty($parent_member))
			{
				$this->error('邀请码无效,请重试.');
			}
			/*if (empty($smscode)) {
				$this->error('验证码为空');
			}*/
			if ($_POST['s_class'] == 'mobile') {
				$data['mobile'] = str_rp(trim($_POST['mobile']));
				if (!empty($data['mobile'])) {//$smscode == session('smscode') && session('codetype') == 'register' && session('mobile') == $data['mobile'] &&
					$count = M('Member')->where(array('mobile'=>$data['mobile']))->count();
					if ($count)
					{
						$this->error('该手机号码已注册过');
					}
					$data['pwd'] = re_md5($_POST['pwd']);
					$data['register_time'] = NOW_TIME;
					$data['member_status'] = 1;
					$data['parent_member_id'] = $parent_member['member_id'];
					$api = 'http://int.dpool.sina.com.cn/iplookup/iplookup.php';
					$ipparam['format'] = 'js';
					$ipparam['ip'] = get_client_ip();
					$res = get_api($api,$ipparam,'array');
					if (!empty($res['city'])) {
						$data['city'] = $res['city'];
					}
					$member_id = $this->model->add($data);
					if($member_id)
					{
						//saveContact($data['mobile'], 'mobile', '注册');
						unset($data);
						session(null);
						session('member_id',$member_id);
						$this->success("注册成功！",U('Member/index'));
						exit;
					}
				}else {
					$this->error('验证码错误');
				}
			}elseif ($_POST['s_class'] == 'email'){
				$data['email'] = filter_var($_POST['email'],FILTER_VALIDATE_EMAIL);
				if ($smscode == session('smscode') && session('codetype') == 'register' && session('email') == $data['email'] && !empty($data['email'])) {
					$data['pwd'] = re_md5($_POST['pwd']);
					$data['register_time'] = NOW_TIME;
					$data['member_status'] = 1;
					$api = 'http://int.dpool.sina.com.cn/iplookup/iplookup.php';
					$ipparam['format'] = 'js';
					$ipparam['ip'] = get_client_ip();
					$res = get_api($api,$ipparam,'array');
					if (!empty($res['city'])) {
						$data['city'] = $res['city'];
					}
					$member_id = $this->model->add($data);
					if($member_id)
					{
						//saveContact($data['email'], 'email', '注册');
						unset($data);
						session(null);
						session('member_id',$member_id);
						$this->success("注册成功！",U('Member/index'));
						exit;
					}
				}else {
					$this->error('验证码错误');
				}
			}
		}elseif (IS_GET){
			$this->check_login();
			$this->invite_phone = trim($_GET['invite_phone']);
			$this->display();
		}
	}
	public function sendSMS(){
		if (IS_AJAX) {
			if (session('code_time')-60 > NOW_TIME) {
				echo '请'.NOW_TIME-session('code_time').'后再进行操作.';die;
			}
			session('smscode',null);
			session('mobile',null);
			session('codetype',null);
			session('code_time',null);
			$mobile = trim($_POST['mobile']);
			$type = trim($_POST['type']);
			$email = I('post.email','','email');
			$class = trim($_POST['s_class']);
			if ($class == 'mobile') {
				if (is_numeric($mobile)) {
					$check_phone = $this->model->where(array('mobile'=>$mobile))->count();
					if ($check_phone && $type == 'register') {
						echo '该手机已注册';
						die;
					}
					if ($check_phone == 0 && $type == 'forgot') {
						echo '该手机号码不存在';
						die;
					}
					$code = nonce_str(4,0,0);
					session('smscode',strtolower($code));
					session('mobile',$mobile);
					session('codetype',$type);
					session('code_time',NOW_TIME);
					$content = '您好,您的短信验证码是'.$code;
					$res = customSendSMS($mobile, $content);
					if ($res) {
						echo '短信发送成功,请查收.';
					}else {
						echo '短信发送失败.';
					}
				}else {
					echo '不是有效的手机号码';
				}
			}elseif ($class == 'email') {
				if (!empty($email)) {
					$check_email = $this->model->where(array('email'=>$email))->count();
					if ($check_email && $type == 'register') {
						echo '该邮箱已注册';
						die;
					}
					if ($check_email == 0 && $type == 'forgot') {
						echo '该邮箱不存在';
						die;
					}
					$code = nonce_str(4,0,0);
					session('smscode',strtolower($code));
					session('email',$email);
					session('codetype',$type);
					session('code_time',NOW_TIME);
					$content = '您好,您的验证码是'.$code;
					$res = sendEmail($email, '欢迎注册佐西卡会员', $content);
					if ($res) {
						echo '邮件发送成功,请查收.';
					}else {
						echo '邮件发送失败.';
					}
				}else {
					echo '邮箱不能为空';
				}
			}
		}else {
			echo '非法操作';
		}
	}
	/**
	 * 找回密码
	 */
	public function forgot()
	{
		if(IS_POST)
		{
			$map = array();
			if ($_POST['s_class'] == 'mobile') {
				$map['mobile'] = trim($_POST['mobile']);
				$smscode = strtolower(trim($_POST['smscode']));
				$data['pwd'] = re_md5($_POST['pwd']);
				$m_info = $this->model->where($map)->find();
				if (!empty($m_info)) {
					if ($smscode == session('smscode') && session('codetype') == 'forgot' && session('mobile') == $map['mobile']) {
						$res = $this->model->where($map)->save($data);
						if ($res) {
							session(null);
							session('member_id',$m_info['member_id']);
							$this->success('密码修改成功.',U('Member/index'));
						}else {
							$this->error('密码修改失败');	
						}
					}else {
						$this->error('手机验证码错误.');
					}
				}else {
					$this->error('该手机号没有注册过.');
				}
			}elseif ($_POST['s_class']){
				$map['email'] = filter_var($_POST['email'],FILTER_VALIDATE_EMAIL);
				$smscode = strtolower(trim($_POST['smscode']));
				$data['pwd'] = re_md5($_POST['pwd']);
				$m_info = $this->model->where($map)->find();
				if (!empty($m_info)) {
					if ($smscode == session('smscode') && session('codetype') == 'forgot' && session('email') == $map['email']) {
						$res = $this->model->where($map)->save($data);
						if ($res) {
							session(null);
							session('member_id',$m_info['member_id']);
							$this->success('密码修改成功.',U('Member/index'));
						}else {
							$this->error('密码修改失败');
						}
					}else {
						$this->error('邮箱验证码错误.');
					}
				}else {
					$this->error('该邮箱没有注册过.');
				}
			}
		}
		$this->display();
	}
	/**
	 * 生成验证码
	 */
	public function get_verify(){
		$config = array(
				'length' => 4,
				'fontSize' => 14,
				'imageW' => 95,
				'imageH' => 30,
				'useNoise' => false,
				'useCurve' => false,
		);
		$verify = new Verify($config);
		$verify->entry();
	}
	/**
	 * 验证验证码
	 * @param string $code 验证码
	 * @param string $id 标示id
	 * @return boolean
	 */
	function check_verify($code, $id = ''){
		$verify = new \Think\Verify();
		if (!empty($_GET['captcha'])) {
			$code = $_GET['captcha'];
			$res =  $verify->check($code);
			$data = json_encode($res);
			echo $data;
		}
		return $verify->check($code, $id);
	}
	/**
	 * 验证用户名
	 */
	public function check_member(){
		$seller_name = trim($_GET['seller_name']);
		if($seller_name != '')
		{
			$num = $this->model->where(array('seller_name'=>$seller_name))->count();
			if($num>0)echo 'false'; else echo 'true';
		}else{
			echo 'false';
		}
	}
	/**
	 * 验证电话号码
	 */
	public function check_phone(){
		$mobile = trim($_GET['mobile']);
		if($mobile != '') {
			$num = $this->model->where(array('mobile'=>$mobile))->count();
			if($num>0)echo 'false'; else echo 'true'; 	   
		}else{
			echo 'false'; 
		}
	}
	/**
	 * 验证邮箱
	 */
	public function check_email()
	{
		$email = trim($_GET['email']);
		if($email)
		{
			$num = $this->model->where('email=\''.$email.'\'')->count();
			if($num>0)echo 'false'; else echo 'true';
		}
	}
	/* static function sendEmail($receiver, $title, $body, $attachment='', $server='', $username='', $password='', $port=25){
		if (empty($server)) {
			$server = MSC('smtp_server');
		}
		if (empty($username)) {
			$username = MSC('smtp_username');
		}
		if (empty($password)) {
			$password = MSC('smtp_password');
		}
		$mail = new Smtp();
		$mail->setServer($server, $username, $password ,$port);// 设置smtp服务器
		$mail->setFrom($username);// 设置发件人
		$mail->setReceiver($receiver);// 设置收件人，多个收件人，调用多次
		$mail->setMailInfo($title, $body, $attachment);// 设置邮件主题、内容
		$res = $mail->sendMail();// 发送
		return true;
	} */
	public function beta(){
		$mail = new Smtp();
		$mail->setServer("smtp.sina.com", "mxdkj2015@sina.com", "mxd2015..");// 设置smtp服务器
		$mail->setFrom("mxdkj2015@sina.com");// 设置发件人
		$mail->setReceiver("283386295@qq.com");// 设置收件人，多个收件人，调用多次
		$mail->setMailInfo("木想到测试", "<b>木想到邮件发送测试</b>");// 设置邮件主题、内容
		$res = $mail->sendMail();// 发送
		return true;
	}
}