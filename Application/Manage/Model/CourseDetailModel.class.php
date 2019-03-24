<?php

namespace Manage\Model;
use Common\Model\BaseModel;

class CourseDetailModel extends BaseModel {

    public function _initialize() {
        parent::_initialize();
    }

    protected $tableName = 'course_detail';

    /**
     * 根据课程ID获取章节列表
     * @DateTime 2018-12-11
     */
    public function getChapter($where, $field = '*')
    {
        return $this -> where($where) -> order('sort asc') -> getField($field);
    }

    /**
     * 章节详情
     * @DateTime 2018-12-11
     */
    public function getDetail($where)
    {
        return $this -> where($where) -> find();
    }

    /**
     * 章节更新
     * @DateTime 2018-12-11
     */
    public function updateData($where, $data)
    {
        return $this -> where($where) -> save($data);
    }
}