<?php

/**
 * @Dec    主页控制器
 * @Auther QiuXiangCheng
 * @Date   2019/01/13
 */

namespace Wechat\Controller;
use Wechat\Model\AdminModel;

class IndexController extends CommonController {

	private $company;
	private $user;
	private $account_course;

	public function _initialize() {

		parent::_initialize();
		$this -> company = new \Wechat\Model\CompanyModel;
		$this -> user = new \Wechat\Model\UserModel;
		$this -> account_course = new \Wechat\Model\AccountcourseModel;
		$this -> course_detail = new \Wechat\Model\DetailcourseModel;
	}


	/**
	 * 取得企业数据
	 * @Author   邱湘城
	 * @DateTime 2019-01-15T23:26:23+0800
	 */
	public function get_companys() {

		$list = $this -> company -> getList(['status' => 0], ['id', 'company_name']);
		$this -> rel(['company_name' => '君安', 'list' => $list]) -> e();
	}


	/**
	 * 主页获取课程列表
	 * @Author   邱湘城
	 * @DateTime 2019-01-15T23:40:18+0800
	 */
	public function course_list() {

		$this -> _get($p);

		$data = ['banner' => 'http://admin.joinersafe.com/img/idx_banner.png', 'list' => []];
		$list = $this -> account_course -> getCompanyCourseList($this -> u['id']);
		if (!is_null($list) && count($list)) {

			foreach ($list as &$values) {
				$values['icon'] = '';
				$values['type_icon'] = 1;
				$values['studied'] = $values['studied'];
				if (is_null($values['finished'])) {
					$values['finished'] = 0;
				}


				// 全部学完可以考试
				// 按钮点亮
				$values['btn'] = '学习中';
				if ($values['total_chapter'] == $values['studied'] && $values['total_chapter'] > 0) {
					$values['finished'] = 1;
					$values['btn'] = '去考试';
				}


				// 但如果已有考试通过按钮熄灭
				if ($values['is_pass_exam']) {
					$values['studied'] = $values['total_chapter'];
					$values['finished'] = 0;
					$values['btn'] = '已完成';
				}
			}
		}

		if (!is_null($list) && count($list)) {
			$data['list'] = $list;
		}
		// pr($list);
		$this -> rel($data) -> e();




		/**
		 * 下面是旧的，不需要了
		 */


		$where = "cac.account_id = {$this -> u['id']}";
		$list = $this -> account_course -> getListCourses($where);
		if (!is_null($list) && count($list)) {
			foreach ($list as &$items) {

				$items['finished'] = 0;
				// 全部学完可以考试
				// 按钮点亮
				if ($items['total_chapter'] == $items['studied'] && $items['total_chapter'] > 0) {
					$items['finished'] = 1;
				}

				// 但如果已有考试通过按钮熄灭
				if ($items['is_pass_exam']) {
					$items['studied'] = $items['total_chapter'];
					$items['finished'] = 0;
				}

				$items['btn'] = '考试';
				$items['url'] = '';
				$items['icon'] = '';
				$items['type_icon'] = '';
			}
		} else {
			$list = [];
		}

		$data = ['banner' => 'http://admin.joinersafe.com/img/idx_banner.png', 'list' => $list];
		$this -> rel($data) -> e();
	}
}