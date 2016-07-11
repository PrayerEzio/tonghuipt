<?php
/**
 * 广告
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Toadmin\Controller;
use Think\Page;
class BillboardsController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->model = M('AdvPosition');
		$this->apcode = array(
			'banner' => '首页幻灯片',
			'img_nav' => '首页图片导航栏',
			'static_img' => '首页静态广告',
			'foot-img' => '底部许可证',
		);
	}	
	//广告管理
	public function position()
	{	
		if(IS_POST) //删除
		{
			if (!empty($_POST['del_id']) && is_array($_POST['del_id']))
			{
				foreach ($_POST['del_id'] as $ap_id)
				{
					//删除图片
					$ap_pic = $this->model->where('ap_id='.$ap_id)->getField('ap_pic');
					if($ap_pic)
					{
						@unlink(BasePath.'/Uploads/'.$ap_pic);						
					}					
					$this->model->where('ap_id='.$ap_id)->delete(); 
				}
				$this->success("操作成功",U('position'));  	
				exit;						
			}else {
				$this->error("请选择要操作的对象"); 	
			}				
		}else{	
			$map = array();
			if($_GET['ap_class'])$map['ap_class'] = array('eq',intval($_GET['ap_class']));	
			if(trim($_GET['ap_name']))$map['ap_name'] = array('like','%'.trim($_GET['ap_name']).'%');
			if(trim($_GET['ap_code']))$map['ap_code'] = array('eq',trim($_GET['ap_code']));
				
			$totalRows = $this->model->where($map)->count();
			$page = new Page($totalRows,10);	
			$list = $this->model->where($map)->limit($page->firstRow.','.$page->listRows)->order('ap_sort desc,ap_id asc')->select();				
			foreach ($list as $key => $val){
				$list[$key]['ap_code_title'] = $this->apCode[$val['ap_code']];
			}
			$this->assign('list',$list);
			$this->assign('search',$_GET);	
			$this->assign('show_page',$page->show());			
			$this->display('position_list');
		}
	}
	//广告添加
	public function add()
	{
		if(IS_POST)
		{
			$data = array();
			$data['ap_name'] = str_rp(trim($_POST['ap_name']));
			$data['ap_code'] = str_rp(trim($_POST['ap_code']));
			$data['ap_intro'] = str_rp(trim($_POST['ap_intro']));
			$data['ap_link'] = str_rp(trim($_POST['ap_link']));
			$data['ap_price'] = price_format($_POST['ap_price']);
			$data['ap_class'] = intval($_POST['ap_class']);
			$data['is_use'] = intval($_POST['is_use']);
			$data['ap_width'] = intval($_POST['ap_width']);
			$data['ap_height'] = intval($_POST['ap_height']);
			$data['ap_sort'] = intval($_POST['ap_sort']);
			$data['default_content'] = str_rp(trim($_POST['default_content']));
			$ap_id = $this->model->add($data);

			if(!empty($_FILES['default_pic']['name']) && $ap_id)
			{
			    $param = array('savePath'=>'info/','subName'=>'','files'=>$_FILES['default_pic'],'saveName'=>'info_'.$ap_id,'saveExt'=>'');				
				$up_return = upload_one($param);
				if($up_return == 'error')
				{
					$this->error('图片上传失败');
					exit;	
				}else{	
					$this->model->where('ap_id='.$ap_id)->setField('ap_pic',$up_return);
				}					
			}			
			$this->success("操作成功",U('position'));  	
			exit;					
		}else{
			$this->display('position_add');	
		}
	}
	
	//编辑
	public function edit()
	{
		if(IS_POST)
		{
			$ap_id = intval($_POST['ap_id']);
			if($ap_id)
			{
				$data = array();
				$data['ap_name'] = str_rp(trim($_POST['ap_name']));
				$data['ap_code'] = str_rp(trim($_POST['ap_code']));
				$data['ap_intro'] = str_rp(trim($_POST['ap_intro']));
				$data['ap_link'] = str_rp(trim($_POST['ap_link']));
				$data['ap_price'] = price_format($_POST['ap_price']);
				$data['ap_class'] = intval($_POST['ap_class']);
				$data['is_use'] = intval($_POST['is_use']);
				$data['ap_width'] = intval($_POST['ap_width']);
				$data['ap_height'] = intval($_POST['ap_height']);
				$data['ap_sort'] = intval($_POST['ap_sort']);
				$data['default_content'] = str_rp(trim($_POST['default_content']));	
				
				if(!empty($_FILES['default_pic']['name']))
				{
					$param = array('savePath'=>'info/','subName'=>'','files'=>$_FILES['default_pic'],'saveName'=>'info_'.$ap_id,'saveExt'=>'');				
					$up_return = upload_one($param);
					if($up_return == 'error')
					{
						$this->error('图片上传失败');
						exit;	
					}else{	
						$data['ap_pic'] = $up_return;
					}					
				}
				
				$this->model->where('ap_id='.$ap_id)->save($data);
				$this->success("操作成功",U('position'));  	
				exit;											
			}
		}else{
			$ap_id = intval($_GET['ap_id']);
			$vo = $this->model->where('ap_id='.$ap_id)->find();
			$this->assign('vo',$vo);
			$this->display('position_edit');		
		}
	}

}