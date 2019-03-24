<?php

/**
 * 小程序考试
 * @Auther cuiruijun
 * @Date 2019/01/08
 */
namespace Wechat\Controller;

class ExamController extends CommonController
{
    private $course;
    private $exam;
    private $member;
    private $question;
    private $course_model;
    private $detail;
    private $examQuestion;
    private $account_id = 1;
    private $company = 1;

    public function _initialize()
    {
        parent::_initialize();
//        $this -> ignore_token(0);

        $this -> course = new \Manage\Model\CourseModel;
        $this -> exam = new \Manage\Model\ExamModel;
        $this -> member = new \Wechat\Model\ExamMemberModel;
        $this -> course_model = new \Manage\Model\CourseModel;
        $this -> question = new \Manage\Model\QuestionsModel;
        $this -> detail = new \Wechat\Model\ExamDetailModel;
        $this -> examQuestion = new \Wechat\Model\ExamQuestionModel;
		$this -> host = 'https://study.joinersafe.com/';

        $this -> account_id = 1;
    }

    /**
     * 前台题目获取
     *
     * @param int $id 课程ID
     * return array
     * */
    public function questions()
    {
        $this->_get($g, 'course_id');
//		$g = I('get.');

//        $this->isInt(['course_id']);
		$account_id = $this->u['id'];
//		$this->e('account_id'.$account_id);
//		$this->e($account_id);
//		$account_id = 1;

        //是否已学习完成
		//这门课程是否已经被删除
		$course_info = $this->course->getOne('iid='.$g['course_id'] . ' and is_deleted=0');
		if(!$course_info){
			$this->e('课程不存在或者已经被删除');
		}

		//是否已经考过试
        $done = $this->member->findData(['account_id' => $account_id, 'course_id' => $g['course_id'], 'is_pass_exam' => 1]);

        if (!empty($done['score'])) {
            $this->e('该考试您已通过');
        }

        //判断exam_questions表是否有记录
        $exist = $this->examQuestion->findExamQuestion(['course_id' => $g['course_id'], 'account_id' => $account_id, 'status' => 1]);

		//查询通用课程id
		$common_course = $this->course->getOne('type=1 and is_deleted=0');
		if($common_course){
			$common_course_id = $common_course['id'];
		}

		//将考试信息抽出来
		$exam_info = $this->exam->getOne('course_id = '. $g['course_id']);
		$radioNum = $exam_info['dx_question_amount'];
		$checkboxNum = $exam_info['fx_question_amount'];
		$judgeNum = $exam_info['pd_question_amount'];

		//如果没过期,而且考试不及格,则重新出题
		$not_pass_exam = $this->member->findData(['account_id' => $account_id, 'course_id' => $g['course_id'], 'is_pass_exam' => 0]);

        if ($exist) {
        	//查询是否已经过期
			$expired_time = $exist['created_time'] + ($exist['exam_time'] * 60);

			if($not_pass_exam && ($not_pass_exam['exam_question_id'] == $exist['id'])){
				//重新出题
				//重新生成题库
				$questionIds = $this->question->getIds($radioNum, $checkboxNum, $judgeNum, $g['course_id'], $common_course_id);

				if(!$questionIds){
					$this->e('考试还未开始');
				}

				$data = [
					'exam_id' => $exam_info['id'],
					'account_id' => $account_id,
					'exam_time' => $exam_info['time'],
					'status' => 1,
					'course_id' => $g['course_id'],
					'question_ids' => implode(',', $questionIds),
				];
				if (!$this->examQuestion->add($data))
				{
					$this->e('重新生成题库失败');
				}
			}elseif((time() - $expired_time > 0)){
				//没过期,则取exam_question中的记录,下面统一处理

				//如果这时候没有答题,则重新生成一套
//				$exist_exam_question_id = $not_pass_exam ? $not_pass_exam['exam_question_id'] : $exist['id'];
				$is_answerd_info = $this->detail->getRecord('id', ['exam_question_id' => $exist['id']]);

				if(!$is_answerd_info){
					//重新生成题库
					$questionIds = $this->question->getIds($radioNum, $checkboxNum, $judgeNum, $g['course_id'], $common_course_id);

					if(!$questionIds){
						$this->e('考试还未开始');
					}

					$data = [
						'exam_id' => $exam_info['id'],
						'account_id' => $account_id,
						'exam_time' => $exam_info['time'],
						'status' => 1,
						'course_id' => $g['course_id'],
						'question_ids' => implode(',', $questionIds),
					];
					if (!$this->examQuestion->add($data))
					{
						$this->e('重新生成题库失败');
					}

				}else{
					//则将分数算出来
					$score = $this->detail->getSumScore(['account_id' => $account_id, 'exam_question_id' => $exist['id']]);

					$data = [
						'score' => (int)$score
					];

					//是否通过考试字段
					$is_pass_exam_info = $this->exam->getOne(['id' => $exist['exam_id']]);

					//加入考试总共做题时间
					if(time() - $exist['created_time'] < $exist['exam_time'] * 60){
						$use_time = (time() - $exist['created_time']);
					}else{
						$use_time = $exist['exam_time'] * 60;
					}

					//这时候要插入分数表
					$exam_score_data = [
						'account_id' => $account_id,
						'exam_question_id' => $exist['id'],
						'company_id' => $this->u['company_id'],
						'course_id' => $g['course_id'],
						'score' => (int)$score,
						'is_pass_exam' => ($score >= $is_pass_exam_info['pass_score']) ? 1 : 0,
						'use_time' => $use_time
					];

					$result = $this->member->add($exam_score_data);
					if($result){
						$this->rel($data)->e();
					}else{
						$this->e('系统错误');
					}
				}
			}
        } else {
            //计算需要得出的考试类型题目数量
			//查询课程对应的exam信
            $questionIds = $this->question->getIds($radioNum, $checkboxNum, $judgeNum, $g['course_id'], $common_course_id);

			if(!$questionIds){
				$this->e('考试还未开始');
			}

			$data = [
				'exam_id' => $exam_info['id'],
				'account_id' => $account_id,
				'exam_time' => $exam_info['time'],
				'status' => 1,
				'course_id' => $g['course_id'],
				'question_ids' => implode(',', $questionIds),
			];
            if (! $this->examQuestion->add($data)) {

				$this->e('生成题库失败');
            }
        }

        //取最新一条考试题目信息
		$last_exam_questions = $this->examQuestion->findExamQuestion(['course_id' => $g['course_id'], 'account_id' => $account_id, 'status' => 1]);
		$question_ids = explode(',', $last_exam_questions['question_ids']);
		//返回第一题的信息
		$first_question = $this->question->getOne(['id' => $question_ids[0]]);
		unset($first_question['answer']);

		//返回是否做了以及做对还是做错的状态
		$is_answerd_info = $this->detail->getRecord('type,answer_id,status', ['exam_question_id' => $last_exam_questions['id'], 'question_id' => $question_ids[0], 'account_id' => $account_id]);
		$is_answerd_info = $is_answerd_info ? array_values($is_answerd_info) : null;

		//增加用户选择的答案
		if($is_answerd_info){
			if($is_answerd_info[0]['type'] == 2){
				$answer = json_decode($is_answerd_info[0]['answer_id'], true);
//				var_dump($answer);
				$answer = implode(',', $answer);
			}else{
				$answer = $is_answerd_info[0]['answer_id'];
			}
		}

		$return_res = [
			'count' => count($question_ids),
			'first_question_info' => $first_question,
			'is_answer' => $is_answerd_info ? 1 : 0,
			'answer_result' => (int)$is_answerd_info[0]['status'],
			'exam_question_id' => (int)$last_exam_questions['id'],
			'answer' => $answer,
			'question_id' => 1,
			'exam_time' => (int)$last_exam_questions['exam_time'],
			'exam_create_time' => $last_exam_questions['created_time'],
		];

		$this->rel($return_res)->e();
    }

