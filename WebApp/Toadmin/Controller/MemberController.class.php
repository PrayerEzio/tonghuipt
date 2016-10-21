<?php
/**
 * 会员
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Toadmin\Controller;
use Think\Page;
class MemberController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->model = D('Member');
	}	
	//管理
	public function member()
	{
		$map = array();
		$mobile = trim($_GET['mobile']);
		$member_id = intval($_GET['member_id']);
		$agent_id = intval($_GET['agent_id']);
		if($member_id)$map['member_id'] = array('eq',$member_id);
		if($mobile)$map['mobile'] = array('eq',$mobile);
		if($agent_id)$map['agent_id'] = array('eq',$agent_id);
		$map['member_type'] = I('get.type',0,'int');
		$totalRows = $this->model->where($map)->count();
		$page = new Page($totalRows,10);	
		$list = $this->model->where($map)->limit($page->firstRow.','.$page->listRows)->order('register_time desc')->select();				
		$this->agent_info = M('AgentInfo')->where(array('agent_status'=>1))->field('agent_id,agent_name')->order('agent_level desc')->select();
		$this->assign('list',$list);
		$this->assign('search',$_GET);	
		$this->assign('page_show',$page->show());
		$this->display();
	}
	//curd
	public function curd(){
		if (IS_POST) {
			$data['member_name'] = str_rp(trim($_POST['member_name']));
			$data['nickname'] = str_rp(trim($_POST['nickname']));
			$data['email'] = str_rp(trim($_POST['email']));
			$data['mobile'] = str_rp(trim($_POST['mobile']));
			$data['predeposit'] = floatval($_POST['predeposit']);
			$data['point'] = intval($_POST['point']);
			$data['member_status'] = intval($_POST['member_status']);
			$data['withdraw_status'] = intval($_POST['withdraw_status']);
			$data['loan_status'] = intval($_POST['loan_status']);
			$data['merchant_status'] = intval($_POST['merchant_status']);
			$data['parent_member_id'] = intval($_POST['parent_member_id']);
			$member_id = intval($_POST['member_id']);
			$pwd = $_POST['pwd'];
			$member_info = M('Member')->where(array('member_id'=>$member_id))->find();
			if ($member_info['pwd'] != $pwd)
			{
				$data['pwd'] = re_md5($pwd);
			}
			//图片上传
			if($_FILES['id_card']['size']){
				$arc_img = 'idcard_'.$member_id;
				$param = array('savePath'=>'id_card/','subName'=>'','files'=>$_FILES['id_card'],'saveName'=>$arc_img,'saveExt'=>'');
				$up_return = upload_one($param);
				if($up_return == 'error')
				{
					$this->error('图片上传失败');
					exit;
				}else{
					$data['id_card'] = $up_return;
				}
			}
			if ($member_id) {
				$res = $this->model->where(array('member_id'=>$member_id))->save($data);
				if ($res) {
					$this->success('修改会员资料成功',U('member'));
				}else {
					$this->error('修改会员资料失败');
				}
			}else {
				$this->error('非法操作');
			}
		}elseif (IS_GET){
			$where['member_id'] = intval($_GET['id']);
			$info = $this->model->relation(true)->where($where)->find();
			$this->title = '会员信息-'.get_member_nickname($info['member_id']);
			$this->assign('info',$info);
			$this->display();
		}
	}
	//删除
	public function member_del()
	{
		if(IS_GET && $_GET['member_id'])
		{
			$this->model->where('member_id='.intval($_GET['member_id']))->delete(); 		
		}
		$this->success("操作成功",U('member'));  	
		exit;			
	}		
	//重置密码
	public function resetpwd()
	{
		$member_id = intval($_GET['member_id']);
		if($member_id)
		{
			$pwd = '123456'; //默认重置密码为123456
			$pwd = re_md5($pwd);
			$this->model->where('member_id='.$member_id)->setField('pwd',$pwd);
			$this->success("操作成功",U('member'));  	
			exit;					
		}	
	}	
	//等级设置
	public function degree()
	{
		$MemberDegree = M('MemberDegree');
		if($_GET['ajax_submit'] == 'ok')
		{
			$type = trim($_GET['type']);
			$md_id = intval($_GET['md_id']);
			if($type == 'name')
			{
				$rs = $MemberDegree->where('md_id='.$md_id)->setField('md_name',trim($_GET['md_name']));	
			}else{
				$md_to = intval($_GET['md_to']);
				$md_from = $md_to+1;
				$md_fid = $md_id+1;
				$rs_a = $MemberDegree->where('md_id='.$md_id)->setField('md_to',$md_to);
				$rs_b = $MemberDegree->where('md_id='.$md_fid)->setField('md_from',$md_from);
				$rs = $rs_a && $rs_b;					
			}
			//更新缓存
			if($rs)
			{
/*				if(F('member_degree'))
				{
					F('member_degree',NULL);	
				}
				$member_degree = array();
				$tmp_list = $MemberDegree->order('md_id asc')->select();
				if(!empty($tmp_list))
				{
					foreach ($tmp_list as $val)
					{
						$member_degree[$val['md_from'].'-'.$val['md_to']] = $val;
					}
					F('member_degree', $member_degree); 	
				}*/
				echo json_encode(array('done'=>true));die;				
			}else{
				echo json_encode(array('done'=>false));die;	
			}
		}
		$list = $MemberDegree->order('md_id asc')->select();
		$this->assign('list',$list);
		$this->display('member_degree');	
	}
	//分数设置
	public function score()
	{
		$MemberScore = M('MemberScore');
		if($_GET['ajax_submit'] == 'ok')
		{
			$rs = $MemberScore->where('ss_id='.intval($_GET['ss_id']))->setField(trim($_GET['ss_type']),intval($_GET['value']));				
			if($rs)
			{
				echo json_encode(array('done'=>true));die;
			}else{
				echo json_encode(array('done'=>false));die;
			}
		}
		$list = $MemberScore->order('ss_id asc')->select();
		$this->assign('list',$list);		
		$this->display('member_score');	
	}
	//vip充值管理
	public function vipRecharge(){
		if (IS_POST) {
			$data['member_id'] = I('post.member_id',0,'int');
			$data['vip_level'] = I('post.vip_level',0,'int');
			$data['start_time'] = strtotime(str_rp(trim($_POST['start_time'])));
			$data['end_time'] = strtotime(str_rp(trim($_POST['end_time'])));
			$res = M('MemberVip')->add($data);
			if ($res) {
				$this->success('充值vip成功');
			}else {
				$this->error('充值vip失败');
			}
		}elseif (IS_GET){
			$member_id = intval($_GET['member_id']);
			if ($member_id) {
				$info = $this->model->relation('MemberVip')->where(array('member_id'=>$member_id))->find();
				$this->assign('info',$info);
				$this->title = '充值vip';
				$this->display();
			}else {
				$this->error('没有找到相关信息');
			}
		}
	}
	//预存款充值管理
	public function predeposit()
	{
		$pc_model = M('PredepositCharge');
		$map = array();
		if($_GET['member_name'])$map['member_name'] = array('eq',trim($_GET['member_name']));	
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
		$this->mod = M('ProviderLog');
		if (IS_POST) {
			;
		}elseif (IS_GET) {
			$where = array();
			$count = $this->mod->where($where)->count();
			$page = new Page($count,10);
			$list = $this->mod->where($where)->where($where)->limit($page->firstRow.','.$page->listRows)->order('pl_time desc')->select();
			$this->list = $list;
			$this->page = $page->show();
			$this->title = '账号审核';
			$this->display();
		}
	}
	/* public function account(){
		if (IS_POST) {
			if (!empty($_POST['del_id']))
			{
				if (is_array($_POST['del_id']))
				{
					foreach ($_POST['del_id'] as $id)
					{ 
						M('MemberAccount')->where(array('account_id'=>$id))->delete(); 
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
		$count = D('MemberAccount')->where($where)->count();
		$page = new Page($count,10);
		$list = D('MemberAccount')->relation(true)->where($where)->limit($page->firstRow.','.$page->listRows)->order('')->select();
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->assign('title','账号管理');
		$this->display();
	} */
	//审核账号
	public function authAccount(){
		if (IS_AJAX) {
			$pl_id = intval($_POST['pl_id']);
			$pl_status = intval($_POST['status']);
			$member_id = M('ProviderLog')->where(array('pl_id'=>$pl_id))->getField('member_id');
			$res = M('ProviderLog')->where(array('pl_id'=>$pl_id))->setField('pl_status',1);
			if ($res && $member_id) {
				if ($pl_id) {
					M('Member')->where(array('member_id'=>$member_id))->setField('member_type',1);
				}
				$result['code'] = 200;
				$result['msg'] = '审核成功';
				$result['data'] = '';
			}else {
				$result['code'] = 300;
				$result['msg'] = '审核失败';
				$result['data'] = '';
			}
			echo json_encode($result);
		}
		/* if (IS_POST) {
			$account_id = intval($_POST['account_id']);
			$data['pf_id'] = intval($_POST['pf_id']);
			$data['status'] = intval($_POST['status']);
			$res = M('MemberAccount')->where(array('account_id'=>$account_id))->save($data);
			if ($res) {
				$this->success('账号审核成功',U('account'));
			}else {
				$this->error('账号审核失败');
			}
		}elseif (IS_GET){
			$where['account_id'] = intval($_GET['id']);
			if ($where['account_id']) {
				$info = D('MemberAccount')->relation(true)->where($where)->find();
				if ($info) {
					$this->assign('info',$info);
					$this->platform = M('Platform')->order('pf_sort desc')->select();
					$this->assign('title','审核账号-'.$info['account_name']);
					$this->display();
				}else {
					$this->error('没有找到相关信息');
				}
			}
		} */
	}
	//删除账号
	public function account_del(){
		$id = intval($_GET['id']);
		if ($id) {
			$res = M('MemberAccount')->where(array('account_id'=>$id))->delete();
			if ($res) {
				$this->success('删除账号成功');
			}else {
				$this->error('删除账号失败');
			}
		}else {
			$this->error('没有找到相关记录');
		}
	}
	//短信群发
	public function massSMS(){
		if (IS_POST) {
			$content = $_POST['content'];
			$where['contact_type'] = 'mobile';
			if ($_POST['contact_source']) {
				$where['contact_source'] = $_POST['contact_source'];
			}
			$where['contact_status'] = 1;
			$mobile_list = M('ContactList')->where($where)->field('contact_info')->select();
			if (is_array($mobile_list) && $content) {
				foreach ($mobile_list as $key => $val){
					customSendSMS($val['contact_info'], $content);
				}
				$this->success('短信群发成功');
			}else{
				$this->error('短信群发失败,原因:没有找到手机列表或者内容为空');
			}
		}elseif (IS_GET){
			$this->display();
		}
	}
}