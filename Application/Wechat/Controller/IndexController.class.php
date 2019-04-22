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
	 * 上传用户头像
	 * @Author   邱湘城
	 * @DateTime 2019-04-22T00:31:04+0800
	 */
	public function uploadImg() {

		$this -> ignore_token();
		if (is_null($_FILES)) {
			$this -> e('没有检测到图片文件！');
		}

		$file = $_FILES['file'];
		$res = $this -> saveFile($file);
		if ($res == '') {
			$this -> e('上传失败，请确保form表单的文件域name名称为 file');
		}

		$this -> rel(['filepath' => $res]) -> e(0);
	}

	// 保存上传的图片
	private function saveFile($file) {

		$upload = new \Think\Upload();
		$upload -> maxSize  = 3145728 ;// 设置附件上传大小
		$upload -> exts     = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
		$upload -> rootPath = './Uploads/user_pic/'; // 设置附件上传根目录
		$upload -> savePath = ''; // 设置附件上传（子）目录

		// 上传文件
		$info = $upload -> uploadOne($file);
		if(!$info) { // 上传错误提示错误信息
			return '';
		}
		return $info['savepath'] . $info['savename'];
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
				if (is_null($values['is_pass_exam'])) {
					$values['is_pass_exam'] = 0;
				}

				if ($values['is_pass_exam']) {
					$values['studied'] = $values['total_chapter'];
					$values['finished'] = 2;
					$values['btn'] = '已完成';
				} else if ($values['total_chapter'] == $values['studied'] && $values['total_chapter'] > 0) {
					$values['finished'] = 1;
					$values['btn'] = '去考试';
				} else {
					$values['btn'] = '学习中';
					$values['finished'] = 0;
				}
			}
		}

		if (!is_null($list) && count($list)) {
			$data['list'] = $list;
		}
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