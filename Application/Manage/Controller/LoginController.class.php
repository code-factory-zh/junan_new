<?php

/**
 * @Dec    登录控制器
 * @Auther QiuXiangCheng
 * @Date   2017/12/12
 */
namespace Manage\Controller;
use Common\Controller\BaseController;
class LoginController extends CommonController {

	private $user;

	private $index;

	// 不需要验证TOKEN
	// protected static $token = 0;

	public function _initialize() {

		parent::_initialize();
		$this -> user  = new \Manage\Model\UserModel;
		$this -> index = new \Manage\Model\IndexModel;
	}

	public function index() {

		$this -> _post();
	}

	/**
	 * 用户登录
	 * @param md5($verify)
	 * @param $phone
	 * @param $pwd
	 */
	public function do() {

		$this -> ignore_token() -> _post($arr, ['phone', 'pwd', 'verify']);
		$this -> phoneCheck($arr['phone']);
		if (!$this -> verify_check($arr['verify'])) {
			$this -> e('验证码错误');
		}
		$arr['pwd'] = $this -> _encrypt($arr['pwd']);
		if (!($u = $this -> user -> UserCheck($arr))) {
			$this -> e('用户名或密码不正确');
		}
		unset($u['pwd']);
		$token = $this -> token_fetch($u);
		if (!$this -> save_token($token, $u)) {
			$this -> e('无法生成TOKEN');
		}
		$this -> user -> UserLogin($u['phone']); // 记录用户登录情况
		$this -> rel(['token' => $token]) -> e();
	}

	/**
	 * 验证用户是否已登录
	 * @param $token
	 */
	public function lc() {

		$this -> ignore_bsid() -> _post($p);
		if (!($u = $this -> getUserByToken($p['token']))) {
			$this -> e('Token Invalid!');
		}
		$this -> e(0, 'on-line');
	}

	// 基地列表
	public function list() {

		// 为基地分组
		// 我的基地和我加入的基地
		$this -> ignore_bsid() -> _get($arr);
		$l = $this -> index -> getAqList($this -> u['id']);
		$this -> rel = ['self' => [], 'join' => []];
		foreach ($l as $list) {
			$t = $list['type'];
			unset($list['type']);
			if ($t == 1) {
				array_push($this -> rel['self'], $list);
			} else if ($t == 2) {
				array_push($this -> rel['join'], $list);
			}
		}
		$this -> e();
	}

 	// 生成图片验证码 
 	public function getverifycode(){

		$config = array(
			'imageW' => 0,
			'fontSize' => 35, // 验证码字体大小
			'length' => 4, // 验证码位数
			'useNoise' => true, // 关闭验证码杂点
			'bg' =>  array(255, 255, 255),  // 背景颜色
		);
 		$verify = new \Think\Verify($config);
 		$verify -> codeSet = '0123456789';
 		$verify -> entry();
 	}

 	/**
 	 * 验证码检查
 	 */
	private function verify_check($code) {

		if (!$this -> check_img_verify($code)) {
 			return false;
 		}
 		return true;
	}

	/*********************** 注册功能 BEGIN *************************/
	// 发送验证码
	public function sendVerify() {

		$this -> ignore_token() -> _post($p, ['phone']);
		$this -> phoneCheck($p['phone']);
		$this -> el($this -> user -> registerCheck($p['phone']), '该手机号已被注册', true);
		$verify = mt_rand(1000, 9999); //echo $verify;
		$this -> sendMessage($p['phone'], $verify, ''); // 发送短信验证码
		self::redisInstance() -> setEx('PHONE_' . $p['phone'], C('PHONE_VERIFY_TIME_OUT'), $verify);
		$this -> e(0, '验证码已发送');
	}

	// 验证码检查
	// 注册页面下一步
	public function verifyCheck() {

		$this -> ignore_token() -> _post($p, ['phone', 'verify_code']);
		$this -> baseRegisterCheck($p);
		$this -> checkVerifyCodeByPhoneNum($p);
		$this -> e();
	}

	// 注册新用户
	// 最后一步
	public function register() {

		$this -> ignore_token() -> _post($p, ['phone', 'verify_code', 'pwd', 'verify_pwd']);
		$this -> baseRegisterCheck($p);
		$this -> lenCheck('pwd', 6, 16);
		$this -> checkVerifyCodeByPhoneNum($p);
		if ($p['pwd'] != $p['verify_pwd']) {
			$this -> e('两次输入的密码不一样');
		}
		if (!preg_match("/^[\w\d\_]+$/si", $p['pwd'])) {
			$this -> e('密码不规范');
		}
		self::redisInstance() -> delete('PHONE_' . $p['phone']);
		$p['pwd'] = $this -> _encrypt($p['pwd']);
		$login = 1; // 默认为用户登录
		// 插入数据库
		if (!($id = $this -> user -> addUser($p))) {
			$this -> e('失败,未知错误');
		}
		// $p['login'] = 1时 注册完成后登录
		if (isset($p['login'])) {
			$this -> suiValue('login', [1, 2]);
			$login = $p['login'];
		}
		// 为用户执行登录
		if ($login == 1 && $token = $this -> token_fetch($p)) {
			$u = ['id' => $id, 'phone' => $p['phone']];
			if (!$this -> save_token($token, $u)) {
				$this -> e('用户登录失败，无法生成TOKEN');
			}
			$rel['us_id'] = $id;
			$rel['token'] = $token;
		}
		$this -> rel($rel) -> e();
	}

	// 注册时的基础检查
	private function baseRegisterCheck($p) {

		$this -> phoneCheck($p['phone']);
		$this -> isInt(['verify_code']);
		$this -> lenCheck('verify_code', 4, 4);
		$this -> el($this -> user -> registerCheck($p['phone']), '该手机号已被注册', true);
	}

	// 根据用户手机检查验证码
	private function checkVerifyCodeByPhoneNum($p) {

		$verify = self::redisInstance() -> get('PHONE_' . $p['phone']);
		if (!$verify || $p['verify_code'] != $verify) {
			$this -> e('验证码错误');
		}
	}
	/*********************** 注册功能 END *************************/
}