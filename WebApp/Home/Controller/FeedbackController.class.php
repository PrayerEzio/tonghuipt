<?php
/**
 * 维修
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Home\Controller;
use Home\Controller\BaseController;
use Think\Page;
class FeedbackController extends BaseController {
	public function __construct(){
		parent::__construct();
		$this->check_login();
		$this->model = D('Repair');
		$this->member_type = M('Member')->where(array('member_id'=>$this->mid))->getField('member_type');
	}
	//维修列表
	public function index(){
		$pl = M('ProviderLog')->where(array('member_id'=>$this->mid))->order('pl_time desc')->find();
		$this->pl = $pl;
		$member_id = M('Member')->where(array('member_id'=>$this->mid,'member_type'=>1))->getField('member_id');
		$where['admin_id'] = $member_id;
		$count = $this->model->where($where)->count();
		$page = new Page($count,10);
		$list = $this->model->relation(true)->where($where)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();
		$this->assign('list',$list);
		$this->assign('page',$page->show());
		$this->assign('search',$_GET);
		$this->display();
	}
	//维修详情
	public function detail(){
		$member_type = M('Member')->where(array('member_id'=>$this->mid))->getField('member_type');
		$this->member_type = $member_type;
		if (IS_POST) {
			$rp_id = $this->model->where(array('admin_id'=>$this->mid,'rp_sn'=>$_GET['sn'],'rp_status'=>1))->getField('rp_id');
			if ($member_type && $rp_id) {
				$i = 0;
				$plan = 1;
				M('RepairMenu')->where(array('rp_id'=>$rp_id,'plan'=>$plan))->delete();
				if (trim($_POST['rp_report'])) {
					$rp_report = trim($_POST['rp_report']);
					unset($_POST['rp_report']);
				}else {
					$rp_report = '维修商很懒,没有填写故障原因.';
				}
				M('Repair')->where(array('rp_id'=>$rp_id))->setField('rp_report',$rp_report);
				$cost = 0;
				foreach ($_POST as $key => $val){
					$menu[$i]['rp_id'] = $rp_id;
					$menu[$i]['plan'] = $plan;
					$menu[$i]['name'] = str_rp($val['name'],1);
					$menu[$i]['type'] = intval($val['type']);
					$menu[$i]['num'] = intval($val['num']);
					$menu[$i]['day'] = intval($val['day']);
					$menu[$i]['price'] = floatval($val['price']);
					$menu[$i]['remark'] = str_rp($val['remark'],1);
					$cost += $menu[$i]['num']*$menu[$i]['price'];
					$i++;
				}
				$res = M('RepairMenu')->addAll($menu);
				if ($res) {
					//写入维修日志
					$log['rp_id'] = $rp_id;
					$log['log_content'] = '维修商提交了维修报价单.';
					$log['is_view'] = 1;
					$log['log_time'] = NOW_TIME;
					M('RepairLog')->add($log);
					$this->model->where(array('rp_id'=>$rp_id))->setField('rp_status',2);
					$this->model->where(array('rp_id'=>$rp_id))->setField('cost',floatval($cost));
					$member_id = $this->model->where(array('rp_id'=>$rp_id))->getField('member_id');
					$member = M('Member')->where(array('member_id'=>$member_id))->find();
					if (empty($member['mobile'])){
						sendEmail($member['email'], '您的维修订单已报价.', '【佐西卡】佐西卡维修商已为您的维修订单报价,请登录佐西卡官网确认.');
					}else {
						sendSMS($member['mobile'], '【佐西卡】佐西卡维修商已为您的维修订单报价,请登录佐西卡官网确认.');
					}
					$this->success('维修报价提交成功.');
				}else {
					$this->error('维修报价提交失败,请重新提交');
				}
			}
		}elseif (IS_GET) {
			$where = array();
			$sn = I('get.sn',0);
			if ($sn) {
				$where['rp_id'] = M('Repair')->where(array('rp_sn'=>$sn,'admin_id'=>$this->mid))->getField('rp_id');
				$where['admin_id'] = $this->mid;
				$info = $this->model->relation(true)->where($where)->find();
			}
			if (empty($info)) {
				$this->error('没有找到相关信息');
			}else {
				$admin_where['member_type'] = 1;
				$admin_list = M('Member')->where($admin_where)->select();
				$this->assign('admin_list',$admin_list);
				$this->assign('info',$info);
				$this->assign('title','反馈详情');
				$this->display();
			}
		}
	}
	public function provider(){
		if (IS_AJAX) {
			$mid = intval($_POST['mid']);
			if ($mid) {
				$data['member_id'] = $this->mid;
				$data['pl_status'] = 0;
				$res = M('ProviderLog')->where($data)->count();
				if ($res) {
					$result['code'] = 404;
					$result['msg'] = '您已经申请过了,请耐心等待管理员确认.';
					$result['data'] = '';
				}else {
					$data['pl_time'] = NOW_TIME;
					$res = M('ProviderLog')->add($data);
					if ($res) {
						$result['code'] = 200;
						$result['msg'] = '您已经申请成功,请耐心等待管理员确认.';
						$result['data'] = '';
					}else {
						$result['code'] = 404;
						$result['msg'] = '申请失败,请重新申请.';
						$result['data'] = '';
					}
				}
 			}else {
				$result['code'] = 404;
				$result['msg'] = '非法操作';
				$result['data'] = '';
			}
			echo json_encode($result);
		}
	}
	public function cancel_repair(){
		$rp_sn = trim($_GET['rp_sn']);
		if ($rp_sn) {
			$where['rp_sn'] = $rp_sn;
			$where['member_id'] = $this->mid;
			$where['rp_status'] = array('lt',4);
			$rp_id = $this->model->where($where)->getField('rp_id');
			$res = $this->model->where($where)->setField('rp_status',-1);
			if ($res) {
				//写入维修日志
				$log['rp_id'] = $rp_id;
				$log['log_content'] = '会员取消了维修订单.';
				$log['is_view'] = 1;
				$log['log_time'] = NOW_TIME;
				M('RepairLog')->add($log);
				$this->success('维修订单取消成功',U('Member/progress',array('sn'=>$rp_sn)));
			}else {
				$this->error('维修订单取消失败');
			}
		}else {
			$this->error('订单号不能为空');
		}
	}
	public function finish(){
		$rp_sn = trim($_GET['rp_sn']);
		if ($rp_sn) {
			$where['rp_sn'] = $rp_sn;
			$where['member_id'] = $this->mid;
			$where['rp_status'] = array('eq',6);
			$rp_id = $this->model->where($where)->getField('rp_id');
			$res = $this->model->where($where)->setField('rp_status',7);
			if ($res) {
				//写入维修日志
				$log['rp_id'] = $rp_id;
				$log['log_content'] = '会员验收了维修订单,维修订单已完成.';
				$log['is_view'] = 1;
				$log['log_time'] = NOW_TIME;
				M('RepairLog')->add($log);
				$this->success('维修订单验收成功',U('Member/progress',array('sn'=>$rp_sn)));
			}else {
				$this->error('维修订单验收失败');
			}
		}else {
			$this->error('订单号不能为空');
		}
	}
}