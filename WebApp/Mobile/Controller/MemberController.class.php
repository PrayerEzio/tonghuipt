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

	public function info()
	{
		if (IS_POST)
		{
			$data['nickname'] = trim($_POST['nickname']);
			$data['pwd'] = re_md5($_POST['pwd']);
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
			if ($res)
			{
				$this->success('修改资料成功');
			}else {
				$this->error('网络繁忙,请重试.');
			}
		}elseif (IS_GET) {
			$user_info = M('Member')->where(array('uid'=>$this->mid))->find();
			$province = M('District')->where(array('level'=>1,'status'=>1))->order('d_sort')->select();
			$this->province = $province;
			$this->user_info = $user_info;
			$this->display();
		}
	}

	public function branch()
	{
		if (IS_POST)
		{

		}elseif (IS_GET){
			$where['parent_member_id'] = $this->mid;
			$list = M('Member')->where($where)->order('register_time')->select();
			$all_list = M('Member')->order('register_time')->select();
			foreach ($list as $key => $item)
			{
				$list[$key]['branch_num'] = count(getChildsId($all_list, $item['member_id'], 'member_id', 'parent_member_id'));
			}
			$this->branch_num = count(getChildsId($all_list, $this->mid, 'member_id', 'parent_member_id'));
			$this->list = $list;
			$this->display();
		}
	}

}