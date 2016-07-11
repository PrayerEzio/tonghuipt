<?php
/**
 * 举报
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Toadmin\Controller;
use Think\Page;
class ReportController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->reportModel = D('Report');
		$this->classModel = M('ReportClass');
	}	
	//举报分类
	public function reportClass(){
		if (IS_POST) {
			$ids = $_POST['del_id'];
			if (!empty($ids)) {
				foreach ($ids as $id){
					$where['rp_class_id'] = $id;
					$this->classModel->where($where)->delete();
				}
				$this->success('操作成功');
			}else {
				$this->error('请选择处理对象');
			}
		}else {
			$where = array();
			$list = $this->classModel->where($where)->order('rp_class_sort desc')->select();
			$this->assign('list',$list);
			$this->title = '申诉分类管理';
			$this->display();
		}
	}
	//curd举报分类
	public function curdReportClass(){
		if (IS_POST) {
			$id = I('post.id',0,'int');
			$data['rp_class_name'] = str_rp(trim($_POST['rp_class_name']));
			$data['rp_class_belong'] = I('post.rp_class_belong',0,'int');
			$data['rp_class_sort'] = I('post.rp_class_sort',0,'int');
			$data['rp_class_status'] = I('post.rp_class_status',0,'int');
			if ($id) {
				$where['rp_class_id'] = $id;
				$res = $this->classModel->where($where)->save($data);
			}else {
				$res = $this->classModel->add($data);
			}
			if ($res) {
				$this->success('操作成功',U('reportClass'));
			}else {
				$this->error('操作失败');
			}
		}elseif (IS_GET){
			$id = I('get.id',0,'int');
			$title = '新增举报分类';
			if ($id) {
				$where['rp_class_id'] = $id;
				$info = $this->classModel->where($where)->find();
				$this->info = $info;
				$title = '编辑举报分类';
			}
			$this->title = $title;
			$this->display();
		}
	}
	//举报列表
	public function index(){
		$where = array();
		$order_sn = str_rp(trim($_GET['order_sn']));
		if ($order_sn) {
			$where['order_sn'] = array('like','%'.$order_sn.'%');
		}
		$where['addtime'] = array('lt',NOW_TIME-(MSC('arbitrate_time')*60*60));
		$where['handle_status'] = array('neq',-1);
		$count = $this->reportModel->where($where)->count();
		$page = new Page($count,10);
		$list = $this->reportModel->relation(true)->where($where)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();
		$this->assign('list',$list);
		$this->assign('page',$page->show());
		$this->title = '申诉管理';
		$this->assign('search',$_GET);
		$this->display();
	}
	//举报细节
	public function detail(){
		$id = I('get.id',0,'int');
		if ($id) {
			$where['report_id'] = $id;
			$info = $this->reportModel->relation(true)->where($where)->find();
		}
		if (empty($info)) {
			$this->error('没有找到相关信息');
		}else {
			$this->assign('info',$info);
			$order = D('Order')->where(array('order_sn'=>$info['order_sn']))->relation(true)->find();
			if (is_array($order['OrderLog'])) {
				foreach ($order['OrderLog'] as $key => $vo){
					$where['order_id'] = $order['order_id'];
					$where['log_id'] = $vo['log_id'];
					$order['OrderLog'][$key]['pic'] = M('OrderPic')->where($where)->select();
				}
			}
			$this->assign('order', $order);
			$this->assign('title','申诉详情-'.$info['order_sn']);
			$this->display();
		}
	}
	//在线编辑	
	public function ajax()
	{
		$id = intval($_GET['id']);
		switch(trim($_GET['branch']))
		{
			case 'rp_class_sort':
			$this->classModel->where(array('rp_class_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
			break;
			case 'rp_class_name':
			$this->classModel->where(array('rp_class_id'=>$id))->setField($_GET['column'],trim($_GET['value']));
			break;	
		}
	}
	public function dealReport(){
		$id = intval($_GET['id']);
		$type = intval($_GET['type']);
		$where['_string'] = 'handle_status = 0 or handle_status =1';
		$where['report_id'] = $id;
		$report = $this->reportModel->relation(true)->where($where)->find();
		$order = M('Order')->where(array('order_sn'=>$report['order_sn']))->find();
		$step_id = M('Order_log')->where(array('order_id'=>$order['order_id']))->max('step_id');
		if ($order['order_state'] == 5) {
			$this->error('该订单已完成,无法进行申诉处理');
		}elseif ($order['order_state'] == -1) {
			$this->error('该订单已取消,无法进行申诉处理');
		}
		if ($step_id == 5) {
			$this->error('该订单已完成,无法进行申诉处理');
		}
		if ($type == 1) {
			//申诉成功
			if ($report['from_to'] == 1) {
				//判断任务进度大于1则强制打款
				//买家申诉,强制打款
				if ($step_id > 1) {
					$sres = M('Seller')->where(array('seller_id'=>$order['seller_id']))->setDec('frozen',$order['order_amount']);
					if ($sres) {
						$mres = M('Member')->where(array('member_id'=>$order['member_id']))->setInc('predeposit',$order['order_amount']);
						//完成订单
						$order['order_state'] = 5;
						$res = M('Order')->where(array('order_sn'=>$report['order_sn']))->save($order);
						if ($res) {
							//写入订单日志
							$order_log['order_id'] = $order['order_id'];
							$order_log['order_state'] = $order['order_state'];
							$order_log['state_info'] = '买家申诉成功,系统强制打款';
							$order_log['log_time'] = NOW_TIME;
							$order_log['operator'] = '系统管理员';
							$order_log['log_content'] = '买家申诉成功,系统强制打款';
							$order_log['step_id'] = 5;
							M('OrderLog')->add($order_log);
						}
					}else {
						$this->error('处理申诉失败,原因:强制扣款失败.');
					}
				}elseif ($step_id == 1) {
					//取消订单
					$order['order_state'] = -2;
					$res = M('Order')->where(array('order_sn'=>$report['order_sn']))->save($order);
					//写入订单日志
					$order_log['order_id'] = $order['order_id'];
					$order_log['order_state'] = $order['order_state'];
					$order_log['state_info'] = '买家申诉成功,取消订单';
					$order_log['log_time'] = NOW_TIME;
					$order_log['operator'] = '系统管理员';
					$order_log['log_content'] = '买家申诉成功,取消订单';
					$order_log['step_id'] = -2;
					M('OrderLog')->add($order_log);
				}
				//写入申诉日志
				$data['report_id'] = $id;
				$data['content'] = '系统判决:买家申诉成功';
				$data['addtime'] = NOW_TIME;
				$data['remark'] = '系统判决';
				M('ReportDetail')->add($data);
				$this->reportModel->where(array('report_id'=>$id))->setField('handle_status',2);
				$this->success('处理申诉成功',U('index'));
			}elseif ($report['from_to'] == -1) {
				//卖家申诉,订单取消
				$order_state = $order['order_state'];
				if ($order_state != -5) {
					$order['order_state'] = -2;
					$res = M('Order')->where(array('order_sn'=>$report['order_sn']))->save($order);
				}else {
					$res = 1;
				}
				if ($res) {
					//写入订单日志
					$order_log['order_id'] = $order['order_id'];
					$order_log['order_state'] = $order['order_state'];
					$order_log['state_info'] = '申诉处理完成,订单取消';
					$order_log['log_time'] = NOW_TIME;
					$order_log['operator'] = '系统管理员';
					$order_log['log_content'] = '卖家申诉成功,订单取消';
					$order_log['step_id'] = -2;
					M('OrderLog')->add($order_log);
					//写入申诉日志
					$data['report_id'] = $id;
					$data['content'] = '系统判决:卖家申诉成功';
					$data['addtime'] = NOW_TIME;
					$data['remark'] = '系统判决';
					M('ReportDetail')->add($data);
					$this->reportModel->where(array('report_id'=>$id))->setField('handle_status',2);
					$this->success('处理申诉成功',U('index'));
				}else {
					$this->error('处理申诉失败');
				}
			}
		}elseif ($type == -1){
			//获取订单最后状态并返回
			M('Order')->where(array('order_id'=>$order['order_id']))->setField('step_id',$step_id);
			//驳回申诉
			$data['report_id'] = $id;
			$data['content'] = '系统判决:驳回申诉';
			$data['addtime'] = NOW_TIME;
			$data['remark'] = '系统判决';
			M('ReportDetail')->add($data);
			$this->reportModel->setField('handle_status',2);
			$this->success('处理申诉成功',U('index'));
		}
	}
}