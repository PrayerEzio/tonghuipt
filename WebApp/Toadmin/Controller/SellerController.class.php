<?php
/**
 * 商家
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Toadmin\Controller;
use Think\Page;
class SellerController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->model = D('Seller');
	}	
	//管理
	public function seller()
	{
		$map = array();
		$mobile = trim($_GET['mobile']);
		if($mobile)$map['mobile'] = array('like','%'.$mobile.'%');
		
		$totalRows = $this->model->where($map)->count();
		$page = new Page($totalRows,10);	
		$list = $this->model->where($map)->limit($page->firstRow.','.$page->listRows)->order('register_time desc')->select();				
		$this->assign('list',$list);
		$this->assign('search',$_GET);	
		$this->assign('page_show',$page->show());
		$this->display();
	}
	//curd
	public function curd(){
		if (IS_POST) {
			$data['seller_name'] = str_rp(trim($_POST['seller_name']));
			$data['nickname'] = str_rp(trim($_POST['nickname']));
			$data['mobile'] = str_rp(trim($_POST['mobile']));
			$data['predeposit'] = floatval($_POST['predeposit']);
			$data['frozen'] = floatval($_POST['frozen']);
			$data['point'] = intval($_POST['point']);
			$data['seller_status'] = intval($_POST['seller_status']);
			$seller_id = intval($_POST['seller_id']);
			if ($seller_id) {
				$res = $this->model->where(array('seller_id'=>$seller_id))->save($data);
				if ($res) {
					$this->success('修改商家资料成功');
				}else {
					$this->error('修改商家资料失败');
				}
			}else {
				$this->error('非法操作');
			}
		}elseif (IS_GET){
			$where['seller_id'] = intval($_GET['id']);
			$info = $this->model->relation(true)->where($where)->find();
			$this->title = '商家信息-'.get_seller_nickname($info['seller_id']);
			$this->assign('info',$info);
			$this->display();
		}
	}
	//删除
	public function seller_del()
	{
		if(IS_GET && $_GET['seller_id'])
		{
			$this->model->where('seller_id='.intval($_GET['seller_id']))->delete(); 		
		}
		$this->success("操作成功",U('seller'));  	
		exit;			
	}		
	//重置密码
	public function resetpwd()
	{
		$seller_id = intval($_GET['seller_id']);
		if($seller_id)
		{
			$pwd = '123456'; //默认重置密码为123456
			$pwd = re_md5($pwd);
			$this->model->where('seller_id='.$seller_id)->setField('pwd',$pwd);
			$this->success("操作成功",U('seller'));  	
			exit;					
		}	
	}	
	//等级设置
	public function degree()
	{
		$SellerDegree = M('SellerDegree');
		if($_GET['ajax_submit'] == 'ok')
		{
			$type = trim($_GET['type']);
			$md_id = intval($_GET['md_id']);
			if($type == 'name')
			{
				$rs = $SellerDegree->where('md_id='.$md_id)->setField('md_name',trim($_GET['md_name']));	
			}else{
				$md_to = intval($_GET['md_to']);
				$md_from = $md_to+1;
				$md_fid = $md_id+1;
				$rs_a = $SellerDegree->where('md_id='.$md_id)->setField('md_to',$md_to);
				$rs_b = $SellerDegree->where('md_id='.$md_fid)->setField('md_from',$md_from);
				$rs = $rs_a && $rs_b;					
			}
			//更新缓存
			if($rs)
			{
/*				if(F('seller_degree'))
				{
					F('seller_degree',NULL);	
				}
				$seller_degree = array();
				$tmp_list = $SellerDegree->order('md_id asc')->select();
				if(!empty($tmp_list))
				{
					foreach ($tmp_list as $val)
					{
						$seller_degree[$val['md_from'].'-'.$val['md_to']] = $val;
					}
					F('seller_degree', $seller_degree); 	
				}*/
				echo json_encode(array('done'=>true));die;				
			}else{
				echo json_encode(array('done'=>false));die;	
			}
		}
		$list = $SellerDegree->order('md_id asc')->select();
		$this->assign('list',$list);
		$this->display('seller_degree');	
	}
	//分数设置
	public function score()
	{
		$SellerScore = M('SellerScore');
		if($_GET['ajax_submit'] == 'ok')
		{
			$rs = $SellerScore->where('ss_id='.intval($_GET['ss_id']))->setField(trim($_GET['ss_type']),intval($_GET['value']));				
			if($rs)
			{
				echo json_encode(array('done'=>true));die;
			}else{
				echo json_encode(array('done'=>false));die;
			}
		}
		$list = $SellerScore->order('ss_id asc')->select();
		$this->assign('list',$list);		
		$this->display('seller_score');	
	}
	//预存款充值管理
	public function predeposit()
	{
		$pc_model = M('PredepositCharge');
		$map = array();
		if($_GET['seller_name'])$map['seller_name'] = array('eq',trim($_GET['seller_name']));	
		if($_GET['status'])$map['status'] = array('eq',intval($_GET['status']));
		
		$totalRows = $pc_model->where($map)->count();
		$page = new Page($totalRows,10);	
		$list = $pc_model->where($map)->limit($page->firstRow.','.$page->listRows)->order('charge_time desc')->select();				
		$this->assign('list',$list);
		$this->assign('search',$_GET);	
		$this->assign('page_show',$page->show());
		$this->display();		
	}	
	//预存款充值信息删除
	public function predeposit_del()
	{
		if(IS_GET && intval($_GET['pre_id']))
		{
			$status = M('PredepositCharge')->where('pre_id='.intval($_GET['pre_id']))->getField('status');
			if($status == 1)
			{
				M('PredepositCharge')->where('pre_id='.intval($_GET['pre_id']))->delete(); 
				$this->success("操作成功",U('predeposit'));  	
				exit;					
			}else{
				$this->error("操作失败",U('predeposit'));  	
				exit;						
			}
		}
	}
	//会员账号列表
	public function account(){
		if (IS_POST) {
			if (!empty($_POST['del_id']))
			{
				if (is_array($_POST['del_id']))
				{
					foreach ($_POST['del_id'] as $id)
					{
						M('SellerAccount')->where(array('account_id'=>$id))->delete();
					}
				}
			}else {
				$this->error("请选择要操作的对象");
			}
		}
		$where = array();
		if (trim($_GET['account_name'])) {
			$where['account_name'] = array('like','%'.str_rp(trim($_GET['account_name'])).'%');
		}
		$count = D('SellerAccount')->where($where)->count();
		$page = new Page($count,10);
		$list = D('SellerAccount')->relation(true)->where($where)->limit($page->firstRow.','.$page->listRows)->order('')->select();
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->assign('title','账号管理');
		$this->display();
	}
	//审核账号
	public function authAccount(){
		if (IS_POST) {
			$account_id = intval($_POST['account_id']);
			$status = intval($_POST['status']);
			$res = M('SellerAccount')->where(array('account_id'=>$account_id))->setField('status',$status);
			if ($res) {
				$this->success('账号审核成功',U('account'));
			}else {
				$this->error('账号审核失败');
			}
		}elseif (IS_GET){
			$where['account_id'] = intval($_GET['id']);
			if ($where['account_id']) {
				$info = D('SellerAccount')->relation(true)->where($where)->find();
				if ($info) {
					$this->assign('info',$info);
					$this->platform = M('Platform')->order('pf_sort desc')->select();
					$this->assign('title','审核账号-'.$info['store_name']);
					$this->display();
				}else {
					$this->error('没有找到相关信息');
				}
			}
		}
	}
	//删除账号
	public function account_del(){
		$id = intval($_GET['id']);
		if ($id) {
			$res = M('SellerAccount')->where(array('account_id'=>$id))->delete();
			if ($res) {
				$this->success('删除账号成功');
			}else {
				$this->error('删除账号失败');
			}
		}else {
			$this->error('没有找到相关记录');
		}
	}
}