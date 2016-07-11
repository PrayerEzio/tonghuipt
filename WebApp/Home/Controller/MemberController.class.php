<?php
/**
 * 会员中心
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Home\Controller;
use Home\Controller\BaseController;
use Think\Page;
use Muxiangdao\DesUtils;
use Common\Lib\Jdpay\Jdpay;
class MemberController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->check_login();
	}
	/**
	 * 基本资料
	 */
	public function index(){
		$where['member_id'] = $this->mid;
		if (IS_POST) {
			//图片上传
			if(!empty($_FILES['avatar']['size'])){
				$arc_img = 'mid_avatar_'.$this->mid;
				$param = array('savePath'=>'member/','subName'=>'','files'=>$_FILES['avatar'],'saveName'=>$arc_img,'saveExt'=>'');
				$up_return = upload_one($param);
				if($up_return == 'error'){
					$this->error('图片上传失败');
					exit;
				}else{
					$data['avatar'] = $up_return;
				}
			}
			//TODO:验证码
			$data['nickname'] = str_rp(trim($_POST['nickname']));
			$data['gender'] = intval($_POST['gender']);
			$data['age'] = intval($_POST['age']);
			$data['mobile'] = str_rp(trimall($_POST['mobile']));
			$data['email'] = I('post.email','','email');
			$res = M('Member')->where($where)->save($data);
		}
		$info = M('Member')->where($where)->find();
		$this->info = $info;
		$this->display();
	}
	/**
	 * 订单管理
	 */
	public function order(){
		if (intval($_GET['status'])) {
			$where['order_state'] = intval($_GET['status']);
		}
		$where['member_id'] = $this->mid;
		$order = 'add_time desc';
		$count = D('Order')->where($where)->count();
		$page = new Page($count,5);
		$this->list = D('Order')->relation(true)->where($where)->limit($page->firstRow.','.$page->listRows)->order($order)->select();
		$this->page = $page->show();
		$this->search = $_GET;
		$this->display();
	}
	/**
	 * 防伪查询
	 */
	public function antiForgery(){
		if (IS_AJAX) {
			$serial_number = trim($_POST['serial_number']);
			$info = M('Serial')->where(array('serial_number'=>$serial_number))->find();
			if (empty($info)) {
				json_return(300,'抱歉,您查询的商品验证码'.$serial_number.',没有通过防伪认证.');
			}else {
				$info['Goods'] = D('Goods')->relation(true)->where(array('goods_id'=>$info['goods_id']))->find();
				$info['Order'] = M('Order')->where(array('order_id'=>$info['order_id']))->find();
				$data['Goods']['goods_name'] = $info['Goods']['goods_name'];
				$data['Order']['order_sn'] = $info['Order']['order_sn'];
				$data['Goods']['goods_brand'] = $info['Goods']['GoodsBrand']['brand_name'];
				$data['Order']['order_time'] = date('Y-m-d H:i:s',$info['Order']['add_time']);
				json_return(200,'您的商品经过防伪认证',$data);
			}
		}elseif (IS_GET){
			$this->display();
		}
	}
	/**
	 * 收货地址
	 */
	public function address(){
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
				$res = M('MemberAddrs')->where(array('addr_id'=>$id))->save($data);
			}else {
				$res = M('MemberAddrs')->add($data);
			}
			$this->redirect('');
		}
		$where['member_id'] = $this->mid;
		$list = M('MemberAddrs')->where($where)->select();
		$this->list = $list;
		$dwhere['upid'] = 0;
		$dwhere['status'] = 1;
		$this->province = M('District')->where($dwhere)->order('d_sort')->select();
		$this->display();
	}
	/**
	 * 修改密码
	 */
	public function resetPassword(){
		if (IS_POST) {
			if (strtolower($_POST['smscode']) != session('smscode')) {
				$this->error('验证码错误');
			}
			$where['member_id'] = $this->mid;
			$where['mobile'] = str_rp(trim($_POST['mobile']));
			if ($_POST['pwd'] != $_POST['repwd']) {
				$this->error('两次输入的密码不一致');
			}
			$data['pwd'] = re_md5($_POST['pwd']);
			$res = M('Member')->where($where)->save($data);
			if ($res) {
				$this->success('修改密码成功');
			}else {
				$this->error('修改密码失败');
			}
		}elseif (IS_GET){
			$where['member_id'] = $this->mid;
			$this->info = M('Member')->where($where)->find();
			$this->display();
		}
	}
	/**
	 * ajax新增收货地址
	 */
	public function ajaxAddAddress(){
		if (IS_AJAX) {
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
				$res = M('District')->where(array('addr_id'=>$id))->save($data);
			}else {
				$res = M('District')->add($data);
			}
			echo $res;
		}
	}
	/**
	 * 删除地址
	 */
	public function delAddress(){
		if (IS_AJAX) {
			$id = intval($_POST['id']);
			if ($id) {
				$where['addr_id'] = $id;
				$where['member_id'] = $this->mid;
				$res = M('MemberAddrs')->where($where)->delete();
				echo $res;
			}else {
				echo '非法操作';
			}
		}else {
			echo '非法操作';
		}
	}
	/**
	 * 登出
	 */
	public function logout(){
		session('member_id',null);
		$this->success("退出成功！",U('Index/index'));
		exit;
	}
	/**
	 * 积分提现
	 */
	public function withdraw(){
		if (IS_POST) {
			$amount = floatval($_POST['amount']);
			$account = str_rp($_POST['account'],1);
			$reaccount = str_rp($_POST['reaccount'],1);
			if ($account != $reaccount){
				$this->error('两次输入的提现账号不同.');
			}
			if ($amount < MSC('withdraw_min_limit')) {
				$this->error('提现金额低于最低限额.');
			}
			$data['member_id'] = $this->mid;
			$data['wd_account'] = $account;
			$data['wd_amount'] = $amount;
			$data['wd_time'] = NOW_TIME;
			$data['wd_status'] = 0;//-1驳回0未处理1已处理
			$data['wd_remark'] = '会员积分提现';
			$log_content['time'] = NOW_TIME;
			$log_content['content'] = '会员进行积分提现操作请求.';
			$log[] = $log_content;
			$data['wd_log'] = serialize($log);
			$res = M('Withdraw')->add($data);
			if ($res) {
				$rc = M('Member')->where(array('member_id'=>$this->mid))->setDec('point',$amount*MSC('point_exchange_rate'));
				if ($rc) {
					//TODO:客服提现或者直接支付宝转账
					$this->success('提现操作成功,请等待客服处理.');
				}else {
					$this->error('提现失败.');
				}
			}else {
				$this->error('提现失败.');
			}
		}
	}
	/**
	 * 维修查询
	 */
	public function repair(){
		$where['member_id'] = $this->mid;
		$list = D('Repair')->relation(true)->where($where)->order('addtime desc')->select();
		foreach ($list as $key => $val){
			$list[$key]['Goods']['brand_name'] = M('GoodsBrand')->where(array('brand_id'=>$val['Goods']['brand_id']))->getField('brand_name');
		}
		$this->list = $list;
		$this->display();
	}
	/**
	 * 维修进度
	 */
	public function progress(){
		$rp_sn = trimall($_GET['sn']);
		$where['rp_sn'] = $rp_sn;
		$where['member_id'] = $this->mid;
		$info = D('Repair')->relation(true)->where($where)->find();
		unset($info['RepairLog']);
		$info['RepairLog'] = M('RepairLog')->where(array('rp_id'=>$info['rp_id'],'is_view'=>1))->order('log_time')->select();
		$this->assign('info',$info);
		$this->display();
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
			if (is_numeric($mobile)) {
				$check_phone = M('Member')->where(array('mobile'=>$mobile,'member_id'=>$this->mid))->count();
				if (!$check_phone) {
					echo '非法操作';
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
		}else {
			echo '非法操作';
		}
	}
	public function message(){
		if (IS_POST) {
			$data['name'] = str_rp($_POST['name'],1);
			$data['member_id'] = $this->mid;
			$data['mobile'] = str_rp($_POST['mobile'],1);
			$data['email'] = I('post.email','','email');
			$data['content'] = str_rp($_POST['content'],1);
			$data['addtime'] = NOW_TIME;
			if (empty($data['content']) || empty($data['name']) || empty($data['mobile']) || empty($data['email'])) {
				$this->error('必填信息不能为空.');
			}else {
				$res = M('Message')->add($data);
				if ($res) {
					$this->success('感谢您的反馈与建议.');
				}else {
					$this->error('抱歉,您的反馈与建议提交失败,请重试.');
				}
			}
		}
		$this->display();
	}
	public function letter(){
		$this->model = M('MemberLetter');
		$where['member_id'] = $this->mid;
		$count = $this->model->where($where)->count();
		$page = new Page($count,10);
		$list = $this->model->where($where)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();
		$this->list = $list;
		$this->page = $page->show();
		$this->display();
	}
	public function letter_detail(){
		$where['member_id'] = $this->mid;
		$where['id'] = intval($_GET['id']);
		$info = M('MemberLetter')->where($where)->find();
		$this->info = $info;
		$this->display();
	}
	/* public function service(){
		$info = M('Document')->where(array('doc_code'=>'service'))->find();
		$this->info = $info;
		$this->display();
	} */
	public function database(){
		$list = M('Beta')->where(array('beta_status'=>0))->select();
		foreach ($list as $key => $val){
			if ($val['goods_name'] == '空') {
				$data['goods_name'] = '';
			}else {
				$data['goods_name'] = $val['goods_name'];
			}
			$brand_id = M('GoodsBrand')->where(array('brand_name'=>$val['goods_brand']))->getField('brand_id');
			if ($brand_id) {
				$data['brand_id'] = $brand_id;
			}else {
				$data['brand_id'] = 73;
			}
			$data['gc_id'] = 2;
			if ($val['display_technique'] == '空') {
				$data['display_technique'] = '';
			}else {
				$data['display_technique'] = $val['display_technique'];
			}
			if ($val['bulb_brand'] == '空') {
				$data['bulb_brand'] = '';
			}else {
				$data['bulb_brand'] = $val['bulb_brand'];
			}
			if ($val['bulb_wattage'] == '空') {
				$data['bulb_wattage'] = '';
			}else {
				$data['bulb_wattage'] = str_replace('W', '', $val['bulb_wattage']);
			}
			if ($val['bulb_code'] == '空') {
				$data['bulb_code'] = '';
			}else {
				$data['bulb_code'] = $val['bulb_code'];
			}
			if ($val['factory_code'] == '空') {
				$data['factory_code'] = '';
			}else {
				$data['factory_code'] = $val['factory_code'];
			}
			if ($val['factory_brand'] == '空') {
				$data['factory_brand'] = '';
			}else {
				$data['factory_brand'] = $val['factory_brand'];
			}
			if ($val['goods_code'] == '空') {
				$data['goods_code'] = '';
			}else {
				$data['goods_code'] = $val['goods_code'];
			}
			$spec_list = explode(',',$val['spec_str']);
			if (!empty($spec_list)) {
				foreach ($spec_list as $k => $v){
					$data['GoodsSpec'][$k]['spec_name'] = $v;
				}
			}
			$data['goods_status'] = 1;
			$res = D('Goods')->relation(true)->add($data);
			unset($data);
			if ($res) {
				p('Success:'.$res.'数据库录入成功-'.$key);
				M('Beta')->where(array('beta_id'=>$val['beta_id']))->setField('beta_status',1);
			}else {
				p('Error:'.$res.'数据库录入失败-'.$key);
			}
		}
	}
	public function member_sevice(){
		$where['notice_status'] = 1;
		$where['notice_type'] = 4;
		$info = M('Notice')->where($where)->find();
		$this->assign('info',$info);
		$this->display();
	}
}