    /**
     * 前台获取单条题目信息
     *
     * @param int question_id 题目ID
     * @param int exam_question_id 试题ID
     * @param int $id 题目ID-第几题
     * return array
     * */
    public function detail()
    {
//    	$account_id = 1;
//		echo $account_id;

//        $this->_get($g, I('get.'));
//        $this->isInt(['question_id']);

//		$g = I('get.');
		$this->_get($g, 'exam_question_id', 'question_id');
		$question_sort = $g['question_id'] - 1;

		$account_id = $this->u['id'];

//		$this->e('exam_question_id = '.$g['exam_question_id'] . ' ---question_id='.$g['question_id']  .'--account_id='.$account_id);
//		exit;

		//查看当前question_id
		$examQuestion = $this -> examQuestion -> findExamQuestion(['id' => $g['exam_question_id'], 'status' => 1, 'account_id' => $account_id]);

		if(!$examQuestion){
			$this->e('考题不存在');
		}

		if($examQuestion['is_pass_exam']){
			$this->e('您已经通过了这次考试,无须再次考试');
		}

		$question_ids = explode(',' , $examQuestion['question_ids']);

		$question_id = $question_ids[$question_sort];

        $question = $this->question-> getQuestion(['id' => $question_id], 'id, type, title, option');
        if (empty($question)) {
            $this->e('题目不存在');
        }

        $question['option'] = json_decode($question['option'], true);
        //查看是否有答题记录
		$is_answerd_info = $this->detail->getRecord('type,answer_id,status', ['exam_question_id' => $g['exam_question_id'], 'question_id' => $question_id, 'account_id' => $account_id]);
		$is_answerd_info = $is_answerd_info ? array_values($is_answerd_info) : null;

		//增加用户选择的答案
		if($is_answerd_info){
			if($is_answerd_info[0]['type'] == 2){
				$answer = json_decode($is_answerd_info[0]['answer_id'], true);
				$answer = implode(',', $answer);
			}else{
				$answer = $is_answerd_info[0]['answer_id'];
			}
		}

		$question['is_answer'] = $is_answerd_info ? 1 : 0;
		$question['answer_result'] = (int)$is_answerd_info[0]['status'];
		$question['answer'] = $answer;
		$question['question_id'] = $g['question_id'];

		//是否是最后一题
		$question['is_last_question'] = count($question_ids) == $g['question_id'] ? 1 : 0;

        $this->rel($question)->e();
    }

