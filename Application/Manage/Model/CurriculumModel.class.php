<?php

/**
 * @Dec    课程基础模块
 * @Auther QiuXiangCheng
 * @Date   2018/12/11
 */
namespace Manage\Model;
use Common\Model\BaseModel;

class CurriculumModel extends BaseModel {


	/**
	 * 取课程键值对
	 * @DateTime 2018-12-11T23:59:30+0800
	 */
	public function getCurList($where, $fields) {

		return $this -> table('course') -> where($where) -> getField($fields);
	}


	/**
	 * 根据条件取数据
	 * @DateTime 2018-12-12T00:19:33+0800
	 */
	public function getCurByWhere($where, $fields = '*', $group = '') {

		return $this -> field($fields) ->
		table('company_account_course') ->
		where($where) -> group($group) -> select();
	}


	/**
	 * 根据条件取出所有课程
	 * @DateTime 2018-12-12T00:32:51+0800
	 */
	public function getCourseListByWhere($where, $fields = '*') {

		$course = "select course_id, count(*) amount from company_account_course where {$where} group by course_id";

		return $this -> field($fields) ->
		table('course c') ->
		join("left join ({$course}) cac on cac.course_id = c.id") ->
		where(['c.is_deleted = 0']) -> select();
	} 
}