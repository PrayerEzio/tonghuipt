<?php
/**
 * 系统文章
 * @copyright  Copyright (c) 2014-2015 muxiangdao-com Inc.(http://www.muxiangdao.com)
 * @license    http://www.muxiangdao.com
 * @link       http://www.muxiangdao.com
 * @author     muxiangdao-com Team Prayer (283386295@qq.com)
 */
namespace Toadmin\Controller;
use Think\Page;
class NoticeController extends GlobalController {
	public function _initialize(){
        parent::_initialize();
		$this->model = M('Notice');
	}
	//列表页
	public function index()
	{
		if (IS_POST) {
			//删除处理
			if (is_array($_POST['del_id']) && !empty($_POST['del_id']))
			{
				foreach ($_POST['del_id'] as $notice_id)
				{
					//删除图片
					$notice_pic = $this->model->where(array('notice_id'=>$notice_id))->getField('notice_pic');
					if($notice_pic)
					{
						@unlink(BasePath.'/Uploads/'.$notice_pic);
					}
					$this->model->where(array('notice_id'=>$notice_id))->delete();
				}
				$this->success('删除成功');
				exit;
			}else {
				$this->error("请选择要操作的对象");
			}
		}elseif (IS_GET){
			$map = array();
			if (trim($_GET['notice_type'])) $map['notice_type'] = intval($_GET['notice_type']);
			if(trim($_GET['notice_title']))$map['notice_title'] = array('like','%'.trim($_GET['notice_title']).'%');
			$count = $this->model->where($map)->count();
			$page = new Page($count,10);
			$list = $this->model->where($map)->order('notice_sort desc')->limit($page->firstRow.','.$page->listRows)->select();
			$this->assign('list',$list);
			$this->assign('title','展示文章');
			$this->assign('search',$_GET);
			$this->assign('page',$page->show());
			$this->display();
		}
	}
	//curd
	public function curd(){
		if (IS_AJAX) {
			echo '错误!';
		}elseif (IS_POST) {
			$id = intval($_POST['notice_id']);
			$data['notice_title'] = str_rp(trim($_POST['notice_title']));
			$data['notice_type'] = intval($_POST['notice_type']);
			$data['notice_url'] = str_rp(trim($_POST['notice_url']));
			$data['notice_title'] = str_rp(trim($_POST['notice_title']));
			$data['notice_keyword'] = str_rp(trim($_POST['notice_keyword']));
			$data['notice_desc'] = str_rp(trim($_POST['notice_desc']));
			$data['notice_sort'] = intval($_POST['notice_sort']);
			$data['notice_status'] = intval($_POST['notice_status']);
			$data['notice_content'] = str_replace('\'','&#39;',$_POST['notice_content']);
			$data['notice_time'] = NOW_TIME;
			$arc_img = 'sa_'.$data['notice_time'];
			//图片上传
			if($_FILES['notice_pic']['size'])
			{
				if ($id) {
					//删除图片
					$notice_pic = $this->model->where(array('notice_id'=>$id))->getField('notice_pic');
					if($notice_pic)
					{
						@unlink(BasePath.'/Uploads/'.$notice_pic);
					}
				}
				$param = array('savePath'=>'notice/','subName'=>'','files'=>$_FILES['notice_pic'],'saveName'=>$arc_img,'saveExt'=>'');
				$up_return = upload_one($param);
				if($up_return == 'error')
				{
					$this->error('图片上传失败');
					exit;
				}else{
					$data['notice_pic'] = $up_return;
				}
			}
			if ($id) {
				$res = $this->model->where(array('notice_id'=>$id))->save($data);
			}else {
				$res = $this->model->add($data);
				$id = $res;
			}
			if ($res) {
				//search处理
				$search['url'] = U('Notice/detail',array('id'=>$id));
				$search['title'] = $data['notice_title'];
				$search['keywords'] = $data['notice_keywordword'];
				$search['description'] = $data['notice_desc'];
				$search['img'] = $up_return;
				$search_id = $this->model->where(array('notice_id'=>$id))->getField('search_id');
				if (empty($search_id)) {
					$search_id = '';
				}
				$search_id = addSearch($search['url'],$search['title'],$search['keywords'],$search['description'],$search_id,$search['img']);
				$this->model->where(array('notice_id'=>$id))->setField('search_id',$search_id);
				$this->success('操作成功',U('index'));
			}else {
				$this->error('操作失败');
			}
		}elseif (IS_GET){
			$id = intval($_GET['id']);
			$info = $this->model->where(array('notice_id'=>$id))->find();
			$this->assign('info',$info);
			$this->display();
		}
	}
	//在线编辑	
	public function ajax()
	{
		$id = intval($_GET['id']);
		switch(trim($_GET['branch']))
		{
			case 'bank_sort':
			$this->model->where(array('bank_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
			break;
			case 'bank_name':
			$this->model->where(array('bank_id'=>$id))->setField($_GET['column'],trim($_GET['value']));
			break;	
			case 'case_sort':
			$this->model->where(array('bank_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
			break;					
		}
	}
}