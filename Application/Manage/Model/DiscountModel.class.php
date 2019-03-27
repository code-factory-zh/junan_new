<?php

namespace Manage\Model;
use Common\Model\BaseModel;

class DiscountModel extends BaseModel {

    public function _initialize() {
        parent::_initialize();
    }

    protected $tableName = 'discount';

    public function getList($where = null)
    {
        return $this->where($where)->getField('id, discount_min_num, discount_max_num, discount, created_time');
    }

	public function _before_insert(&$data, $options)
	{
		$data['created_time'] = time();
		$data['updated_time'] = time();
//		$data['min_num'] = intval($data['min_num']);
//		$data['max_num'] = $data['max_num'] ? intval($data['min_num']) : null;
	}

	public function _before_update(&$data, $options)
	{
		$data['updated_time'] = time();
//		$data['min_num'] = intval($data['min_num']);
//		$data['max_num'] = $data['max_num'] ? intval($data['min_num']) : null;
	}

	public function getCourseAmount($where = []) {

		return $this -> where($where) -> getField('amount');
	}

	/**
	 * 获取没有考过试的课程信息
	 */
	public function getNotExamCourse(){
		$sql = 'select * from course where type = 0  and id not in (select course_id from exam where is_deleted=0)';

		return $this->query($sql);
	}
}