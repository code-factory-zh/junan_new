<?php

/**
 * 公众号购买课程主页
 * @Auther QiuXiangCheng
 * @Date   2019/03/31
 */

namespace Buy\Controller;
use Common\Controller\BaseController;
use Manage\Model\AccountModel;
use Think\Controller;
class IndexController extends CommonController{

	private $user;

	public function _initialize() {

		$this -> ignore_token();
		parent::_initialize();

		$this -> user     = new \Buy\Model\UserModel;
		$this -> course   = new \Buy\Model\CourseModel;
		$this -> order    = new \Buy\Model\OrderModel;
		$this -> account  = new \Manage\Model\AccountModel;
		$this -> discount = new \Manage\Model\DiscountModel;
	}

	/**
	 * 生成二维码
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T19:16:10+0800
	 */
	public function fetchCode() {

		$this -> _get($p, ['open_id', 'url']);
		if (!count($this -> ufo)) {
			$this -> e('请先登录！');
		}

		$url = $p['url'] . '?tk=' . $this -> ufo['share_id'];
		Vendor('phpqrcode.phpqrcode');
		\QRcode::png($url, false, QR_ECLEVEL_L, 10, 2, false, 0xFFFFFF, 0x000000);
	}

	/**
	 * 买课确认页获取相关的课程价格
	 * @Author   邱湘城
	 * @DateTime 2019-04-13T01:31:19+0800
	 */
	public function comfirmOrder() {

		$rel = [
			'title' => '我要买课',
			'banner' => [
				'tips'  => '课程价格',
				'price' => 100,
				'unit'  => 'RMB',
				'spec'  => 'RMB/人',
			],
			'bottom_tips' => [
				'tips' => '温馨提示：',
				'spec' => [],
				'desc' => '',
			],
			'btn' => '购买',
		];

		$course = $this -> course -> getCourseList(['is_deleted' => 0], 2);
		if (is_null($course) || !count($course)) {
			$this -> e('没有课程信息！');
		}

		$prices = array_column($course, 'price');
		$rel['banner']['price'] = array_sum($prices);

		// 优惠信息
		$discount = $this -> discount -> getList();
		if (count($discount)) {
			foreach ($discount as $items) {
				$dc = $items['discount'] * 10;
				$msg = "购买{$items['discount_min_num']}-{$items['discount_max_num']}份{$dc}折！";
				$rel['bottom_tips']['desc'] .= "<p>{$msg}</p>";
				$rel['bottom_tips']['spec'][] = $msg;
			}
		}

		$this -> rel($rel) -> e();
	}

	/**
	 * 购买课程主页
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T14:41:26+0800
	 */
	public function index() {

		$this -> _get($p, ['open_id']);

		if (!count($this -> ufo)) {
			$this -> e('请先登录！');
		}

		$data = [
			'course_list' => [],
			'history_list' => [],
			'btn_buy' => '买课',
			'btn_invitation' => '邀请学员',
			'user_ttl' => '帐号有效期至：2019年3月31日',
		];

		$data['course_list'] = $this -> course -> getCourseList(['is_deleted' => 0], 2);
		$data['history_list']['buy_amount']    = M() -> table('company') -> where(['id' => $this -> ufo['id']]) -> getField('stu_amount');
		$data['history_list']['learn_amount']  = $this -> account -> getAccountInfoCount(['company_id' => $this -> ufo['id'], 'status' => 0]);
		$data['history_list']['examed_amount'] = 0;

		$this -> rel($data) -> e(0);
	}

	/**
	 * 用户注册页面
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T11:14:35+0800
	 */
	public function register() {

		$this -> _get($p);
		$data = [
			'title' => '提交公司资料',
			'industry' => [
				1 => '冶金行业',
				2 => '有色行业',
				3 => '建材行业',
				4 => '机械行业',
				5 => '轻工行业',
				6 => '纺织行业',
				7 => '烟草行业',
			],
		];

		$this -> rel($data) -> e();
	}

