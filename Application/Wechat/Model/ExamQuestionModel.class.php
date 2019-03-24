<?php

namespace Wechat\Model;
use Common\Model\BaseModel;

class ExamQuestionModel extends BaseModel
{

    public function _initialize()
    {
        parent::_initialize();
    }

    protected $tableName = 'exam_questions';

    public function _before_insert(&$data, $options)
    {
        $data['created_time'] = time();
        $data['status'] = 1;
    }

    public function findExamQuestion($where) {

        return $this -> where($where) -> order('created_time desc') -> find();
    }

}
