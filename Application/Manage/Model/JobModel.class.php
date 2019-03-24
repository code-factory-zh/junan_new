<?php

/**
 * @Dec    Manage模块主模型
 * @Auther QiuXiangCheng
 * @Date   2018/12/08
 */
namespace Manage\Model;
use Common\Model\BaseModel;

class JobModel extends BaseModel {

	protected $tableName = 'job';
	protected $_validate = array(
		array('name','','岗位已经存在！不要重复创建',0,'unique',1), // 在新增的时候验证name字段是否唯一
	);

	public function _initialize() {

		parent::_initialize();
	}

	/**
	 * 取得所有岗位
	 * @DateTime 2018-12-08T17:27:08+0800
	 */
	public function getJobs($fields, $where = []) {

		$where['is_deleted'] = 0;
		return $this -> where($where) -> getField($fields);
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