<?php
/**
 * SEO后台
 * @copyright  Copyright (c) 2014-2015 muxiangdao-com Inc.(http://www.muxiangdao.com)
 * @license    http://www.muxiangdao.com
 * @link       http://www.muxiangdao.com
 * @author     muxiangdao-com Team Prayer (283386295@qq.com)
 */
namespace Toadmin\Controller;
use Think\Page;
class SeoController extends GlobalController{
	public function _initialize(){
		parent::_initialize();
		$this->mod = M('Seo');
		$this->local = 'seo设置';
	}
	
	public function index(){
		if (IS_POST) {
			if (!empty($_POST['del_id'])) {
				foreach ($_POST['del_id'] as $key => $del){
					$this->mod->where(array('id'=>$del))->delete();
				}
				$this->success('删除成功');
			}else {
				$this->error('请选择所要删除的对象');
			}
		}elseif (IS_GET) {
			$where = array();
			$order = 'type desc';
			$count = $this->mod->where($where)->count();
			$page = new Page($count,10);
			$this->list = $this->mod->where($where)->limit($page->firstRow.','.$page->listRows)->order($order)->select();
			$this->page = $page->show();
			$this->assign('title','SEO设置');
			$this->display();
		}
	}
	
	public function curdSeo(){
		if (IS_POST){
			$id = intval($_POST['id']);
			$data = array(
					'title' => str_rp(trim($_POST['title'])),
					'keywords' => str_rp(trim($_POST['keywords'])),
					'description' => str_rp(trim($_POST['description'])),
					'cavalue' => str_rp(trim($_POST['cavalue'])),
					'remark' => str_rp(trim($_POST['remark'])),
					'type' => intval($_POST['type']),
			);
			if ($id) {
				$res = $this->mod->where(array('id'=>$id))->save($data);
				if ($res) {
					$this->success('更新seo成功',U('index'));
				}else {
					$this->error('更新seo失败');
				}
			}else {
				$id = $this->mod->add($data);
				if ($id) {
					$this->success('新增seo成功',U('index'));
				}else {
					$this->error('新增seo失败');
				}
			}
		}elseif (IS_GET){
			$id = intval($_GET['id']);
			if ($id) {
				$where = array('id'=>$id);
				$this->info = $this->mod->where($where)->find();
			}
			$this->display();
		}
	}
}