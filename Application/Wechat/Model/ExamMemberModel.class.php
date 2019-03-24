<?php

namespace Wechat\Model;
use Common\Model\BaseModel;

class ExamMemberModel extends BaseModel
{

    public function _initialize()
    {
        parent::_initialize();
    }

    protected $tableName = 'exam_member';


    public function _before_insert(&$data, $options) {
        $data['created_time'] = time();
        $data['updated_time'] = time();
        $data['is_deleted'] = 0;
    }

    public function _after_insert($data, $options)
	{
		//插入表以后更新章节表的状态status=1 和 company_account_course 表的is_pass_exam 字段
		if($data['is_pass_exam'] == 1){
			//只有考过了才更新company_account_course表
			$company_account_course_model = new \Wechat\Model\CompanyAccountModel;
			$company_account_course_model->where(['account_id' => $data['account_id'], 'course_id' => $data['course_id']])->save(['is_pass_exam' => 1]);
		}

		//更新章节状态status=1
		$account_course_model = new \Wechat\Model\AccountcourseModel;
		$account_course_model->where(['account_id' => $data['account_id'], 'course_id' => $data['course_id']])->save(['status' => 1]);

	}


	/**
     * 根据条件取考试数据
     * @DateTime 2019-01-10T13:17:10+0800
     */
    public function findData($where) {

        return $this -> where($where)->order('created_time desc') -> find();
    }

	/**
	 * 查询成绩列表
	 * @DateTime 2019-01-20T12:47:10+0800
	 */
    public function getUserScoreList($account_id){

    	//连表查询
		return $this-> alias('m')->field('m.score, m.use_time, m.created_time,c.name')->where(['account_id' => $account_id])->join('course c on m.course_id=c.id', 'left')->select();
	}

	/**
	 * 答对题答错题总数
	 */
	public function getAnswerResultCount($account_id, $where = null){
		$sql = 'select status, count(id) as count from exam_detail where exam_question_id in (select exam_question_id from exam_member where is_pass_exam = 1 and account_id=' . $account_id . ') group by status';

		return $this->query($sql);
	}
}