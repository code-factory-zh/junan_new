<?php

/**
 * @Dec    公众号课程相关
 * @Auther QiuXiangCheng
 * @Date   2019/03/31
 */

namespace Buy\Model;
use Common\Model\BaseModel;

class CourseModel extends BaseModel {

	protected $tableName = 'course';

	public function _initialize() {

		parent::_initialize();
	}

	/**
	 * 取得当前课程列表数据
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T14:48:15+0800
	 */
	public function getCourseList($where, $type = 1) {

		$this -> where($where) -> field('id,name,detail,price');
		if ($type == 1) {
			return $this -> find();
		}

		return $this -> select();
	}
}