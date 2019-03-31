<?php

/**
 * @Dec    用户相关模块
 * @Auther QiuXiangCheng
 * @Date   2019/03/31
 */

namespace Buy\Model;
use Common\Model\BaseModel;

class UserModel extends BaseModel {

	protected $tableName = 'company';

	public function _initialize() {

		parent::_initialize();
	}

	/**
	 * 新增企业
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T11:36:13+0800
	 */
	public function createCompany($data) {

		$time = time();
		$data['updated_time'] = $time;
		$data['created_time'] = $time;

		return $this -> add($data);
	}

	/**
	 * 验证用户密码
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T12:58:37+0800
	 */
	public function login_very($data) {

		$user = $this -> where(['code' => $data['code']]) -> find();
		if (!is_null($user) && count($user)) {
			if (password_verify($data['pwd'], $user['password'])) {
				if (empty($user['open_id']) && !empty($data['open_id'])) {
					return $this -> where(['id' => $user['id']]) -> save(['open_id' => $data['open_id']]);
				}
				if (!empty($user['open_id']) && $user['open_id'] != $data['open_id']) {
					return false;
				}
				return true;
			}
		}
		return false;
	}
}