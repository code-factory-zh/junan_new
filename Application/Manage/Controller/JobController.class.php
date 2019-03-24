<?php

/**
 * 岗位模块
 * @Auther cuiruijun
 * @Date 2018/12/11
 */
namespace Manage\Controller;
use Common\Controller\BaseController;
class JobController extends CommonController {


	private $job;
	private $course;

	public function _initialize() {

		parent::_initialize();
		$this -> islogin();
		$this->job = new \Manage\Model\JobModel;
		$this->course = new \Manage\Model\CourseModel;
		$this->exam_member = new \Manage\Model\ExamMemberModel;
	}

	/**
	 * 岗位-列表
	 * @author cuiruijun
	 * @date   2018/12/10 下午11:59
	 * @url    manage/job
	 * @return  array
	 */
	public function index() {
		$jobs = $this->job->getJobs('id,name,created_time,is_deleted');

		$data['list'] = $jobs;
		$this->assign($data);
		$this->display();
	}

	/**
	 * 编辑岗位
	 * @author cuiruijun
	 * @date   2018/12/10 下午11:59
	 * @url    manage/job/edit
	 * @return  array
	 */
	public function edit(){
		if (IS_POST) {
			$data = I('post.');
//			$this->_post($data, ['name']);

			if (!$data['name']) {
				$this->e('岗位名称不能为空!');
			}

			if(!$data['id']){
				//新增
				$is_name_exist = $this->job->getJobs('name', ['name' => $data['name']]);
				if($is_name_exist){
					$this->e('岗位名称已经存在了');
				}

				if($result = $this->job->add($data)){
					$this->e();
				}else{
					$this->el($result, 'fail');
				}
			}else{
				//修改
				if($result = $this->job->save($data)){
					$this->e();
				}else{
					$this->e('修改失败');
				}
			}
		}

		//参数
		if (!empty(I('get.id'))){
			$jobs = $this->job->getOne('id = ' . I('get.id'));
		}

		$data['list'] = $jobs;
		$this->assign($data);
		$this->display();
	}

	/**
	 * 删除岗位
	 * @author cuiruijun
	 * @date   2018/12/10 下午11:59
	 * @url    manage/job/del
	 * @return  array
	 */
	public function del()
	{
		if (!empty(I('post.id'))){
			//判断是否有相应课程,如果有,就不能删除
			$course_info = $this->exam_member->getList(['job_id' => I('post.id')]);
			if($course_info){
				$this->e('该岗位已经有相应课程,不能删除');
			}

			$data = [
				'id' => I('post.id'),
				'is_deleted' => 1,
			];
			$result = $this->job->save($data);
			if($result){
				$this->e();
			}else{
				$this->e('删除失败');
			}
		}
	}

}