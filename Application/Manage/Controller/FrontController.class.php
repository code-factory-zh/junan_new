<?php

/**
 * 子帐户模块
 * @Auther cuiruijun
 * @Date 2019/01/08
 */
namespace Manage\Controller;
use Common\Controller\BaseController;

class FrontController extends CommonController
{
    private $exam;
    private $member;
    private $question;
    private $detail;
    private $examQuestion;
    private $account_id = 1;
    private $company = 1;

    public function _initialize()
    {
        parent::_initialize();
        $this -> ignore_token(0);

        $this -> exam = new \Manage\Model\ExamModel;
        $this -> member = new \Manage\Model\ExamMemberModel;
        $this -> question = new \Manage\Model\QuestionsModel;
        $this -> detail = new \Manage\Model\ExamDetailModel;
        $this -> examQuestion = new \Manage\Model\ExamQuestionModel;

        $this -> account_id = 1;
    }

    /**
     * 前台考试列表
     * */
    public function lists()
    {
        $data = ['list' => []];
        $data['list'] = $this -> exam -> getlist(['is_deleted' => 0], 'id, name');

        $this -> e(0, $data['list']);
    }

    /**
     * 前台题目获取
     *
     * @param int $id 考试ID
     * return array
     * */
    public function questions()
    {
        $this->_get($g, 'id');
        $this->isInt(['id']);

        //是否已学习完成
        $done = $this -> member -> findData(['account_id' => $this -> account_id]);

        if (!empty($done['score'])) {
            $this->e('该考试您已填写');
        }

        $exam = $this -> exam -> findExam(['id' => $g['id'], 'is_deleted' => 0]);
        if (empty($exam)) {
            $this->e('考试不存在');
        }

        //计算需要得出的考试类型题目数量
        $radioNum = $exam['dx_question_amount'] / $exam['dx_question_score'];
        $checkboxNum = $exam['fx_question_amount'] / $exam['fx_question_score'];
        $judgeNum = $exam['pd_question_amount'] / $exam['pd_question_score'];

        $radioWhere = ['course_id' => $exam['course_id'], 'is_deleted' => 0, 'type' => 1];
        $checkboxWhere = ['course_id' => $exam['course_id'], 'is_deleted' => 0, 'type' => 2];
        $judgeWhere = ['course_id' => $exam['course_id'], 'is_deleted' => 0, 'type' => 3];

        //判断exam_questions表是否有记录
        $exist = $this -> examQuestion -> findExamQuestion(['exam_id' => $g['id'], 'account_id' => $this -> account_id, 'status' => 1]);
        if (!empty($exist)) {
            $questionIds = json_decode($exist['question_ids']);

            //查询已经打完的题目
            $return = [];
            foreach ($questionIds as $value) {
                $record = $this -> detail -> getRecord('id, type', ['account_id' => $this -> account_id, 'exam_questions_id' => $exist['id'], 'question_id' => $value]);
                if (empty($record)) {
                    $return[$value] = 0;
                } else {
                    $return[$value] = 1;
                }
            }
            $this->e(0, ['id' => $exist['id'], 'rows' => $return]);
        } else {
            $radio = $this -> question -> getAll('id', $radioWhere, 1, $radioNum);
            $checkbox = $this -> question -> getAll('id', $checkboxWhere, 1, $checkboxNum);
            $judge = $this -> question -> getAll('id', $judgeWhere, 1, $judgeNum);

            $data['exam_id'] = $g['id'];
            $data['account_id'] = $this -> account_id;
            $data['exam_time'] = $exam['time'];
            $questionIds = [];
            if (!empty($radio)) $questionIds = array_merge($questionIds, array_column($radio, 'id'));
            if (!empty($checkbox)) $questionIds = array_merge($questionIds, array_column($checkbox, 'id'));
            if (!empty($judge)) $questionIds = array_merge($questionIds, array_column($judge, 'id'));
            $data['question_ids'] = json_encode($questionIds);

            if ($result = $this ->examQuestion -> add($data)) {
                $return = [];
                foreach ($questionIds as $value) {
                    $return[$value] = 0;
                }
                $this->e(0, ['id' => $result, 'rows' => $return]);
            } else {
                $this->el($result, 'fail');
            }
        }
    }

