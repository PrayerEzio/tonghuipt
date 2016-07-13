<?php
/**
 * 基类
 * @package    Base
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Home\Controller;
use Think\Controller;

class BaseController extends Controller{
	public function __construct()
	{
		parent::__construct();
		//读取配置信息
		$web_stting = F('setting');
		if($web_stting === false) 
		{
			$params = array();
			$list = M('Setting')->getField('name,value');
			foreach ($list as $key=>$val) 
			{
				$params[$key] = unserialize($val) ? unserialize($val) : $val;
			}
			F('setting', $params); 				
			$web_stting = F('setting');
		}
		$this->assign('web_stting',$web_stting);
		//站点状态判断
		if($web_stting['site_status'] != 1 || 1 == 1){
		   echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		   echo $web_stting['closed_reason'];
		   exit;	
		}else {
			/* $link = M('Link')->where(array('status'=>1))->order('sort DESC')->select();
			$this->assign('link',$link); */
			$this->mid = session('member_id');
			$this->assign('seo',seo());
			$this->about = M('SystemArticle')->where(array('ac_type'=>'about'))->order('article_sort desc')->select();
			$this->service = M('SystemArticle')->where(array('ac_type'=>'service'))->order('article_sort desc')->select();
			$this->article_class = M('ArticleClass')->where(array('ac_parent_id'=>0))->order('ac_sort desc')->select();
		}
	}
	public function check_login(){
		if(session('member_id') || cookie('autologin'))
		{
			$this->mid = session('member_id');
			$member = M('Member')->where(array('member_id'=>$this->mid))->find();
			$this->assign('member',$member);
			if (CONTROLLER_NAME == 'Login') {
				$this->redirect('Member/index',$_GET);//已经登录直接跳转会员中心
				exit();
			}
		}else {
			if (CONTROLLER_NAME != 'Login') {
				$this->error('您还未登录,请先进行登录操作.',U('Login/index'));
// 				$this->redirect('Index/index',$_GET);//已经登录直接跳转会员中心
				exit();
			}
		}
	}
	/**
	 * 获取下级城市
	 */
	public function getDisctrict(){
		if (IS_AJAX) {
			$where['upid'] = intval($_POST['id']);
			$where['status'] = 1;
			$list = M('District')->where($where)->order('d_sort')->select();
			$data['city'] = $list;
			if ($list[0]['level'] == 2) {
				$data['level'] = 'city';
			}elseif ($list[0]['level'] == 3){
				$data['level'] = 'area';
			}
			echo json_encode($data);
		}
	}
	/**
	 * 添加新的收货地址
	 */
	public function curdAddress(){
		if (IS_POST) {
			$id = intval($_POST['addr_id']);
			$data['member_id'] = $this->mid;
			$data['name'] = str_rp($_POST['name'],1);
			$data['province_id'] = intval($_POST['province']);
			$data['city_id'] = intval($_POST['city']);
			$data['area_id'] = intval($_POST['area']);
			$data['addr'] = str_rp($_POST['addr'],1);
			$data['zip'] = intval($_POST['zip']);
			$data['mobile'] = str_rp($_POST['mobile'],1);
			$data['addr_tag'] = str_rp($_POST['addr_tag'],1);
			if ($id) {
				$rc = M('MemberAddrs')->where(array('addr_id'=>$id))->save($data);
			}else {
				$rc = M('MemberAddrs')->add($data);
			}
		}
		if (IS_AJAX) {
			$res['addr_id'] = $rc;
			$res['province'] = getDistrictName($data['province_id']);
			$res['city'] = getDistrictName($data['city_id']);
			$res['area'] = getDistrictName($data['area_id']);
			echo json_encode($res);
		}elseif (IS_POST) {
			$this->redirect('Member/address');
		}
	}
	/**
	 * ajax获取品牌下商品和货物
	 */
	public function getGoods(){
		if (IS_AJAX) {
			$gc = I('post.gc','0','int');
			$where['gc_id'] = $gc;
			$brand_id = intval($_POST['brand_id']);
			$where['brand_id'] = $brand_id;
			$where['goods_status'] = 1;
			//$list = M('Goods')->where($where)->field('goods_id,goods_name')->order('goods_price desc,goods_storage desc,goods_name')->select();
			$where1 = $where;
			$where1['goods_price'] = array('gt',0);
			$list1 = M('Goods')->where($where1)->field('goods_id,goods_name')->order('goods_name')->select();
			foreach ($list1 as $key => $val){
				$goods_ids1 .= $val['goods_id'].',';
			}
			$goods_ids1 = substr($goods_ids1, 0, -1);
			if (!empty($goods_ids1)) {
				$GoodsSpec1 = M('GoodsSpec')->where(array('goods_id'=>array('in',$goods_ids1)))->field('spec_id,spec_name')->order('spec_name')->select();
			}
			$where2 = $where;
			$where2['goods_price'] = 0;
			$list2 = M('Goods')->where($where2)->field('goods_id,goods_name')->order('goods_name')->select();
			foreach ($list2 as $key => $val){
				$goods_ids2 .= $val['goods_id'].',';
			}
			$goods_ids2 = substr($goods_ids2, 0, -1);
			if (!empty($goods_ids2)) {
				$GoodsSpec2 = M('GoodsSpec')->where(array('goods_id'=>array('in',$goods_ids2)))->field('spec_id,spec_name')->order('spec_name')->select();
			}
			$data['GoodsSpec'] = array();
			if (empty($GoodsSpec1) && $GoodsSpec2) {
				$data['GoodsSpec'] = $GoodsSpec2;
			}elseif ($GoodsSpec1 && empty($GoodsSpec2)){
				$data['GoodsSpec'] = $GoodsSpec1;
			}elseif ($GoodsSpec1 && $GoodsSpec2){
				$data['GoodsSpec'] = array_merge($GoodsSpec1,$GoodsSpec2);
			}
			$list = array();
			if (empty($list1) && !empty($list2)) {
				$list = $list2;
			}elseif (!empty($list1) && empty($list2)){
				$list = $list1;
			}elseif ($list1 && $list2){
				$list = array_merge($list1,$list2);
			}
			$data['list'] = $list;
			echo json_encode($data);
		}else {
			echo '非法操作';
		}
	}
}