<?php

/**
 * 课程基本信息模块
 * @Auther cuiruijun
 * @Date 2018/12/11
 */
namespace Manage\Controller;
use Common\Controller\BaseController;
class CourseController extends CommonController {


	private $course;
	private $job;
	private $company_account;

	public function _initialize() {

		parent::_initialize();
		$this -> islogin();
		$this->course = new \Manage\Model\CourseModel;
		$this->job = new \Manage\Model\JobModel;
		$this->company_account = new \Wechat\Model\CompanyAccountModel;
	}

	/**
	 * 课程-列表
	 * @author cuiruijun
	 * @date   2018/12/10 下午11:59
	 * @url    manage/job
	 * @return  array
	 */
	public function index() {
		$courses = $this->course->getList();

		$data['list'] = $courses;
		$this->assign($data);
		$this->display();
	}

	/**
	 * 编辑课程
	 * @author cuiruijun
	 * @date   2018/12/10 下午11:59
	 * @url    manage/job/edit
	 * @return  array
	 */
	public function edit(){
		if (IS_POST) {
			$data = ($_POST);
//			$this->_post($data, ['name']);

			if (!$data['name']) {
				$this->el(0, '课程名称不能为空!');
			}

			$res = $this->course->getOne('type = 1 and is_deleted = 0');

			if(!$data['id']){
				//新增
				//通用课程只能有一个
				if($res && ($data['type'] == 1)){
					$this->e('通用课程只能有一个');
				}

				if($result = $this->course->add($data)){
					$this->e();
				}else{
					$this->e('fail');
				}
			}else{

				$course_info_modify = $this->course->getOne('id = ' . $data['id']);
				if($course_info_modify['type'] == 1 && $data['type'] == 0){
					$this->e('通用课程不能更改为专业课程');
				}

				if($data['type'] == 1 && $res['id'] != $data['id']){
					$this->e('通用课程只能有一个');
				}

				if($data['type'] == 1){
					$data['job_id'] = 0;
				}

				//修改
				if($result = $this->course->save($data)){
					$this->e();
				}else{
					$this->el($result, 'fail');
				}
			}
		}

		//参数
		if (!empty(I('get.id'))){
			$course_info = $this->course->getOne('id = ' . I('get.id'));
		}

		$data['list'] = $course_info;
		$data['jobs'] = $this->job->getJobs('id, name');
		$this->assign($data);
		$this->display();
	}

	/**
	 * 删除课程
	 * @author cuiruijun
	 * @url    manage/course/del
	 * @return  array
	 */
	public function del()
	{
		if (!empty(I('post.id'))){
			$data = [
				'is_deleted' => 1,
				'id' =>  I('post.id')
			];
			//1.该课程是否有有人购买且学习进度还有效。
			//2.该课程是否有人购买，在学习进度无效的时候是否考试通过了，如果没通过也不给删除。
			$is_not_pass_list = $this->company_account->getRecord(['course_id' => $data['id'], 'is_pass_exam' => 0]);

			if($is_not_pass_list){
				$this->e('此课程正在被使用,不能被删除');
			}

			$result = $this->course->save($data);
			if($result){
				$this->e();
			}else{
				$this->e('删除失败');
			}
		}
	}

}