    /**
     * 前台提交题目答案
     *
     * @pram int $exam_question_id 考试ID
     * @param int $question_id 题目ID
     * @param int|array $answer_id 答案ID,用逗号分隔开
     * return bool
     * */
    public function answer()
    {
//		$account_id = 1;
//        $this->_post($g, ['exam_question_id', 'question_id', 'answer_id']);
//        $this->isInt(['id', 'question_id']);

//		$g = I('post.');
		$this->_post($g, ['exam_question_id', 'question_id', 'answer_id']);

		$question_sort = $g['question_id'] - 1;

		$account_id = $this->u['id'];

		//查看当前question_id
		$examQuestion = $this -> examQuestion -> findExamQuestion(['id' => $g['exam_question_id'], 'status' => 1, 'account_id' => $account_id]);

		if(!$examQuestion){
			$this->e('考题不存在');
		}

		if($examQuestion['is_pass_exam']){
			$this->e('您已经通过了这次考试,无须再次考试');
		}

		$question_ids = explode(',' , $examQuestion['question_ids']);

		$question_id = $question_ids[$question_sort];

        //该考生题库是否存在
        $examQuestion = $this -> examQuestion -> findExamQuestion(['id' => $g['exam_question_id'], 'status' => 1, 'account_id' => $account_id]);
        if (!$examQuestion) {
            $this->e('该套试题已经下架或者删除');
        }else{
        	//查看该套试题中是否有这道题
			if(!in_array($question_id, explode(',', $examQuestion['question_ids']))){
				$this->e('未找到该题目');
			}else{
				$question = $this->question->getQuestion(['id' => $question_id]);
				if (empty($question)) {
					$this -> e('题目不存在哦');
				}
			}
		}

        $exam = $this -> exam -> findExam(['id' => $examQuestion['exam_id'], 'is_deleted' => 0]);
        if (empty($exam)) {
            $this -> e('考试不存在');
        }

        //是否已经过期
		$expired_time = $examQuestion['created_time'] + ($examQuestion['exam_time'] * 60);
		if(time() - $expired_time > 0){
			$this -> e('考试已经结束');
		}

//        //是否已学习完成
//        $done = $this -> member -> findData(['account_id' => $this -> account_id, 'exam_id' => $examQuestion['exam_id'], 'is_deleted' => 0]);
//
//        if (!empty($done['score'])) {
//            $this -> e('该考试您已填写');
//        }

        //是否已答过
		$is_answerd_info = $this->detail->getRecord('id', ['exam_question_id' => $g['exam_question_id'], 'question_id' => $question_id, 'account_id' => $account_id]);
//        if ($is_answerd_info) {
//            $this -> e('已经回答过这道题了');
//        }

        //开始写入数据
        $data['account_id'] = $account_id;
        $data['exam_question_id'] = $g['exam_question_id'];
        $data['question_id'] = $question_id;
        $data['type'] = $question['type'];

		if($question['type'] == 2)
		{
			$answer_id = explode(',' , $g['answer_id']);
			$answer_id = json_encode($answer_id);
		}
		else
		{
			$answer_id = $g['answer_id'];
		}

        $data['answer_id'] = $answer_id;
        $data['status'] = ($answer_id == $question['answer']) ? 1 : 0;

        if ($data['status']) {
            $data['score'] = $exam['dx_question_score'];
            if ($question['type'] == 2) $data['score'] = $exam['fx_question_score'];
            if ($question['type'] == 3) $data['score'] = $exam['pd_question_score'];
        }else{
			$data['score'] = 0;
		}

		if($is_answerd_info){
			//如果已经存在,就修改
			$data['id'] = $is_answerd_info;
			$result = $this->detail->save($data);
		}else{
			$result = $this -> detail -> add($data);
		}

//        //判断时间是否在范围内或者是最后一题,如果是，则返回分数
//        $time = $examQuestion['start_time'] + $examQuestion['exam_time'] >= time();
//        $num = $this -> detail -> getFieldByCondition(['account_id' => $this -> account_id, 'exam_questions_id' => $g['id'], 'status' => 1], 'count(1) as num');
//
//        $end = count($questions) == (!empty($num['num'])) ? $num['num'] + 1 : 0;
//
//        if (empty($num['num'])) {
//            $this -> examQuestion -> save(['id' => $g['id'], 'start_time' => time()]);
//
//            $result = $this -> detail -> add($data);
//        } elseif ((!$examQuestion['exam_time'] && $time) || $end) {
//            if ($end) {
//                $this -> detail -> add($data);
//            }
//
//            $score['exam_id'] = $examQuestion['exam_id'];
//            $score['course_id'] = $exam['course_id'];
//            $score['company_id'] = $this -> company;
//            $score['account_id'] = $this -> account_id;
//
//            $total = $this -> detail -> getFieldByCondition(['account_id' => $this -> account_id, 'exam_questions_id' => $g['id'], 'status' => 1], 'sum(score) as total');
//            $score['score'] = (!empty($total['total'])) ? $total['total'] : 0;
//            $result = $this -> member -> add($score);
//            if ($result) {
//                $this -> e(0, $total);
//            }
//        } else {
//            $result = $this -> detail -> add($data);
//        }

        if ($result) {
        	//返回答题的状态
			$data = [
				'answer_result' => $data['status'],
				'answer' => $g['answer_id'],
				'question_id' => (int)$g['question_id'],
			];

			//是否是最后一题
			$data['is_last_question'] = count($question_ids) == $g['question_id'] ? 1 : 0;

            $this->rel($data)->e();
        } else {
            $this->e('系统异常');
        }
    }

