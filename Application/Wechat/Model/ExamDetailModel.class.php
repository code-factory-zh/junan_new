<?php

namespace Wechat\Model;
use Common\Model\BaseModel;

class ExamDetailModel extends BaseModel
{

    public function _initialize()
    {
        parent::_initialize();
    }

    protected $tableName = 'exam_detail';

    public function _before_insert(&$data, $options)
    {
        $data['created_time'] = time();
        $data['updated_time'] = time();
    }

    public function _before_update(&$data, $options)
    {
        $data['updated_time'] = time();
    }

    /**
     * 根据条件获取记录
     *
     * @param string $fields
     * @param array|string $where
     * return array
     */
    public function getRecord($fields, $where) {
        return $this -> where($where) -> getField($fields);
    }

    /*
     * 根据条件获取总数
     * @param array $where
     * @param string $field
     *
     * **/
    public function getFieldByCondition($where, $field)
    {
        return $this -> field($field) -> where($where) -> find();
    }



    /**
     * 根据条件取试题数据
     * @DateTime 2019-01-10T13:17:10+0800
     */
    public function findDetail($where) {

        return $this -> where($where) -> find();
    }

    /**
     *  获取成绩
     */
	public function getSumScore($where) {

		return $this -> where($where) -> sum('score');
	}

	/**
	 * 获取所有提交的答案
	 */
	public function get_exam_detail($where){
		return $this->where($where)->select();
	}
}