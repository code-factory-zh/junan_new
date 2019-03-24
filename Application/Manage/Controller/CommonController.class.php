<?php

/**
 * Manage模块基类
 * @Auther QiuXiangCheng
 * @Date 2017/12/12
 */
namespace Manage\Controller;
use Common\Controller\BaseController;
class CommonController extends BaseController {

	// 针对HTTP接口的固定TOKEN
	CONST HTTP_TOKEN_N1 = '8FA02B017FCDE7836A6FDB5D00AC638F';

	protected $base_url = 'http://192.168.1.220';

	// 生成一个被组合好的JSON数据
	protected function postFetch(&$data) {

		$data['token'] = self::HTTP_TOKEN_N1;
	}

	public function _initialize() {

		parent::_initialize();
		$this -> assign('uf', $this -> userinfo);
		$this -> assign('domain', $this -> select_domain());
	}

	// 生成字母随机数
	// @param MD5之后的密码
	public function fetchRandPwd(&$pwdMD5, $len = 6) {

		$str = '';
		$mod = 'abcdefghijklmnopqrstuvwxyz0123456789';
		for ($i = 0; $i < $len; $i ++) {
			$str .= $mod[mt_rand(0, 35)];
		}
		$pwdMD5 = $this -> _encrypt($str);
		return $str;
	}
}