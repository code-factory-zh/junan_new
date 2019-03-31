<?php


namespace Buy\Controller;
use Common\Controller\BaseController;
class IndexController extends CommonController{

	private $user;

	public function _initialize() {

		$this -> ignore_token();
		parent::_initialize();

		$this -> user = new \Buy\Model\UserModel;
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
		$this -> lenCheck('code', 5);

		if ($p['pwd'] != $p['very_pwd']) {
			$this -> e('输入的两次密码不一样！');
		}

		$p['password'] = sha1($p['pwd']);
		unset($p['pwd'], $p['very_pwd']);

		$done = $this -> user -> createCompany($p);
		if (!$done) {
			$this -> e('失败！');
		}

		$this -> e(0, '注册成功！');
	}

	/**
	 * 注册功能
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T11:54:57+0800
	 */
	public function login() {

		$this -> _post($p, ['code' => '帐号', 'pwd' => '密码', 'open_id']);
du($this -> _encrypt('www'));
		$this -> lenCheck('code', 6);
		$this -> lenCheck('pwd', 6);

		$done = $this -> user -> select_and_very($p);
		du($done);
	}

	public function index() {

		echo 1;
	}
}