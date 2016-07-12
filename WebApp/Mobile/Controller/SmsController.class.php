<?php
/**
 * 短信
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Mobile\Controller\BaseController;
use Think\Page;
use Muxiangdao\DesUtils;
class SmsController extends BaseController{
	public function __construct(){
		parent::__construct();
	}

	/**
	 *
	 */
	public function sendSms()
	{
		if (IS_AJAX)
		{
			$mobile = trim($_POST['mobile']);
			$type = trim($_POST['type']);
			$data['mobile'] = $mobile;
			$data['type'] = $type;
			$data['time'] = time();
			$data['code'] = nonce_str(6);
			$content = '您好,您的短信验证码是:'.$data['code'].',三分钟有效,请不要告诉给别人哦.';
			sendSMS($data['mobile'],$content);
			session('sms_code_'.$type,$data);
			json_return(1,'验证码发送成功','');
		}
	}
}