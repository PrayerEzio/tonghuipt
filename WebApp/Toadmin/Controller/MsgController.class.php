<?php
/**
 * 留言
 * @copyright  Copyright (c) 2014-2015 muxiangdao-com Inc.(http://www.muxiangdao.com)
 * @license    http://www.muxiangdao.com
 * @link       http://www.muxiangdao.com
 * @author     muxiangdao-com Team Prayer (283386295@qq.com)
 */
namespace Toadmin\Controller;
use Think\Page;
class MsgController extends GlobalController
{
	public function _initialize() 
	{
        parent::_initialize();
        $this->model = M('Message');
        $this->title = '留言板';
	}
	public function index()
	{
		if(IS_POST) //删除
		{
			if (!empty($_POST['del_id']))
			{
				if (is_array($_POST['del_id']))
				{
					foreach ($_POST['del_id'] as $ac_id)
					{ 
						$this->model->where(array('id'=>$ac_id))->delete(); 
					}
					$this->success("操作成功",U('index'));  	
					exit;						
				}
			}else {
				$this->error("请选择要操作的对象"); 	
			}				
		}elseif (IS_GET){
			$where = array();
			$order = '';
			$count = $this->model->where($where)->count();
			$page = new Page($count,10);
			$list = $this->model->where($where)->order($order)->limit($page->firstRow.','.$page->listRows)->select();
			$this->assign('list',$list);
			$this->assign('page',$page->show());
			$this->display();
		}
	}
	public function detail()
	{
		$id = intval($_GET['id']);
		$info = $this->model->where(array('id'=>$id))->find();
		$this->assign('info',$info);
		$this->display();
	}
}