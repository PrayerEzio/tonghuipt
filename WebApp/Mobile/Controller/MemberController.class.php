<?php
/**
 * 会员中心
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Muxiangdao\DesUtils;
use Think\Page;

class MemberController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->check_login();
	}

	/**
	 * 会员中心.
	 */
	public function index()
	{
		$where['member_id'] = $this->mid;
		$user_info = M('Member')->where($where)->find();
		$this->user_info = $user_info;
		$this->display();
	}

	//登出
	public function logout()
	{
		session('member_id',null);
		redirect(U('Index/index'));
	}

	//个人资料
	public function info()
	{
		if (IS_POST)
		{
			$data['nickname'] = trim($_POST['nickname']);
			$data['member_name'] = trim($_POST['member_name']);
			$data['gender'] = intval($_POST['gender']);
			$data['province'] = intval($_POST['province']);
			$data['city'] = intval($_POST['city']);
			$data['area'] = intval($_POST['area']);
			if(!empty($_FILES['avatar']['size'])){
				$arc_img = 'mid_avatar_'.$this->mid;
				$param = array('savePath'=>'member/','subName'=>'','files'=>$_FILES['avatar'],'saveName'=>$arc_img,'saveExt'=>'');
				$up_return = upload_one($param);
				if($up_return == 'error'){
					$this->error('图片上传失败');
					exit;
				}else{
					$data['avatar'] = $up_return;
				}
			}
			$data['wechat'] = trim($_POST['wechat']);
			$data['alipay'] = trim($_POST['alipay']);
			$res = M('Member')->where(array('member_id'=>$this->mid))->save($data);
			$this->success('修改资料成功');
		}elseif (IS_GET) {
			$user_info = M('Member')->where(array('member_id'=>$this->mid))->find();
			$province = M('District')->where(array('level'=>1,'status'=>1))->order('d_sort')->select();
			$this->province = $province;
			$this->user_info = $user_info;
			$this->display();
		}
	}

	//我的团队
	public function branch()
	{
		if (IS_POST)
		{

		}elseif (IS_GET){
			$all_list = M('Member')->select();
			$where['parent_member_id'] = $this->mid;
			$list = M('Member')->where($where)->order('register_time')->select();
			$field = 'member_id,parent_member_id';
			$loop = 9;
			foreach ($list as $key => $item)
			{
				$list[$key]['branch_num'] = count($this->getChildsMember($item['member_id'],$field,$loop));//count(getChildsId($all_list,$item['member_id'],'member_id','parent_member_id',$loop));//
			}
			$this->branch_num = count($this->getChildsMember($this->mid,$field,$loop));//count(getChildsId($all_list,$this->mid,'member_id','parent_member_id',$loop));//
			$this->list = $list;
			$this->display();
		}
	}

	//获取子级id
	private function getChildsMember($parent_member_id,$field = '*',$loop = 9999)
	{
		$array = array();
		if (!$loop)
		{
			return $array;
		}
		$loop--;
		$array = M('Member')->where(array('parent_member_id'=>$parent_member_id))->field($field)->select();
		if (!empty($array))
		{
			$child_array = array();
			foreach ($array as $v){
				$child = $this->getChildsMember($v['member_id'],$field,$loop);
				if ($child)
				{
					$child_array = array_merge($child_array,$child);
				}
			}
			$array = array_merge($array, $child_array);
			return $array;
		}else {
			return $array;
		}
	}

	//账单
	public function bill()
	{
		$bill_type = intval($_GET['type']);
		$where['bill_type'] = $bill_type;
		$where['member_id'] = $this->mid;
		$count = M('MemberBill')->where($where)->count();
		$page = new Page($count,10);
		$list = M('MemberBill')->where($where)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();
		$this->list = $list;
		$this->display();
	}

	//站内信
	public function letter()
	{
		$where['member_id'] = $this->mid;
		$count = M('MemberLetter')->where($where)->count();
		$page = new Page($count,10);
		$list = M('MemberLetter')->where($where)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();
		$this->list = $list;
		$this->display();
	}
}