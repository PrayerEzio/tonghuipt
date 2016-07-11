<?php
/**
 * 维修
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Toadmin\Controller;
use Think\Page;
class FeedbackController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
        $this->model = D('Repair');
	}	
	//维修列表
	public function index(){
		$where = array();
		/* $admin_type = M('Admin')->where(array('admin_id'=>AID))->getField('admin_type');
		$this->admin_type = $admin_type;
		if ($admin_type) {
			$where['admin_id'] = AID;
		} */
		if(intval($_GET['admin_id'])){
			if (intval($_GET['admin_id']) == -1) {
				$where['admin_id'] = 0;
			}else {
				$where['admin_id'] = array('eq',intval($_GET['admin_id']));
			}
		}
		$add_time_from = trim($_GET['add_time_from']) ? strtotime(trim($_GET['add_time_from'])) : 0;
		$add_time_to = trim($_GET['add_time_to']) ? strtotime(trim($_GET['add_time_to']))+86400 : NOW_TIME;
		$where['addtime'] = array('between',array($add_time_from,$add_time_to));
		if (trim($_GET['member_name'])) {
			$member_id_list = M('Member')->where(array(trim($_GET['type'])=>array('like','%'.trim($_GET['member_name']).'%')))->field('member_id')->select();
			if ($member_id_list) {
				$member_ids = '';
				foreach ($member_id_list as $key => $member_id){
					$member_id = $member_id.',';
				}
				$member_ids = substr($member_ids, 0, -1);
				if ($member_ids) {
					$where['member_id'] = array('in',$member_ids);
				}
			}
		}
		if (trim($_GET['machine_code'])) {
			$where['machine_code'] = array('like','%'.trim($_GET['machine_code']).'%');
		}
		$count = $this->model->where($where)->count();
		$page = new Page($count,10);
		$list = $this->model->relation(true)->where($where)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();
		if (is_array($list)) {
			foreach ($list as $key => $val){
				if ($val['spec_id'] && empty($val['spec_name'])) {
					$list[$key]['spec_name'] = M('GoodsSpec')->where(array('spec_id'=>$val['spec_id']))->getField('spec_name');
				}
			}
		}
		$admin_where['member_type'] = 1;
		$admin_list = M('Member')->where($admin_where)->select();
		$this->assign('admin_list',$admin_list);
		$this->assign('list',$list);
		$this->assign('page',$page->show());
		$this->title = '维修列表';
		$this->assign('search',$_GET);
		$this->display();
	}
	//维修详情
	public function detail(){
		$admin_type = M('Admin')->where(array('admin_id'=>AID))->getField('admin_type');
		$this->admin_type = $admin_type;
		if (IS_POST) {
			$rp_id = $this->model->where(array('admin_id'=>AID,'rp_id'=>$_GET['id'],'rp_status'=>1))->getField('rp_id');
			if ($admin_type && $rp_id) {
				$i = 0;
				$plan = 1;
				M('RepairMenu')->where(array('rp_id'=>$rp_id,'plan'=>$plan))->delete();
				if (trim($_POST['rp_report'])) {
					$rp_report = trim($_POST['rp_report']);
					unset($_POST['rp_report']);
				}else {
					$rp_report = '维修工程师很懒,没有填写故障原因.';
				}
				M('Repair')->where(array('rp_id'=>$rp_id))->setField('rp_report',trim($_POST['rp_report']));
				$cost = 0;
				foreach ($_POST as $key => $val){
					$menu[$i]['rp_id'] = $_GET['id'];
					$menu[$i]['plan'] = $plan;
					$menu[$i]['name'] = str_rp($val['name'],1);
					$menu[$i]['type'] = intval($val['type']);
					$menu[$i]['num'] = intval($val['num']);
					$menu[$i]['price'] = floatval($val['price']);
					$menu[$i]['remark'] = str_rp($val['remark'],1);
					$cost += $menu[$i]['num']*$menu[$i]['price'];
					$i++;
				}
				$res = M('RepairMenu')->addAll($menu);
				if ($res) {
					//写入维修日志
					$log['rp_id'] = $rp_id;
					$log['log_content'] = '维修工程师'.get_admin_nickname(AID).'提交了维修报价单.';
					$log['is_view'] = 1;
					$log['log_time'] = NOW_TIME;
					M('RepairLog')->add($log);
					$this->model->where(array('rp_id'=>$rp_id))->setField('rp_status',2);
					$this->model->where(array('rp_id'=>$rp_id))->setField('cost',floatval($cost));
					$this->success('维修报价提交成功.');
				}else {
					$this->error('维修报价提交失败,请重新提交');
				}
			}
		}elseif (IS_GET) {
			$where = array();
			$id = I('get.id',0,'int');
			if ($id) {
				$where['rp_id'] = $id;
				$info = $this->model->relation(true)->where($where)->find();
				if ($info['spec_id'] && empty($info['spec_name'])) {
					$info['spec_name'] = M('GoodsSpec')->where(array('spec_id'=>$info['spec_id']))->getField('spec_name');
				}
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
	//ajax提交分配维修工程师
	public function allot(){
		if (IS_AJAX) {
			$rp_sn = trimall($_POST['rp_sn']);
			$admin_id = intval($_POST['admin_id']);
			$admin_id = M('Member')->where(array('member_id'=>$admin_id,'admin_type'=>1))->getField('member_id');
			$data['admin_id'] = $admin_id;
			$data['rp_status'] = 1;//-1取消维修,0会员提交订单,1管理员分配订单,2维修工程师提交报价单,3管理员确认报价单,4会员支付维修报价,5维修工程师维修设备,6维修工程师发货确认,7会员收货确认
			$res = $this->model->where(array('rp_sn'=>$rp_sn))->save($data);
			if ($res) {
				//写入维修日志
				$log['rp_id'] = $this->model->where(array('rp_sn'=>$rp_sn))->getField('rp_id');
				$log['log_content'] = '平台将维修订单分配给维修工程师'.get_admin_nickname($admin_id);
				$log['is_view'] = 1;
				$log['log_time'] = NOW_TIME;
				M('RepairLog')->add($log);
				$result['code'] = 200;
				$result['msg'] = '平台将维修订单分配给维修工程师.';
				$result['data'] = get_member_nickname($admin_id);
			}else {
				$result['code'] = 300;
				$result['msg'] = '分配维修工程师失败.';
				$result['data'] = array();
			}
			echo json_encode($result);
		}else {
			echo '非法操作';
		}
	}
	//ajax管理员确认报价单
	public function confirmPrice(){
		if (IS_AJAX) {
			$admin_id = M('Admin')->where(array('admin_id'=>AID,'admin_type'=>0))->getField('admin_id');
			if ($admin_id) {
				$rp_id = intval($_POST['rp_id']);
				$attitude = intval($_POST['attitude']);
				$remark = str_rp($_POST['remark'],1);
				if ($attitude){
					//同意报价
					$price = floatval($_POST['price']);
					if ($price <= 0) {
						$result['code'] = 300;
						$result['msg'] = '维修报价等于或小于0';
						$result['data'] = '';
						json_encode($result);
						die;
					}
					$data['rp_status'] = 3;
					$data['price'] = $price;
					$data['remark'] = $remark;
					$data['cost'] = M('RepairMenu')->where(array('rp_id'=>$rp_id))->sum('price');
					$res = $this->model->where(array('rp_id'=>$rp_id,'rp_status'=>2))->save($data);
					if ($res){
						//写入维修日志
						$log['rp_id'] = $rp_id;
						$log['log_content'] = '系统后台确认维修工程师的维修报价,等待会员支付.';
						$log['is_view'] = 1;
						$log['log_time'] = NOW_TIME;
						M('RepairLog')->add($log);
						$result['code'] = 200;
						$result['msg'] = '维修报价已成功提交给客户,等待客户支付';
						$result['data'] = '';
					}else {
						$result['code'] = 300;
						$result['msg'] = '维修报价提交失败,请重新提交.';
						$result['data'] = '';
					}
				}else {
					//写入维修日志
					$log['rp_id'] = $rp_id;
					$log['log_content'] = '系统后台驳回维修工程师的维修报价.';
					$log['is_view'] = 1;
					$log['log_time'] = NOW_TIME;
					$res = M('RepairLog')->add($log);
					//驳回报价
					$res = $this->model->where(array('rp_id'=>$rp_id,'rp_status'=>2))->setField('rp_status',1);
					$result['code'] = 200;
					$result['msg'] = '维修报价已驳回,等待维修工程师重新报价.';
					$result['data'] = '';
				}
				echo json_encode($result);
			}else {
				echo '你没有权限操作';
			}
		}else {
			echo '非法操作';
		}
	}
	public function startRepair(){
		if (IS_AJAX) {
			$admin_id = M('Admin')->where(array('admin_id'=>AID,'admin_type'=>0))->getField('admin_id');
			if ($admin_id) {
				$rp_id = intval($_POST['rp_id']);
				$res = $this->model->where(array('rp_id'=>$rp_id,'rp_status'=>4))->setField('rp_status',5);
				if ($res){
					//写入维修日志
					$log['rp_id'] = $rp_id;
					$log['log_content'] = '佐西卡开始为您维修.';
					$log['is_view'] = 1;
					$log['log_time'] = NOW_TIME;
					M('RepairLog')->add($log);
					$result['code'] = 200;
					$result['msg'] = '开始维修确认成功.';
					$result['data'] = '';
				}else {
					$result['code'] = 300;
					$result['msg'] = '开始维修确认失败.';
					$result['data'] = '';
				}
				echo json_encode($result);
			}else {
				echo '你没有权限操作';
			}
		}else {
			echo '非法操作';
		}
	}
	public function deliver(){
		if (IS_POST) {
			$rp_id = intval($_POST['rp_id']);
			$type = intval($_POST['type']);
			$data['rp_status'] = 6;
			if ($type) {
				$data['deliver_express'] = intval($_POST['deliver_express']);
				$data['deliver_sn'] = str_rp($_POST['deliver_sn'],1);
				if (empty($data['deliver_express']) || empty($data['deliver_sn'])) {
					$this->error('请填写完整的物流信息.');
				}
			}
			$res = $this->model->where(array('rp_id'=>$rp_id,'rp_status'=>5))->save($data);
			if ($res) {
				//写入维修日志
				$log['rp_id'] = $rp_id;
				if ($type) {
					$log['log_content'] = '佐西卡已完成维修,开始为您发货.物流公司:'.get_express_name($data['deliver_express']).'物流号:'.$data['deliver_sn'];
				}else {
					$log['log_content'] = '佐西卡已完成,请您上门自取.';
				}
				$log['is_view'] = 1;
				$log['log_time'] = NOW_TIME;
				$c_res = M('RepairLog')->add($log);
				$member_id = $this->model->where(array('rp_id'=>$rp_id))->getField('member_id');
				$member = M('Member')->where(array('member_id'=>$member_id))->find();
				if (empty($member['mobile'])){
					sendEmail($member['email'], '您的维修订单已发货.', '【佐西卡】您的维修订单已发货,收货请登录佐西卡官网确认收货.');
				}else {
					sendSMS($member['mobile'], '【佐西卡】您的维修订单已发货,收货请登录佐西卡官网确认收货.');
				}
				$this->success('发货处理成功.');
			}else {
				$this->error('发货处理失败.');
			}
		}elseif (IS_GET){
			$this->express = M('Express')->where(array('e_state'=>1))->order('e_order')->select();
			$this->display();
		}
	}
	public function finishRepair(){
		if (IS_AJAX) {
			$admin_id = M('Admin')->where(array('admin_id'=>AID,'admin_type'=>0))->getField('admin_id');
			if ($admin_id) {
				$rp_id = intval($_POST['rp_id']);
				$res = $this->model->where(array('rp_id'=>$rp_id,'rp_status'=>6))->setField('rp_status',7);
				if ($res){
					//写入维修日志
					$log['rp_id'] = $rp_id;
					$log['log_content'] = '系统后台确认维修订单完成.';
					$log['is_view'] = 1;
					$log['log_time'] = NOW_TIME;
					M('RepairLog')->add($log);
					$result['code'] = 200;
					$result['msg'] = '维修订单确认完成.';
					$result['data'] = '';
				}else {
					$result['code'] = 300;
					$result['msg'] = '维修订单完成失败,请重新提交.';
					$result['data'] = '';
				}
				echo json_encode($result);
			}else {
				echo '你没有权限操作';
			}
		}else {
			echo '非法操作';
		}
	}
	public function breakdown(){
		$list = M('Breakdown')->order('bd_sort desc')->select();
		$list = unlimitedForLayer($list,'child','bd_upid','bd_id');
		$this->assign('list',$list);
		$this->display();
	}
	public function curdBreakdown(){
		if(IS_POST){
			$bd_id = intval($_POST['bd_id']);
			$data['bd_upid'] = intval($_POST['bd_upid']);
			$data['bd_name'] = trim($_POST['bd_name']);
			$data['bd_sort'] = intval($_POST['bd_sort']);
			$data['bd_status'] = 1;
			if ($bd_id) {
				M('Breakdown')->where(array('bd_id'=>$bd_id))->save($data);
			}else {
				M('Breakdown')->add($data);
			}
			$this->success("操作成功",U('breakdown'));
			exit;
		}else{
			$bd_id = intval($_GET['bd_id']);
			$this->info = M('Breakdown')->where(array('bd_id'=>$bd_id))->find();
			$this->ac_list = M('Breakdown')->where(array('bd_upid'=>0))->select();
			$this->display();
		}
	}
	public function delBreakdown(){
		$bd_id = intval($_GET['bd_id']);
		$array = M('Breakdown')->select();
		$ids = getChildsId($array, $bd_id, 'bd_id', 'bd_upid');
		if (is_array($ids) && !empty($ids)) {
			$this->error('请先删除子级分类');
		}
		$res = M('Breakdown')->where(array('bd_id'=>$bd_id))->delete();
		if ($res){
			$this->success('删除成功.');
		}else {
			$this->error('删除失败');
		}
	}
	//异步处理 在线编辑
	public function ajax(){
		$id = intval($_GET['id']);
		switch(trim($_GET['branch'])){
			case 'bd_sort':
				M('Breakdown')->where(array('bd_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
				break;
			case 'bd_name':
				M('Breakdown')->where(array('bd_id'=>$id))->setField($_GET['column'],trim($_GET['value']));
				break;
		}
	}
}