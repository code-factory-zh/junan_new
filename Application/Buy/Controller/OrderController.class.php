<?php

/**
 * 公众号订单相关
 * @Auther QiuXiangCheng
 * @Date   2019/04/10
 */

namespace Buy\Controller;
use Common\Controller\BaseController;
use Manage\Model\AccountModel;
use Think\Controller;
class OrderController extends CommonController{

	private $user;
	private $course;
	private $order;
	private $account;

	public function _initialize() {

		$this -> ignore_token();
		parent::_initialize();

		$this -> user    = new \Buy\Model\UserModel;
		$this -> course  = new \Buy\Model\CourseModel;
		$this -> order   = new \Buy\Model\OrderModel;
		$this -> account = new \Manage\Model\AccountModel;
	}

	private function printf_info($data) {
	    foreach($data as $key=>$value){
	        echo "<font color='#00ff55;'>$key</font> :  ".htmlspecialchars($value, ENT_QUOTES)." <br/>";
	    }
	}

	public function getInfo() {

		$data = [
			'tips' => '联系运营人员进行转帐',
			'list' => [
				[
					'name'  => '帐户名',
					'value' => '李xxx',
				],
				[
					'name'  => '开户行',
					'value' => '中山市xxxxx',
				],
				[
					'name'  => '银行帐号',
					'value' => '6214xxxxxxxxxxxx',
				],
				[
					'name'  => '电话',
					'value' => '0774-00000',
				],
			],
		];
		$this -> rel($data) -> e();
	}

	/**
	 * 生成订单
	 * @Author   邱湘城
	 * @DateTime 2019-04-10T00:36:07+0800
	 */
	public function create() {

		if (!count($this -> ufo)) {
			$this -> e('请先登录！');
		}

		$this -> _get($p, ['open_id', 'pay_type' => '支付方式', 'count' => '请输入课程数量', 'unit_price' => '请输入课程单价', 'total_price' => '请输入课程总价']);
		$this -> isInt(['count', 'unit_price', 'total_price']);

		// if ($p['count'] * $p['unit_price'] != $p['total_price']) {
		// 	$this -> e('您的订单价格有误！');
		// }

		// 生成内部预付订单
		$time = time();
		$orderNum = date('YmdHis') . mt_rand(10, 99) . $time;
		$data = [
			'company_id' => $this -> ufo['id'],
			'order_num' => $orderNum,
			'created_time' => $time,
			'pay_type' => $p['pay_type'],
			'amount' => $p['total_price'],
			'count' => $p['count'],
		];

		$order_id = $this -> order -> add($data);
		if (!$order_id) {
			$this -> e('创建订单失败，请重试！');
		}

		$course = $this -> course -> getCourseList(['is_deleted' => 0], 2);
		if (!count($course)) {
			$this -> e('找不到订单所需数据！');
		}

		// 保存订单详情
		$orderDetail = [
			'company_id' => $this -> ufo['id'],
			'order_id' => $order_id,
			'created_time' => $time,
			'updated_time' => $time,
		];

		$course_id = array_column($course, 'price', 'id');
		foreach ($course_id as $courseId => $price) {
			$orderDetail['course_id'] = $courseId;
			M('order_detail') -> add($orderDetail);
		}

		// 收集需返回的数据
		$rel = [
			'self_order_sn' => $orderNum,
			'price' => floatval($p['total_price']),
		];

		// 利用微信支付
		if ($p['pay_type'] == 'wechat_sdk') {

			vendor('Wxpay.example.WxPayJsApiPay');
			vendor('Wxpay.example.WxPayConfig');
			vendor('Wxpay.lib.WxPayApi');

			$tools = new \JsApiPay();
			$openId = $p['open_id'];

			// $openId = $tools -> GetOpenid();
			// if (is_null($openId)) {
				// $this -> e('获取必要参数失败，请确保code未被重复使用！');
			// }

			$input = new \WxPayUnifiedOrder();
			$input -> SetBody("购买课程");
			$input -> SetGoods_tag("购买课程");
			$input -> SetAttach("公司统一购买课程");
			$input -> SetOut_trade_no($data['order_num']);
			$input -> SetTotal_fee($p['total_price'] * 100);
			$input -> SetTime_start(date("YmdHis", $time));
			$input -> SetTime_expire(date("YmdHis", $time + 600));
			$input -> SetNotify_url("http://wxpay.joinersafe.com/manage/pay/finishedWxPay");
			$input -> SetTrade_type("JSAPI");
			$input -> SetOpenid($openId);
			$input -> SetSignType("MD5");

			$config = new \WxPayConfig();
			$order = \WxPayApi::unifiedOrder($config, $input);

			if ($order['return_code'] == 'FAIL') {
				$this -> rel($order) -> e('失败，详见微信返回数据提示。');
			}

			$jsApiParameters = $tools -> GetJsApiParameters($order);
			$jsApiParameters = json_decode($jsApiParameters, true);

			$getTicketInfo = $this -> getJsapiTicket($p['open_id']);
			if (!isset($getTicketInfo)) {
				$this -> e('获取 jsapi_ticket 失败');
			}

			$signs = [
				'jsapi_ticket' => $getTicketInfo,
				'noncestr' => $jsApiParameters['nonceStr'],
				'timestamp' => $jsApiParameters['timeStamp'],
				'url' => str_replace('amp;', '', urldecode($p['url'])),
			];

			$sign_str = '';
			foreach ($signs as $k => $items) {
				if ($k != 'sign') {
					$sign_str .= $k .= '=' . $items . '&';
				}
			}

			$sign_str = trim($sign_str, '&');
			$jsApiParameters['signature'] = sha1($sign_str);

			$rel['wx'] = $jsApiParameters;
		}

		$this -> rel($rel) -> e();
	}

	// 取jsapi_ticket
	public function getJsapiTicket($open_id) {

		$data = M() -> table('user_wx') -> where(['openid' => $open_id]) -> find();
		if (!is_null($data) && count($data)) {
			return $data['jsapi_ticket'];
		}

		$rel = $this -> getAccessTokens();
		if (!isset($rel['access_token'])) {
			$this -> e('获取access_token失败');
		}

		$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token={$rel['access_token']}&type=jsapi";
		$ret = $this -> httpGet($url);

		$insert = [
			'openid' => $open_id,
			'access_token' => $rel['access_token'],
			'jsapi_ticket' => $ret['ticket'],
		];
		M() -> table('user_wx') -> add($insert);

		return $ret['ticket'];
	}

	// 取 access_token
	public function getAccessTokens() {

		$auth = self::getScreat();
		$url = "https://api.weixin.qq.com/cgi-bin/token?appid={$auth[2]}&secret={$auth[3]}&grant_type=client_credential";
		return $this -> httpGet($url);
	}

	private static function getScreat() {

		$fi = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/cert/screat');
		return explode("\n", trim($fi));
	}
}