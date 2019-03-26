<?php

/**
 * @Dec    Manage模块主模型
 * @Auther QiuXiangCheng
 * @Date   2018/12/08
 */
namespace Manage\Model;
use Common\Model\BaseModel;

class SysModel extends BaseModel {

	protected $tableName = 'sys_cfg';

	public function _initialize() {

		parent::_initialize();
	}

	public function _before_insert(&$data, $options)
	{
		$data['created_time'] = time();
		$data['updated_time'] = time();
	}

	public function _before_update(&$data, $options)
	{
		$data['updated_time'] = time();
	}
}