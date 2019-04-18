<?php

/**
 * 试题模块模块
 * @Auther QiuXiangCheng
 * @Date 2018/12/16
 */
namespace Manage\Controller;
use Common\Controller\BaseController;

class ExamController extends CommonController {

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
	 * 试题列表
	 * @DateTime 2018-12-16T13:02:07+0800
	 */
	public function list() {

		$data = [];
		$list = $this -> exam -> getlist(['is_deleted' => 0]);

		if (count($list)) {
			$course = $this -> curri -> getCurList(['is_deleted' => 0], 'id, name');
			foreach ($list as &$items) {
				$items['course_name'] = '-';
				$items['total_exam_amount'] = $items['pd_question_amount'] + $items['dx_question_amount'] + $items['fx_question_amount'];
				isset($course[$items['course_id']]) && $items['course_name'] = $course[$items['course_id']];
			}
		}
		$data['list'] = $list;
		// pr($list);

		$this -> assign($data);
		$this -> display('Exam/list');
	}


	/**
	 * 新增、修改
	 * @DateTime 2018-12-16T13:40:08+0800
	 */
	public function edit() {

		if (IS_POST) {
			$needle = ['name', 'time', 'pass_score', 'dx_question_amount', 'fx_question_amount', 'pd_question_amount', 'dx_question_score', 'fx_question_score', 'course_id',  'pd_question_score'];
			$this -> _post($p, $needle);
			$this -> isInt(['dx_question_amount', 'fx_question_amount', 'pd_question_amount', 'dx_question_score', 'fx_question_score', 'course_id', 'pd_question_score']);

			$score = $p['dx_question_amount'] * $p['dx_question_score'] + $p['fx_question_amount'] * $p['fx_question_score'] + $p['pd_question_amount'] * $p['pd_question_score'];

			//没填参数的提示
			if(!$p['name']){
				$this->e('试题名称不能为空');
			}

			if(!$p['time']){
				$this->e('考试时长不能为空');
			}

			if(!$p['pass_score']){
				$this->e('及格分数必须大于0');
			}

			if(!$p['course_id']){
				$this->e('必须选择一门课程');
			}

			if($score != 100)
			{
				$this->e('总分固定100分');
			}

			//题型总数不能超出已有题目总数,否则报错
			$type_count_info = $this->questions->getQuestionByType();
			foreach($type_count_info as $t_k => $t_v){
				if($t_v['type'] == 1){
					//单选
					if($p['dx_question_amount'] > $t_v['count']){
						$this->e('出题单选题总数超出了本课程已有单选题总数');
					}
				}elseif($t_v['type'] == 2){
					if($p['fx_question_amount'] > $t_v['count']){
						$this->e('出题复选题总数超出了本课程已有复选题总数');
					}
				}elseif($t_v['type'] == 3){
					if($p['pd_question_amount'] > $t_v['count']){
						$this->e('出题判断题总数超出了本课程已有判断题总数');
					}
				}
			}

			$p['score'] = $score;

			$p['created_time'] = $p['updated_time'] = time();
			if (isset($p['id']) && $p['id'] != '') {
				$id = $p['id'];
				unset($p['id']);
				$done = $this -> exam -> where(['id' => $id]) -> save($p);
			} else {
				$done = $this -> exam -> table('exam') -> add($p);
			}

			if (!$done) {
				$this -> e('失败');
			}

			$this -> e();
		}

		$this -> _get($p);
//		$p = I('get.');
		$data = $this -> exam -> where(['id' => $p['id']]) -> find();

		//取所有的课程
		$data['course'] = $this->course->getNotExamCourse();

		if($p['id']){
			//增加一个当前已经取到的
			$data['course'][] = $this->course->getOne(['id' => $data['course_id']]);
		}

		$this -> assign($data);
		$this -> display('Exam/edit');
	}


	/**
	 * 考生列表
	 * @DateTime 2018-12-16T13:40:27+0800
	 */
	public function mlist() {

		$this -> _get($g, 'course_id');
		$this -> isInt(['course_id']);

		$data = ['list' => []];
		$where = ['em.is_deleted' => 0, 'course_id' => $g['course_id']];
		$data['list'] = $this -> exam -> getMlist($where);

		if (count($data['list'])) {
			foreach ($data['list'] as &$items) {
				$items['created_time'] = date('Y-m-d H:i:s', $items['created_time']);
			}
		}

		$this -> assign($data);
		$this -> display('Exam/mlist');
	}

	/**
	 * 删除
	 * @author cuiruijun
	 * @date   2019/1/27 上午11:59
	 * @url    exam/del
	 * @method post
	 *
	 * @param  int id
	 * @return  array
	 */
	public function del(){
		if (!empty(I('post.id'))){
			//判断是否有相应课程,如果有,就不能删除
			$exam_info = $this->exam_member->isDelExam(I('post.id'));
			if($exam_info){
				foreach($exam_info as $k => $v){
					if($v['account_id'] && !$v['is_pass_exam']){
						$this->e('该试题还有未完成考试或者考试失败的用户,不能删除');
					}
				}
			}

			$data = [
				'id' => I('post.id'),
				'is_deleted' => 1,
			];
			$result = $this->exam->save($data);
			if($result){
				$this->e();
			}else{
				$this->e('删除失败');
			}
		}
	}



	/**
	 * 2019/03/27
	 * 考生成绩管理
	 */
	public function ach() {

		$where = ['em.company_id' => $this -> userinfo['id'], 'em.is_deleted' => 0];
		$list = $this -> exam_member -> getExamAchList($where, 'a.name, c.name course_name, em.*');

		if (count($list)) {
			foreach ($list as &$items) {
				$items['created_time'] = date('Y-m-d H:i:s', $items['created_time']);
				$items['is_pass_exam'] = $items['is_pass_exam'] == 1 ? '及格' : '未及格';
				$items['use_time'] = intval($items['use_time'] / 60) . ':' . ($items['use_time'] % 60);
			}
		}

		$this -> assign('list', $list);
		$this -> display('Exam/achlist');
	}

	/**
	 * 2019/03/27
	 * 报告页面
	 */
	public function report1() {
//		$this -> assign('list', $list);
		$this -> display('Exam/report');
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