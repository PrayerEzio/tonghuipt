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
class SystemArticleController extends GlobalController {
	public function _initialize(){
        parent::_initialize();
		$this->model = M('SystemArticle');
	}
	//列表页
	public function index()
	{
		if (IS_POST) {
			//删除处理
			if (is_array($_POST['del_id']) && !empty($_POST['del_id']))
			{
				foreach ($_POST['del_id'] as $article_id)
				{
					//删除图片
					$article_pic = $this->model->where(array('article_id'=>$article_id))->getField('article_pic');
					if($article_pic)
					{
						@unlink(BasePath.'/Uploads/'.$article_pic);
					}
					$this->model->where(array('article_id'=>$article_id))->delete();
				}
				$this->success('删除成功');
				exit;
			}else {
				$this->error("请选择要操作的对象");
			}
		}elseif (IS_GET){
			$map = array();
			if (trim($_GET['ac_type'])) $map['ac_type'] = array('eq',str_rp($_GET['ac_type'])) ;
			if(trim($_GET['article_title']))$map['article_title'] = array('like','%'.trim($_GET['article_title']).'%');
			$count = $this->model->where($map)->count();
			$page = new Page($count,10);
			$list = $this->model->where($map)->order('article_sort desc')->limit($page->firstRow.','.$page->listRows)->select();
			$this->assign('list',$list);
			$this->assign('title','系统文章');
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
			$id = intval($_POST['article_id']);
			$data['article_title'] = str_rp(trim($_POST['article_title']));
			$data['ac_type'] = str_rp(trim($_POST['ac_type']));
			$data['article_url'] = str_rp(trim($_POST['article_url']));
			$data['seo_title'] = str_rp(trim($_POST['seo_title']));
			$data['seo_key'] = str_rp(trim($_POST['seo_key']));
			$data['seo_desc'] = str_rp(trim($_POST['seo_desc']));
			$data['article_sort'] = intval($_POST['article_sort']);
			$data['article_show'] = 1;
			$data['article_content'] = str_replace('\'','&#39;',$_POST['article_content']);
			$data['article_time'] = NOW_TIME;
			$data['to_type'] = intval($_POST['to_type']);
			$arc_img = 'sa_'.$data['article_time'];
			//图片上传
			if($_FILES['article_pic']['size'])
			{
				if ($id) {
					//删除图片
					$article_pic = $this->model->where(array('article_id'=>$id))->getField('article_pic');
					if($article_pic)
					{
						@unlink(BasePath.'/Uploads/'.$article_pic);
					}
				}
				$param = array('savePath'=>'artic/','subName'=>'','files'=>$_FILES['article_pic'],'saveName'=>$arc_img,'saveExt'=>'');
				$up_return = upload_one($param);
				if($up_return == 'error')
				{
					$this->error('图片上传失败');
					exit;
				}else{
					$data['article_pic'] = $up_return;
				}
			}
			if ($id) {
				$res = $this->model->where(array('article_id'=>$id))->save($data);
			}else {
				$res = $this->model->add($data);
				$id = $res;
			}
			if ($res) {
				//search处理
				$search['url'] = U('Article/detail',array('id'=>$id));
				$search['title'] = $data['article_title'];
				$search['keywords'] = $data['article_key'];
				$search['description'] = $data['article_desc'];
				$search['img'] = $up_return;
				$search_id = $this->model->where(array('article_id'=>$id))->getField('search_id');
				if (empty($search_id)) {
					$search_id = '';
				}
				$search_id = addSearch($search['url'],$search['title'],$search['keywords'],$search['description'],$search_id,$search['img']);
				$this->model->where(array('article_id'=>$id))->setField('search_id',$search_id);
				$this->success('操作成功',U('index'));
			}else {
				$this->error('操作失败');
			}
		}elseif (IS_GET){
			$id = intval($_GET['id']);
			$info = $this->model->where(array('article_id'=>$id))->find();
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