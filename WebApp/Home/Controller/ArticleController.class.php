<?php
/**
 * 文章
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Home\Controller;
use Home\Controller\BaseController;
use Common\Lib\Cart\Cart;
class ArticleController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->model = M('SystemArticle');
	}
	public function detail(){
		$id = intval($_GET['id']);
		$this->info = $this->model->where(array('article_id'=>$id,'article_show'=>1))->find();
		$this->display();
	}
	public function document(){
		$id = intval($_GET['id']);
		$info = M('Document')->where(array('doc_id'=>$id))->find();
		$this->info = $info;
		$this->display('detail');
	}
}