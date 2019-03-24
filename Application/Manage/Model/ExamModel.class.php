<?php

/**
 * @Dec    试题模块
 * @Auther QiuXiangCheng
 * @Date   2018/12/16
 */
namespace Manage\Model;
use Common\Model\BaseModel;

class ExamModel extends BaseModel {

	protected $tableName = 'exam';

	public function _initialize() {

		parent::_initialize();
	}


	/**
	 * 根据条件取试题数据
	 * @DateTime 2018-12-16T13:17:10+0800
	 */
	public function getlist($where = '', $fields = '*', $limit = '0, 10', $order = 'id desc') {

		return $this -> field($fields) -> where($where) -> select();
	}

    /**
     * 根据条件取试题数据
     * @DateTime 2019-01-10T13:17:10+0800
     */
    public function findExam($where) {

        return $this -> where($where) -> find();
    }

	/**
	 * 考生列表
	 * @DateTime 2018-12-20T00:16:17+0800
	 * @param   $where 
	 * @param   $fields
	 * @param   $order 
	 */
	public function getMlist($where, $fields = 'em.id, a.name uname, em.created_time, em.score', $order = 'id desc') {

		return $this -> field($fields) ->
		table('exam_member em') ->
		join('left join account a ON a.id = em.account_id') ->
		order($order) ->
		where($where) ->
		select();
	}
}