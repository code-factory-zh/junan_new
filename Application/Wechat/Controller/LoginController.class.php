<?php

/**
 * @Dec    主页控制器
 * @Auther QiuXiangCheng
 * @Date   2019/01/13
 */

namespace Wechat\Controller;
use Manage\Model\AccountModel;
use Wechat\Model\UserModel;
class LoginController extends CommonController {

	private $user;
	private $account;

	const token_salt = 'junan.com:wx_app_session_key:';
	const wx_app = 'https://api.weixin.qq.com/sns/jscode2session';
	const session_otime = 86400;

	public function _initialize() {

		parent::_initialize();
		$this -> account = new \Manage\Model\AccountModel;
		$this -> user = new \Wechat\Model\UserModel;
	}


	/**
	 * 根据用户手机号取企业数据
	 * @Author   邱湘城
	 * @DateTime 2019-01-25T00:22:33+0800
	 */
	public function getCompanyId() {

		$this -> ignore_token() -> _post($p, ['mobile']);
		$us = $this -> user -> getCompanyInfo($p['mobile'], 'a.company_id, c.company_name');
		if (!count($us)) {
			$this -> e('获取企业列表失败！');
		}
		$this -> rel($us) -> e();
	}

	/**
	 * 根据code取得openid
	 * @Author   邱湘城
	 * @DateTime 2019-01-16T21:40:59+0800
	 */
	public function get_open_id($code) {

		$auth = self::getScreat();
		$url = self::wx_app . "?appid={$auth[0]}&secret={$auth[1]}&js_code={$code}&grant_type=authorization_code";
		return $this -> httpGet($url);
	}

	private static function getScreat() {

		$fi = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/cert/screat');
		return explode("\n", trim($fi));
	}

	/**
	 * 登录功能
	 * @Author   邱湘城
	 * @DateTime 2019-01-15T21:25:40+0800
	 */
	public function dologin() {

		$this -> ignore_token() -> _post($p, ['company_id', 'mobile', 'code']);
		$this -> isint(['company_id', 'mobile']);
		$this -> phoneCheck($p['mobile']);

		$where = ['a.company_id' => $p['company_id'], 'a.mobile' => $p['mobile'], 'a.status' => 0, 'c.status' => 0];
		$user = $this -> user -> getCompanyUserByWhere($where);
		if (!$user || !count($user)) {
			$this -> e('登录失败!');
		}

		$rel = $this -> get_open_id($p['code']);
		if (!is_array($rel) || (!isset($rel['openid']) && !isset($rel['session_key']))) {
			$this -> rel([]) -> e($rel['errcode'], '效验获取open_id失败！');
		}

		// $rel['session_key'] = 'aaxa';
		// $rel['openid'] = 'xxxxx';
		if ($user['mobile'] != '18800000000' && $user['open_id'] != '' && $user['open_id'] != $rel['openid']) {
			$this -> e('登录失败，当前帐号已被其他用户绑定！');
		}

		// 绑定用户OPEN_ID
		$token = md5(self::token_salt . $rel['session_key'] . $p['company_id'] . time());
		$data = ['open_id' => $rel['openid'], 'otime' => time() + self::session_otime, 'session_key' => $token];
		$where = ['company_id' => $p['company_id'], 'mobile' => $p['mobile']];
		$this -> user -> where($where) -> save($data);

		$user['openid'] = $rel['openid'];

		$this -> save_openid_token($token, $user);
		// pr($this -> get_openid_token($token));
		$this -> rel(['token' => $token]) -> e();
	}


	/**
	 * 取用户数据
	 * @Author   邱湘城
	 * @DateTime 2019-01-16T22:11:59+0800
	 */
	public function get_user_inf() {

		$this -> _get($p);
		if (is_null($this -> u)) {
			$this -> e('没有数据！');
		}

		unset($this -> u['openid'], $this -> u['open_id'], $this -> u['session_key']);
		$this -> rel($this -> u) -> e();
	}


	// 检查用户是否已登录过
	public function check() {

		$this -> _get($p, ['token']);
		$this -> e();
	}


	public function x() {

		$_SESSION["wwxw"] = 1;
		$this -> save_openid_token('aaa', ['www' => 1]);
		$this -> e('w');
	}


	public function y() {

		// $list = $this -> get_openid_token('aaa');
		$this -> rel($_SESSION) -> e();
	}
}