<?php
/**
 * 通告
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Home\Controller;
use Home\Controller\BaseController;
use Common\Lib\Cart\Cart;
class NoticeController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->model = M('Notice');
	}
	public function detail(){
		$id = intval($_GET['id']);
		$where['notice_id'] = $id;
		$where['notice_status'] = 1;
		$info = $this->model->where($where)->find();
		$this->info = $info;
		$this->display();
	}
}