	/**
	 * 注册企业
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T11:15:00+0800
	 */
	public function doregist() {

		$needle = [
			'company_name' => '企业名称',
			'credit_code' => '企业信用代码',
			// 'province' => '省',
			'city' => '城市',
			'county' => '县',
			'address' => '详细地址',
			'industry' => '行业类型',
			'code' => '企业帐号',
			'pwd' => '密码',
			'very_pwd' => '确认密码',
		];

		$this -> _post($p, $needle);
		$this -> lenCheck('pwd', 6);
		$this -> lenCheck('very_pwd', 6);
		$this -> lenCheck('code', 6);

		if ($p['pwd'] != $p['very_pwd']) {
			$this -> e('输入的两次密码不一样！');
		}

		$p['password'] = $this -> _encrypt($p['pwd']);
		unset($p['pwd'], $p['very_pwd']);

		$p['share_id'] = md5(time() . rand(10000, 99999));
		$done = $this -> user -> createCompany($p);
		if (!$done) {
			$this -> e('失败！');
		}

		$this -> e(0, '注册成功！');
	}

	/**
	 * 登录功能
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T11:54:57+0800
	 */
	public function login() {

		$this -> _post($p, ['code' => '帐号', 'pwd' => '密码', 'open_id']);
		$this -> lenCheck('code', 6);
		$this -> lenCheck('pwd', 6);

		if ($this -> user -> login_very($p)) {
			$this -> e();
		}
		$this -> e('登录失败！');
	}

	/**
	 * 通过 open_id 验证当前是否可登录
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T13:27:51+0800
	 */
	public function lgcheck() {

		$this -> _get($p, ['open_id']);
		$find = $this -> user -> userCheck(['open_id' => $p['open_id']]);
		if (!$find) {
			$this -> e('登录验证失败，没有发现当前用户登录态！');
		}
		$this -> e();
	}

	/**
	 * 取OPENID
	 * @Author   邱湘城
	 * @DateTime 2019-04-03T23:56:58+0800
	 */
	public function getOpenId() {

		$this -> _get($p, ['code']);

		$data = $this -> get_open_id($p['code']);
		if (!isset($data['openid'])) {
			$this -> e('失败，微信返回 errorcode：' . $data['errcode']);
		}

		$this -> rel(['open_id' => $data['openid']]) -> e();
	}

	/**
	 * 根据code取得openid
	 * @Author   邱湘城
	 * @DateTime 2019-01-16T21:40:59+0800
	 */
	public function get_open_id($code) {

		$auth = self::getScreat();
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$auth[2]}&secret={$auth[3]}&code={$code}&grant_type=authorization_code";
		return $this -> httpGet($url);
	}

	// 取验证的必要数据
	private static function getScreat() {

		$fi = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/cert/screat');
		return explode("\n", trim($fi));
	}

	/**
	 * 取公众号TOKEN
	 * @Author   邱湘城
	 * @DateTime 2019-04-18T01:54:16+0800
	 */
	public function getAccessToken() {

		$auth = self::getScreat();
		$url = "https://api.weixin.qq.com/cgi-bin/token?appid={$auth[2]}&secret={$auth[3]}&grant_type=client_credential";
		$rel = $this -> httpGet($url);
		if (isset($rel['errcode'])) {
			$this -> rel($rel) -> e('失败！');
		}
		$this -> rel($rel) -> e();
	}

	/**
	 * 取得小程序TOKEN
	 * @Author   邱湘城
	 * @DateTime 2019-04-18T01:51:00+0800
	 */
	public function getAccessTokenXcx() {

		$access_token = session("xcx_access_token");
		if (!is_null($access_token) && !$access_token) {
			return $access_token;
		}

		$auth = self::getScreat();
		$url = "https://api.weixin.qq.com/cgi-bin/token?appid={$auth[0]}&secret={$auth[1]}&grant_type=client_credential";
		$rel = $this -> httpGet($url);
		if (isset($rel['errcode'])) {
			$this -> rel($rel) -> e('失败！');
		}

		session("xcx_access_token", $rel['access_token']);
		return $rel['access_token'];
	}

	/**
	 * 生成当前用户专用二维码
	 * @Author   邱湘城
	 * @DateTime 2019-04-18T01:50:18+0800
	 */
	public function fetchApplicationCode() {

		$this -> _get($p, ['open_id']);

		$data = $this -> user -> getCompanyByWhere(['open_id' => $p['open_id']], 'id,open_id');
		if (is_null($data)) {
			$this -> e('不合法的open_id');
		}

		$token = $this -> getAccessTokenXcx();
		$url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $token;
		
		$params = ['scene' => "id={$data['id']}", 'width' => 430];
		if (isset($p['page'])) {
			$params['page'] = $p['page'];
		}
		$rel = $this -> httpPost($url, $params);

		file_put_contents("./Uploads/code/{$data['open_id']}.png", $rel);

		$this -> rel(['img' => "/Uploads/code/{$data['open_id']}.png"]) -> e();
	}
}