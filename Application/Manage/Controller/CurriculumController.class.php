<?php

/**
 * 课程模块
 * @Auther QiuXiangCheng
 * @Date 2018/12/09
 */
namespace Manage\Controller;
use Common\Controller\BaseController;
class CurriculumController extends CommonController {

	private $account;
	private $curriculum;

	public function _initialize() {

		parent::_initialize();
		$this -> islogin();
		$this -> ignore_token();

		$this -> course = new \Manage\Model\CourseModel;
		$this -> account = new \Manage\Model\AccountModel;
		$this -> curriculum = new \Manage\Model\CurriculumModel;
	}

	/**
	 * 岗位课程列表
	 * @DateTime 2018-12-11T23:56:15+0800
	 */
	public function list() {

		$this -> _get($g);
		$data = [];

		// $session_key = 'company_id:order:' . $this -> userinfo['id'];
		// session($session_key, null);

		$where = 1;
		$data['list'] = $this -> curriculum -> getCourseListByWhere($where, 'c.id, c.name, c.amount price, c.job_id, cac.amount');
		$data['course_id'] = $g['course_id'];
		$data['job_id'] = $g['job_id'];

		$data['buy'] = 0;
		$s = session('company_id:order:' . $this -> userinfo['id']);
		if (!is_null($s)) {
			$data['buy'] = 1;
		}
		$this -> assign($data);
		$this -> display('Curriculum/list');
	}


	/**
	 * 展示当前企业可购买此课程的员工
	 * @Author   邱湘城
	 * @DateTime 2019-01-11T21:36:53+0800
	 */
	public function show_account() {

		$session_key = 'company_id:order:' . $this -> userinfo['id'];
		// pr(session($session_key));
		// session($session_key, null);

		$this -> _get($p, ['course_id']);
		$where = ['c.id' => $p['course_id'], 'a.company_id' => $this -> userinfo['id'], 'a.status' => 0, 'c.is_deleted' => 0];
		$list = $this -> account -> getAccountsByWhere($where);

		// 去重
		$where = ['company_id' => $this -> userinfo['id'], 'course_id' => $p['course_id'], 'status' => 0];
		$accounts = $this -> account -> getCourseByCpnid($where);
		$accounts = array_values($accounts);
		foreach ($list as $k => &$items) {
			if (in_array($items['account_id'], $accounts)) {
				unset($list[$k]);
				continue;
			}
			$items['selected'] = 0;
		}
		$list = array_values($list);

		// 默认选中刚刚已经点过的人
		$ss = session($session_key);
		if (!is_null($ss)) {
			if (isset($ss[$p['course_id']])) {
				if (isset($ss[$p['course_id']]['phone_list'])) {
					$record = $ss[$p['course_id']]['phone_list'];
					if (count($record)) {
						foreach ($list as &$values) {
							if (in_array($values['mobile'], $record)) {
								$values['selected'] = 1;
							}
						}
					}
				}
			}
		}

		$data = ['course_id' => $p['course_id'], 'job_id' => $p['job_id'], 'list' => $list];
		$this -> assign($data);
		$this -> display('Curriculum/certificate');
	}


	/**
	 * 提交数据到购物车
	 * @Author   邱湘城
	 * @DateTime 2019-01-11T23:06:09+0800
	 */
	public function add_course() {

		$this -> _post($p, ['course_id', 'job_id', 'account']);

		$session_key = 'company_id:order:' . $this -> userinfo['id'];
		$accounts = [];
		$list = $this -> account -> getAccount(['a.company_id' => $this -> userinfo['id'], 'a.status' => 0]);

		foreach ($list as $job) {
			$accounts[$job['mobile']][] = $job['job_id'];
		}
		$data = ['course_id' => $p['course_id'], 'job_id' => $p['job_id'], 'price' => 0, 'phone_list' => []];

		$course_price = $this -> course -> getCourseAmount(['id' => $p['course_id']]);
		if (is_null($course_price)) {
			$this -> e('异常，无法取得课程价格！');
		}

		unset($p['course_id'], $p['job_id']);
		foreach ($p['account'] as $items) {
			if (empty($items)) {
				$this -> e('输入框内的手机号码不得为空');
			}

			$this -> phoneCheck($items, '手机号码"' . $items . '"不规范！');
			if (!isset($accounts[$items])) {
				$this -> e('手机号码"' . $items . '"不在您的帐户下，请检查！');
			}

			if (!in_array($data['job_id'], $accounts[$items])) {
				$this -> e('用户"' . $items . '"的工作岗位不适用于该课程！');
			}
			$data['price'] += floatval($course_price);
			$data['phone_list'][] = $items;
		}
		// pr($data);

		// 保存课程
		$session_list = session($session_key);
		if (is_null($session_list)) {
			session($session_key, [$data['course_id'] => $data]);
		} else {
			if (isset($session_list[$data['course_id']])) {
				$tmp = array_merge($session_list[$data['course_id']]['phone_list'], $data['phone_list']);
				$session_list[$data['course_id']]['phone_list'] = array_unique($tmp);
			} else {
				$session_list[$data['course_id']] = $data;
			}
			session($session_key, $session_list);
		}

		// pr(session($session_key));
		$this -> e(0);
	}


	/**
	 * 订单列表
	 * @DateTime 2019-01-05T14:17:50+0800
	 */
	public function order_list() {

		$data = [];
		$total = 0;

		$session_key = 'company_id:order:' . $this -> userinfo['id'];
		$list = session($session_key);

		// 生成订单号
		// $this -> fetch_order_num();
		if (count($list)) {
			$jobs = $this -> account -> getCourse();
			$users = $this -> account -> getAccountColumn(['company_id' => $this -> userinfo['id']]);
			foreach ($list as $k => $items) {
				$tmp = [
					'job_name' => $jobs[$items['course_id']],
					'users' => [],
					'price' => $items['price'],
				];
				$total == $items['price'];
				foreach ($items['phone_list'] as $v) {
					$tmp['users'][] = ['mobile' => $v, 'name' => $users[$v]];
				}
				$data[$k] = $tmp;
			}
		}

		// pr($data);
		$this -> assign('total', $total);
		$this -> assign('list', $data);
		$this -> display('Curriculum/buy');
	}

	// 删除购物车东西
	public function delShoppingCar() {

		$this -> _get($p);
		if (empty($p['key'])) {
			echo "<script>alert('错误！');</script>";
		}

		$del = explode(',', $p['key']);
		$session_key = 'company_id:order:' . $this -> userinfo['id'];
		$list = session($session_key);

		$list[$del[0]]['price'] = $list[$del[0]]['price'] - ($list[$del[0]]['price'] / count($list[$del[0]]['phone_list']));
		unset($list[$del[0]]['phone_list'][$del[1]]);
		if (empty($list[$del[0]]['phone_list'])) {
			unset($list[$del[0]]);
		}

		session($session_key, $list);
		exit(header('Location: /manage/curriculum/order_list'));
	}
}