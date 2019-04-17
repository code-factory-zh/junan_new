<?php

namespace Manage\Model;
use Common\Model\BaseModel;

class CourseModel extends BaseModel {

    public function _initialize() {
        parent::_initialize();
    }

    protected $tableName = 'course';

    public function getList($where = null)
    {
		$where['is_deleted'] = 0;

        return $this->where($where)->getField('id, name, type, detail, created_time');
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

	public function _after_insert($data, $options){
		//插入成功后,写入表中对应的exam模板库信息
		$exam_model = new \Manage\Model\ExamModel;
		$xam_data = [
			'name' => $data['name'],
			'detail' => $data['detail'],
			'course_id' => $data['id'],
			'time' => 60,
			'score' => 100,
			'pass_score' => 60,
			'pd_question_score' => 15,
			'pd_question_amount' => 2,
			'dx_question_score' => 15,
			'dx_question_amount' => 2,
			'fx_question_score' => 10,
			'fx_question_amount' => 4,
			'created_time' => time(),
			'updated_time' => time(),
		];
		$exam_model->add($xam_data);
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