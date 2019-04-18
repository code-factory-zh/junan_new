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

	/**
	 * 获取当前试卷详细信息
	 * @author cuirj
	 * @date   2019/4/15 下午9:58
	 *
	 * @param  int $exam_question_id
	 * @return  array
	 */
    public function getExamQuestionDetail($exam_question_id){
    	return $this->alias('e')
			->field('a.name, c.name as couse_name,m.score,e.question_ids as question_ids,e.exam_id, a.company_id,e.course_id, e.account_id, a.card_num,a.mobile')
			->where(['e.id' => $exam_question_id])
			->join('course as c on e.course_id = c.id', 'left')
			->join('account as a on e.account_id = a.id', 'left')
			->join('exam_member as m on e.id = m.exam_question_id')
			->find();
	}
}