	/**
	 * 获取在考试题的每题的状态
	 * @author cuiruijun
	 * @date   2019/1/18 下午4:31
	 * @method get
	 *
	 * @param  int exam_question_id
	 * @return  array
	 */
    public function get_exam_question(){
//    	$account_id = 1;
//		$g = I('get.');
		$this->_get($g, 'exam_question_id');

		$account_id = $this->u['id'];

		$exam_question_info = $this->examQuestion->findExamQuestion(['id' => $g['exam_question_id'], 'account_id' => $account_id, 'status' => 1]);

		if(!$exam_question_info){
			$this->e('试题不存在');
		}

		if($exam_question_info['is_pass_exam']){
			$this->e('您已经通过了这次考试,无须再次考试');
		}

		//查询做题的状态
		$exam_detail_list = $this->detail->get_exam_detail(['exam_question_id' => $g['exam_question_id']]);

		$quesiton_ids = explode(',', $exam_question_info['question_ids']);

		$exam_question_ids = array_column($exam_detail_list, 'question_id');

		$result = [];
		foreach($quesiton_ids as $k => $v){
			$search_key = array_search($v, $exam_question_ids);
			if($search_key === false || $search_key === null){
				//没答题
				$tmp['question_id'] = $k + 1;
				$tmp['is_answer'] = 0;
				$tmp['answer_result'] = 0;
			}else{
				//答题了
				$tmp['question_id'] = $k + 1;
				$tmp['is_answer'] = 1;
				$tmp['answer_result'] = (int)$exam_detail_list[$search_key]['status'];
			}

			$result[$k] = $tmp;
		}

		//当前总共得了多少分
		$score = $this->detail->getSumScore(['account_id' => $account_id, 'exam_question_id' => $g['exam_question_id']]);

		$data = [
			'question_info' => $result,
			'score' => (int)$score,
		];

		$this->rel($data)->e();
	}

