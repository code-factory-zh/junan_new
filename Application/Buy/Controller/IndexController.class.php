<?php

/**
 * 公众号购买课程主页
 * @Auther QiuXiangCheng
 * @Date   2019/03/31
 */

namespace Buy\Controller;
use Common\Controller\BaseController;
use Manage\Model\AccountModel;
class IndexController extends CommonController{

	private $user;

	public function _initialize() {

		$this -> ignore_token();
		parent::_initialize();

		$this -> user    = new \Buy\Model\UserModel;
		$this -> course  = new \Buy\Model\CourseModel;
		$this -> order   = new \Buy\Model\OrderModel;
		$this -> account = new \Manage\Model\AccountModel;
	}

	/**
	 * 购买课程主页
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T14:41:26+0800
	 */
	public function index() {

		$this -> _get($p, ['open_id']);

		if (!count($this -> ufo)) {
			$this -> e('请先登录！');
		}

		$data = [
			'course_list' => [],
			'history_list' => [],
			'btn_buy' => '买课',
			'btn_invitation' => '邀请学员',
			'user_ttl' => '帐号有效期至：2019年3月31日',
		];

		$data['course_list'] = $this -> course -> getCourseList(['is_deleted' => 0], 2);
		$data['history_list']['buy_amount']    = $this -> order -> getTotalOrderAmount($this -> ufo['id']);
		$data['history_list']['learn_amount']  = $this -> account -> getAccountInfoCount(['company_id' => $this -> ufo['id'], 'status' => 0]);
		$data['history_list']['examed_amount'] = 0;

		$this -> rel($data) -> e(0);
	}

	/**
	 * 用户注册页面
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T11:14:35+0800
	 */
	public function register() {

		$this -> _get($p);
		$data = [
			'title' => '提交公司资料',
			'industry' => [
				1 => '冶金行业',
				2 => '有色行业',
				3 => '建材行业',
				4 => '机械行业',
				5 => '轻工行业',
				6 => '纺织行业',
				7 => '烟草行业',
			],
		];

		$this -> rel($data) -> e();
	}

	/**
	 * 注册企业
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T11:15:00+0800
	 */
	public function doregist() {

		$needle = [
			'company_name' => '企业名称',
			'credit_code' => '企业信用代码',
			'province' => '省',
			'city' => '城市',
			'county' => '县',
			'address' => '详细地址',
			'industry' => '行业类型',
			'code' => '企业帐号',
			'pwd' => '密码',
			'very_pwd' => '确认密码',
		];

		$this -> _post($p, $needle);
		$this -> lenCheck('pwd', 6);
		$this -> lenCheck('very_pwd', 6);
		$this -> lenCheck('code', 6);

		if ($p['pwd'] != $p['very_pwd']) {
			$this -> e('输入的两次密码不一样！');
		}

		$p['password'] = $this -> _encrypt($p['pwd']);
		unset($p['pwd'], $p['very_pwd']);

		$done = $this -> user -> createCompany($p);
		if (!$done) {
			$this -> e('失败！');
		}

		$this -> e(0, '注册成功！');
	}

	/**
	 * 登录功能
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T11:54:57+0800
	 */
	public function login() {

		$this -> _post($p, ['code' => '帐号', 'pwd' => '密码', 'open_id']);
		$this -> lenCheck('code', 6);
		$this -> lenCheck('pwd', 6);

		if ($this -> user -> login_very($p)) {
			$this -> e();
		}
		$this -> e('登录失败！');
	}

	/**
	 * 通过 open_id 验证当前是否可登录
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T13:27:51+0800
	 */
	public function lgcheck() {

		$this -> _get($p, ['open_id']);
		$find = $this -> user -> userCheck(['open_id' => $p['open_id']]);
		if (!$find) {
			$this -> e('登录验证失败，没有发现当前用户登录态！');
		}
		$this -> e();
	}
}