<?php

/***************************************************************************
 * 
 * Copyright (c) 2013 Baidu.com, Inc. All Rights Reserved
 * 
 **************************************************************************/
namespace Common\Lib\Bdpay;
// 商户在百度钱包的商户ID
const SP_NO = '1000437165';
// 密钥文件路径，该文件中保存了商户的百度钱包合作密钥，该文件需要放在一个安全的地方，切勿让外人知晓或者外网访问
const SP_KEY = 'njNxieaZT9qvUXZcx7UFHkXE4UPK2J3n';
// 商户订单支付成功
const SP_PAY_RESULT_SUCCESS = 1;
// 商户订单等待支付
const SP_PAY_RESULT_WAITING = 2;
// 日志文件
const LOG_FILE = 'sdk.log';

// 百度钱包PC端即时到账支付接口URL（需要用户登录百度钱包）
const BFB_PAY_DIRECT_LOGIN_URL = "https://www.baifubao.com/api/0/pay/0/direct/0";
// 百度钱包订单号查询支付结果接口URL
const BFB_QUERY_ORDER_URL = "https://www.baifubao.com/api/0/query/0/pay_result_by_order_no";
// 百度钱包订单号查询重试次数
const BFB_QUERY_RETRY_TIME = 3;
// 百度钱包支付成功
const BFB_PAY_RESULT_SUCCESS = 1;
// 百度钱包支付通知成功后的回执
const BFB_NOTIFY_META = "<meta name=\"VIP_BFB_PAYMENT\" content=\"BAIFUBAO\">";

// 签名校验算法
const SIGN_METHOD_MD5 = 1;
const SIGN_METHOD_SHA1 = 2;
// 百度钱包即时到账接口服务ID
const BFB_PAY_INTERFACE_SERVICE_ID = 1;
// 百度钱包查询接口服务ID
const BFB_QUERY_INTERFACE_SERVICE_ID = 11;
// 百度钱包接口版本
const BFB_INTERFACE_VERSION = 2;
// 百度钱包接口字符编码
const BFB_INTERFACE_ENCODING = 1;
// 百度钱包接口返回格式：XML
const BFB_INTERFACE_OUTPUT_FORMAT = 1;
// 百度钱包接口货币单位：人民币
const BFB_INTERFACE_CURRENTCY = 1;
class Bdpay{
	public $err_msg;
	public $order_no;

	function __construct() {
	}
	function toBdpay($params){
		redirect($this->create_baifubao_pay_order_url($params));
	}
	/**
	 * 生成百度钱包PC端即时到账支付接口对应的URL
	 *
	 * @param array $params	生成订单的参数数组，具体参数的取值参见接口文档
	 * @param string $url   百度钱包PC端即时到账支付接口URL
	 * @return string 返回生成的百度钱包PC端即时到账支付接口URL
	 */
	function create_baifubao_pay_order_url($params, $url = BFB_PAY_DIRECT_LOGIN_URL) {
		$params['service_code'] = 1;
		$params['sp_no'] = SP_NO;
		$params['order_create_time'] = date('YmdHis',NOW_TIME);
		$params['currency'] = BFB_INTERFACE_CURRENTCY;
		$params['total_amount'] = $params['total_amount']*100;
		if (empty($params['return_url'])) {
			$params['return_url'] = U('Home/Pay/bdpayNotify', '', true, true);
		}
		if (empty($params['page_url'])) {
			$params['page_url'] = U('Home/Pay/bdpayReturn', '', true, true);
		}
		if (empty($params['pay_type'])) {
			$params['pay_type'] = 1;
		}
		if (empty($params['input_charset'])) {
			$params['input_charset'] = BFB_INTERFACE_ENCODING;
		}
		$params['version'] = 2;
		if (empty($params['sign_method'])) {
			$params['sign_method'] = SIGN_METHOD_MD5;
		}
		if (empty($params ['service_code']) || 
			empty($params ['sp_no']) ||
			empty($params ['order_create_time']) || 
			empty($params ['order_no']) || 
			empty($params ['goods_name']) || 
			empty($params ['total_amount']) || 
			empty($params ['currency']) || 
			empty($params ['return_url']) || 
			empty($params ['pay_type']) || 
			empty($params ['input_charset']) || 
			empty($params ['version']) || 
			empty($params ['sign_method'])) {
			$this->log(sprintf('invalid params, params:[%s]', print_r($params, true)));
			return false;
		}
		if (!in_array($url, 
				array (
					BFB_PAY_DIRECT_LOGIN_URL,
			        BFB_QUERY_ORDER_URL,
				))) {
			$this->log(
					sprintf('invalid url[%s], bfb just provide three kind of pay url', 
					$url));
			return false;
		}
		
		$pay_url = $url;
		
		if (false === ($sign = $this->make_sign($params))) {
			return false;
		}
		$this->order_no = $params ['order_no'];
		$params ['sign'] = $sign;
		$params_str = http_build_query($params);
		$this->log(
				sprintf('the params that create baifubao pay order is [%s]', 
						$params_str));
		
		return $pay_url . '?' . $params_str;
	}

