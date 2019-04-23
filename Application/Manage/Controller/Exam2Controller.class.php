<?php

/**
 * 试题模块模块
 * @Auther QiuXiangCheng
 * @Date 2018/12/16
 */
namespace Manage\Controller;
use Common\Controller\BaseController;

class Exam2Controller extends CommonController {

	private $exam;
	private $curri;
	private $exam_member;

	private $member;
	private $question;
	private $course_model;
	private $detail;
	private $examQuestion;

	public function _initialize() {

		parent::_initialize();
		$this -> islogin();

		$this -> ignore_token(0);
		$this -> exam = new \Manage\Model\ExamModel;
		$this -> course = new \Manage\Model\CourseModel;
		$this -> curri = new \Manage\Model\CurriculumModel;
		$this -> questions = new \Manage\Model\QuestionsModel;
		$this -> exam_member = new \Manage\Model\ExamMemberModel();

		$this -> member = new \Wechat\Model\ExamMemberModel;
		$this -> course_model = new \Manage\Model\CourseModel;
		$this -> question = new \Manage\Model\QuestionsModel;
		$this -> detail = new \Wechat\Model\ExamDetailModel;
		$this -> examQuestion = new \Wechat\Model\ExamQuestionModel;
	}

	/**
	 * 考试成绩单
	 * @author cuiruijun
	 * @date   2019/1/20 下午6:18
	 * @method get
	 * @return  array
	 */
	public function score_report(){
		$this->_get($g, 'exam_question_id');
		//获取当前题目详情
		$exam_question_detail = $this->examQuestion->getExamQuestionDetail($g['exam_question_id']);
		$accout_id = $exam_question_detail['account_id'];

		//获取每道题的详细信息和是否答对了题目
		$exam_question = $this->question->get_question_detail($exam_question_detail['question_ids'], $accout_id, $g['exam_question_id']);

		//获取每道题分数分配
		$exam_detail = $this->exam->getOne(['id' => $exam_question_detail['exam_id']]);

		//获取每道题的参与人数和答对的题目数
		//答对的题目数
		$count = $this->detail->get_exam_count(['exam_question_id' => $g['exam_question_id'], 'status' => 1]);

		//参与人次和排名
		$join_detail = $this->member->get_join_result($exam_question_detail['course_id'], $exam_question_detail['company_id']);

		//参与总人数
		$join_count_detail = $this->member->get_join_count($exam_question_detail['course_id']);
		$join_total = count($join_count_detail);

		//排名
		$score_array = array_column($join_detail, 'score');

		$rank = array_search($exam_question_detail['score'], $score_array) + 1;


		//不同类型的题目分数设置
		$question_type_score = [
			1 => $exam_detail['dx_question_score'],
			2 => $exam_detail['fx_question_score'],
			3 => $exam_detail['pd_question_score'],
		];

		$answer_explain_result = [];
		foreach($exam_question as $e_k => $e_v)
		{
			$options = json_decode($e_v['option'], true);
			$per_score = $question_type_score[$e_v['type']];

			if($e_v['type'] == 2){
				$answer = implode('', json_decode($e_v['answer'], true));
				$my_answer = implode('', json_decode($e_v['answer_id'], true));
			}else{
				$answer = $e_v['answer'];
				$my_answer = $e_v['answer'];
			}

			$answer_explain_result[] = [
				'title' => $e_v['title'],
				'status' => $e_v['status'],
				'my_score' => $e_v['score'],
				'score' => $per_score,
				'option' => $options,
				'answer' => $answer,
				'my_answer' => $my_answer,
				'type' => $e_v['type']
			];
		}

		$data['question_detail'] = $answer_explain_result;
		$data['exam_detail'] = [
			'user_name' => $exam_question_detail['name'],
			'couse_name' => $exam_question_detail['couse_name'],
			'id_card' => $exam_question_detail['card_num'],
			'mobile' => $exam_question_detail['mobile'],
		];
		$data['score_detail'] = [
			'my_score' => $exam_question_detail['score'],
			'total_questions' => count(explode(',', $exam_question_detail['question_ids'])),
			'total_score' => $exam_detail['score'],
			'correct_question' => $count,
			'join_users' => $join_total,
			'my_rank' => $rank,
		];

		$this->rel($data)->e();
	}
}