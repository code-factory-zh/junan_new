<?php

namespace Manage\Model;
use Common\Model\BaseModel;

class ExamMemberModel extends BaseModel
{

    public function _initialize()
    {
        parent::_initialize();
    }

    protected $tableName = 'exam_member';


    public function _before_insert(&$data, $options)
    {
        $data['created_time'] = time();
        $data['updated_time'] = time();
        $data['is_deleted'] = 0;
    }


    /**
     * 根据条件取考试数据
     * @DateTime 2019-01-10T13:17:10+0800
     */
    public function findData($where) {

        return $this -> where($where) -> find();
    }

    /**
     * 判断是否能删除该考试
     */
    public function isDelExam($exam_id){
		$sql = 'select tmp.*,m.is_pass_exam from (select account_id,max(id) as id from exam_questions tmp where exam_id=' . $exam_id . ') tmp left join exam_member m on tmp.account_id=m.account_id and tmp.id=m.exam_question_id';

		return $this->query($sql);
	}
}