	/**
	 * 当收到百度钱包的支付结果通知后，return_url页面需要做的预处理工作
	 * 该方法放在商户配置的return_url的页面的处理逻辑里，当收到该页面的get请求时，
	 * 预先进行参数验证，签名校验，订单查询，然后才是商户对该订单的处理流程。
	 *
	 * @return boolean 预处理成功返回true，否则返回false
	 */
	function check_bfb_pay_result_notify() {
		// 检查请求的必选参数，具体的参数参见接口文档
		if (empty($_GET) || !isset($_GET ['sp_no']) || !isset(
				$_GET ['order_no']) || !isset($_GET ['bfb_order_no']) ||
				 !isset($_GET ['bfb_order_create_time']) ||
				 !isset($_GET ['pay_time']) || !isset($_GET ['pay_type']) ||
				 !isset($_GET ['total_amount']) || !isset($_GET ['fee_amount']) ||
				 !isset($_GET ['currency']) || !isset($_GET ['pay_result']) ||
				 !isset($_GET ['input_charset']) || !isset($_GET ['version']) ||
				 !isset($_GET ['sign']) || !isset($_GET ['sign_method'])) {
			$this->err_msg = 'return_url页面的请求的必选参数不足';
			$this->log(
					sprintf('missing the params of return_url page, params[%s]', 
							print_r($_GET)));
		}
		$arr_params = $_GET;
		$this->order_no = $arr_params ['order_no'];
		// 检查商户ID是否是自己，如果传过来的sp_no不是商户自己的，那么说明这个百度钱包的支付结果通知无效
		if (SP_NO != $arr_params ['sp_no']) {
			$this->err_msg = '百度钱包的支付结果通知中商户ID无效，该通知无效';
			$this->log(
					'the id in baifubao notify is wrong, this notify is invaild');
			return false;
		}
		// 检查支付通知中的支付结果是否为支付成功
		if (BFB_PAY_RESULT_SUCCESS != $arr_params ['pay_result']) {
			$this->err_msg = '百度钱包的支付结果通知中商户支付结果异常，该通知无效';
			$this->log(
					'the pay result in baifubao notify is wrong, this notify is invaild');
			return false;
		}
		
		// 签名校验
		if (false === $this->check_sign($arr_params)) {
			$this->err_msg = '百度钱包后台通知签名校验失败';
			$this->log('baifubao notify sign failed');
			return false;
		}
		$this->log('baifubao notify sign success');
		
		// 通过百度钱包订单查询接口再次查询订单状态，二次校验
		// 该查询接口存在一定的延迟，商户可以不用二次校验，信任后台的支付结果通知便行
// 		if (false === $this->query_baifubao_pay_result_by_order_no(
// 				$arr_params ['order_no'])) {
// 			$this->err_msg = '调用百度钱包订单查询接口失败';
// 			$this->log('call baifubao pay result interface failed');
// 			return false;
// 		}
// 		$this->log('baifubao query pay result by order_no success');
		
		// 查询订单在商户自己系统的状态
		$order_no = $arr_params ['ord