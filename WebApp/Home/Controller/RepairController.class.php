<?php
/**
 * 售后维修
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Home\Controller;
use Home\Controller\BaseController;
class RepairController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->model = D('Repair');
	}
	public function index(){
		$where['doc_code'] = 'repair';
		$this->info = M('Document')->where($where)->find();
		$notice_where['notice_status'] = 1;
		$notice_where['notice_type'] = 6;
		$this->notice = M('Notice')->where($notice_where)->order('notice_sort desc')->select();
		$this->display();
	}
	/**
	 * 申请维修
	 */
	public function apply(){
		if (!$this->mid) {
			$this->error('请先登录再提交申请',U('Login/index'));
		}
		$this->brand = M('GoodsBrand')->where(array('brand_status'=>1))->order('brand_sort desc')->select();
		$this->breakdown = M('Breakdown')->where(array('bd_status'=>1,'bd_upid'=>0))->order('bd_sort desc')->select();
		$this->display();
	}
	public function apply_address(){
		if (IS_POST) {
			$_SESSION['Repair']['spec_id'] = intval($_POST['spec_id']);
			$_SESSION['Repair']['goods_id'] = M('GoodsSpec')->where(array('spec_id'=>intval($_POST['spec_id'])))->getField('goods_id');
			$_SESSION['Repair']['spec_name'] = str_rp($_POST['spec_name'],1);
			$_SESSION['Repair']['bd_id'] = intval($_POST['bd_id']);
			$_SESSION['Repair']['machine_code'] = str_rp($_POST['code'],1);
			$_SESSION['Repair']['content'] = str_rp($_POST['content'],1);
			$this->express = M('Express')->where(array('e_state'=>1))->select();
			$this->display();
		}
	}
	public function apply_success(){
		if (IS_POST) {
			if ($_SESSION['Repair']) {
				$data['rp_sn'] = order_sn();
				$data['goods_id'] = intval($_SESSION['Repair']['goods_id']);
				$data['spec_id'] = intval($_SESSION['Repair']['spec_id']);
				$data['bd_id'] = intval($_SESSION['Repair']['bd_id']);
				$data['machine_code'] = str_rp($_SESSION['Repair']['machine_code'],1);
				$data['spec_name'] = str_rp($_SESSION['Repair']['spec_name'],1);
				$data['content'] = str_rp($_SESSION['Repair']['content']);
				$data['member_id'] = $this->mid;
				$data['addtime'] = NOW_TIME;
				$data['express_style'] = intval($_POST['style']);
				$r = str_rp($_POST['express2'],1);
				if (empty($r)) {
					$data['express'] = str_rp($_POST['express'],1);
				}else {
					$data['express'] = str_rp($_POST['express2'],1);
				}
				$data['express_sn'] = trim($_POST['express_sn']);
				$data['rp_status'] = 0;
			}else {
				$this->error('申请表单验证失败',U('apply'));
			}
			$res = $this->model->add($data);
			if ($res) {
				unset($_SESSION['Repair']);
				$log['rp_id'] = $res;
				$log['log_content'] = '会员申请维修机器号'.$data['machine_code'];
				$log['log_time'] = NOW_TIME;
				$log['is_view'] = 1;
				M('RepairLog')->add($log);
				$this->display();
			}else {
				$this->error('申请维修失败.');
			}
		}
	}
	/**
	 * 获取品牌下的商品
	 */
	public function ajaxGetGoodsList(){
		if (IS_AJAX) {
			$brand_id = intval($_POST['brand_id']);
			if ($brand_id) {
				$goods_list = M('Goods')->where(array('brand_id'=>$brand_id))->select();
				$goods_ids = '';
				foreach ($goods_list as $key => $val){
					$goods_ids .= $val['goods_id'].',';
				}
				$goods_ids = substr($goods_ids, 0, -1);
				if (!empty($goods_ids)) {
					$list = M('GoodsSpec')->where(array('goods_id'=>array('in',$goods_ids)))->field('spec_id,goods_id,spec_name')->order('spec_name')->select();
				}
				if (!empty($list)) {
					$result['code'] = 200;
					$result['error_msg'] = '';
					$result['data'] = $list;
				}else {
					$result['code'] = 300;
					$result['error_msg'] = '没有找到相关记录';
					$result['data'] = '';
				}
				echo json_encode($result);
			}
		}
	}
	/**
	 * 获取当前故障大类下的子级分类
	 */
	public function ajaxGetSubBreakdown(){
		if (IS_AJAX) {
			$bd_upid = intval($_POST['bd_id']);
			if ($bd_upid) {
				$list = M('Breakdown')->where(array('bd_status'=>1,'bd_upid'=>$bd_upid))->order('bd_sort desc')->select();
				if (!empty($list)) {
					$result['code'] = 200;
					$result['error_msg'] = '';
					$result['data'] = $list;
				}else {
					$result['code'] = 300;
					$result['error_msg'] = '没有找到相关记录';
					$result['data'] = '';
				}
				echo json_encode($result);
			}
		}
	}
}