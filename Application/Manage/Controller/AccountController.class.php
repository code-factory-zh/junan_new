<?php

/**
 * 子帐户模块
 * @Auther QiuXiangCheng
 * @Date 2018/12/08
 */
namespace Manage\Controller;
use Common\Controller\BaseController;
class AccountController extends CommonController {


	private $job;
	private $account;

	public function _initialize() {

		parent::_initialize();
		$this -> islogin();
		$this -> job = new \Manage\Model\JobModel;
		$this -> account = new \Manage\Model\AccountModel;
	}


	/**
	 * 子帐户列表管理
	 * @DateTime 2018-12-08T17:58:00+0800
	 */
	public function list() {

		$data = [];
		$jobs = $this -> job -> getJobs('id, name');
		$list = $this -> account -> getAccount(['a.company_id' => $this -> userinfo['id']]);

		$cour = [];
		$courses = $this -> account -> getCourses();
		if (count($courses)) {
			foreach ($courses as $k => $v) {
				 if (!isset($cour[$v['account_id']])){
				 	$cour[$v['account_id']] = $v['course_name'];
				 } else {
				 	$cour[$v['account_id']] .= '，' . $v['course_name'];
				 }
			}
		}

		if (count($list)) {
			foreach ($list as &$items) {
				$items['course_name'] = '-';
				$items['job_name'] = '-';
				if (isset($jobs[$items['job_id']])) {
					$items['job_name'] = $jobs[$items['job_id']];
				}
				if (isset($cour[$items['account_id']])) {
					$items['course_name'] = $cour[$items['account_id']];
				}
			}
		}

		$data['list'] = $list;
		$this -> assign($data);
		$this -> display('Account/list');
	}


	/**
	 * 增加修改子帐号
	 * @DateTime 2018-12-08T17:14:45+0800
	 */
	public function edit() {

		if (IS_POST) {
			$this -> ignore_token() -> _post($p, ['name', 'mobile']);
			$this -> phoneCheck($p['mobile']);
			$this -> isInt(['job_id']);

			$job_id = $p['job_id'];
			$p['company_id'] = $this -> userinfo['id'];

			// 检查是否存在当前用户手机号
			$check = M('account') -> where(['company_id' => $this -> userinfo['id'], 'mobile' => $p['mobile']]) -> count();
			if ($check) {
				$this -> e('您已添加过该用户，请不要重复添加！');
			}

			unset($p['job_id']);
			if (!($id = $this -> account -> add($p))) {
				$this -> e('增加子帐户失败!');
			}

			// 插入关系数据
			$time = time();
			$done = M('account_job') -> table('account_job') -> add([
				'company_id' => $this -> userinfo['id'],
				'account_id' => $id,
				'job_id' => $job_id,
				'created_time' => $time,
				'updated_time' => $time,
			]);

			if (!$done) {
				$this -> e('失败!');
			}
			$this -> e();
		}

		$data = [];
		$data['jobs'] = $this -> job -> getJobs('id, name');

		$this -> assign($data);
		$this -> display('Account/edit');
	}
}