    /**
     * 前台获取单条题目信息
     *
     * @param int $id 题目ID
     * return array
     * */
    public function detail()
    {
        $this->_get($g, 'id');
        $this->isInt(['id']);

        $question = $this -> question -> getQuestion(['id' => $g['id']], 'id, type, title, option');
        if (empty($question)) {
            $this -> e('题目不存在');
        }

        $question['option'] = json_decode($question['option'], true);
        //查看是否有答题记录
        $record = $this -> detail -> findDetail(['account_id' => $this ->account_id, 'question_id' => $g['id']]);
        if (!empty($record)) {
            $question['answer_id'] = $record['answer_id'];
            $question['status'] = $record['status'];
        }

        $this -> e($question);
    }

    /**
     * 前台提交题目答案
     *
     * @pram int $exam_id 考试ID
     * @param int $question_id 题目ID
     * @param int|array $answer_id 答案ID
     * return bool
     * */
    public function submit()
    {
        $this->_post($g, ['id', 'question_id', 'answer_id']);
        $this->isInt(['id', 'question_id']);

        //该考生题库是否存在
        $examQuestion = $this -> examQuestion -> findExamQuestion(['id' => $g['id'], 'status' => 1, 'account_id' => $this -> account_id]);
        if (empty($examQuestion)) {
            $this -> e('非法访问');
        }

        $exam = $this -> exam -> findExam(['id' => $examQuestion['exam_id'], 'is_deleted' => 0]);
        if (empty($exam)) {
            $this -> e('考试不存在');
        }

        //是否已学习完成
        $done = $this -> member -> findData(['account_id' => $this -> account_id, 'exam_id' => $examQuestion['exam_id'], 'is_deleted' => 0]);

        if (!empty($done['score'])) {
            $this -> e('该考试您已填写');
        }

        //是否已答过
        $isAnswer = $this -> detail -> getRecord('id', ['account_id' => $this -> account_id, 'question_id' => $g['question_id'], 'exam_questions_id' => $g['id']]);
        if (!empty($isAnswer)) {
            $this -> e('请勿重复提交');
        }

        //查看该题是否在课程ID下
        $questions = json_decode($examQuestion['question_ids']);
        if (!in_array($g['question_id'], $questions)) {
            $this -> e('题目不存在');
        }

        $question = $this -> question -> getQuestion(['id' => $g['question_id']]);
        if (empty($question)) {
            $this -> e('题目不存在哦');
        }

        //开始写入数据
        $data['account_id'] = $this -> account_id;
        $data['exam_questions_id'] = $g['id'];
        $data['question_id'] = $g['question_id'];
        $data['type'] = $question['type'];
        $data['answer_id'] = ($question['type'] == 2) ? json_encode($g['answer_id']) : $g['answer_id'];
        if ($question['type'] == 2) {
            $correct = implode(',', json_decode($question['answer'], true));
            $data['status'] = ($correct != $g['answer_id']) ? 0 : 1;
        } else {
            $data['status'] = ($g['answer_id'] != $question['answer']) ? 0 : 1;
        }

        if ($data['status']) {
            $data['score'] = $exam['dx_question_score'];
            if ($question['type'] == 2) $data['score'] = $exam['fx_question_score'];
            if ($question['type'] == 3) $data['score'] = $exam['pd_question_score'];
        }

        //判断时间是否在范围内或者是最后一题,如果是，则返回分数
        $time = $examQuestion['start_time'] + $examQuestion['exam_time'] >= time();
        $num = $this -> detail -> getField(['account_id' => $this -> account_id, 'exam_questions_id' => $g['id'], 'status' => 1], 'count(1) as num');

        $end = count($questions) == (!empty($num['num'])) ? $num['num'] + 1 : 0;

        if (reset($questions) == $g['question_id']) {
            $this -> examQuestion -> save(['id' => $g['id'], 'start_time' => time()]);

            $result = $this -> detail -> add($data);
        } elseif ((!$examQuestion['exam_time'] && $time) || $end) {
            if ($end) {
                $this -> detail -> add($data);
            }

            $score['exam_id'] = $examQuestion['exam_id'];
            $score['course_id'] = $exam['course_id'];
            $score['company_id'] = $this -> company;
            $score['account_id'] = $this -> account_id;

            $total = $this -> detail -> getField(['account_id' => $this -> account_id, 'exam_questions_id' => $g['id'], 'status' => 1], 'sum(score) as total');
            $score['score'] = (!empty($total['total'])) ? $total['total'] : 0;
            $result = $this -> member -> add($score);
            if ($result) {
                $this -> e(0, $total);
            }
        } else {
            $result = $this -> detail -> add($data);
        }

        if ($result) {
            $this -> e();
        } else {
            $this -> e('系统异常');
        }
    }
}