	/**
	 * 交卷
	 * @author cuiruijun
	 * @date   2019/1/18 下午4:35
	 * @method get
	 *
	 * @param  int exam_question_id
	 * @return  array
	 */
	public function finish_exam(){
//		$account_id = 1;
//		$g = I('post.');
		$this->_post($g, ['exam_question_id']);

		$account_id = $this->u['id'];

		//插入memeber表
		$score = $this->detail->getSumScore(['account_id' => $account_id, 'exam_question_id' => $g['exam_question_id']]);

		$data = [
			'score' => (int)$score
		];

		$exam_question_info = $this->examQuestion->findExamQuestion(['id' => $g['exam_question_id'], 'account_id' => $account_id, 'status' => 1]);

		if(!$exam_question_info){
			$this->e('考试不存在');
		}

		if($exam_question_info['is_pass_exam']){
			$this->e('您已经通过了这次考试,无须再次考试');
		}

		//是否通过考试字段
		$is_pass_exam_info = $this->exam->getOne(['id' => $exam_question_info['exam_id']]);

		//加入考试总共做题时间
		if(time() - $exam_question_info['created_time'] < $exam_question_info['exam_time'] * 60){
			$use_time = time() - $exam_question_info['created_time'];
		}else{
			$use_time = $exam_question_info['exam_time'] * 60;
		}

		//这时候要插入分数表
		$exam_score_data = [
			'account_id' => $account_id,
			'exam_question_id' => $exam_question_info['id'],
			'company_id' => $this->u['company_id'],
//			'company_id' => 1,
			'course_id' => $exam_question_info['course_id'],
			'score' => (int)$score,
			'is_pass_exam' => ($score >= $is_pass_exam_info['pass_score']) ? 1 : 0,
			'use_time' => $use_time,
		];

		$result = $this->member->add($exam_score_data);
		if($result){
			$this->rel($data)->e();
		}else{
			$this->e('交卷失败');
		}
	}

	/**
	 * 考试成绩列表
	 * @author cuiruijun
	 * @date   2019/1/20 下午6:18
	 * @method get
	 * @return  array
	 */
	public function score_list(){
		$this->_get();
		$accout_id = $this->u['id'];
//		$accout_id = 1;

		$list = $this->member->getUserScoreList($accout_id);

		$count = $this->member->getAnswerResultCount($accout_id);
		foreach($count as $k => $v){
			if($v['status'] == 1){
				$true_count = $v['count'];
			}

			if($v['status'] == 0){
				$false_count = $v['count'];
			}
		}

		$data['true_count'] = (int)$true_count;
		$data['false_count'] = (int)$false_count;

		$data['list'] = $list;

		$this->rel($data)->e();
	}
}