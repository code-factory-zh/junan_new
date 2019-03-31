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

	public function select_and_very($data) {

		$find = $this -> where(['code' => $data['code'], 'password' => md5($data['pwd'])]) -> count();
		return $find;
	}
}