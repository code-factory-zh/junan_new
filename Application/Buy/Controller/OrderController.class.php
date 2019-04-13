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

		if ($p['count'] * $p['unit_price'] != $p['total_price']) {
			$this -> e('您的订单价格有误！');
		}

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

		$done = $this -> order -> add($data);
		if (!$done) {
			$this -> e('创建订单失败，请重试！');
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
			$input -> SetTotal_fee($p['total_price']);
			$input -> SetTime_start(date("YmdHis"));
			$input -> SetTime_expire(date("YmdHis", time() + 600));
			$input -> SetNotify_url("http://wxpay.joinersafe.com/manage/pay/setpay");
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

			// $editAddress = $tools -> GetEditAddressParameters();
			// pr($jsApiParameters);

			$rel['wx'] = $jsApiParameters;
		}

		$this -> rel($rel) -> e();
	}
}