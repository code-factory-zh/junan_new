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

	/**
	 * 生成订单
	 * @Author   邱湘城
	 * @DateTime 2019-04-10T00:36:07+0800
	 */
	public function create() {

		if (!count($this -> ufo)) {
			$this -> e('请先登录！');
		}

		$this -> _get($p, ['count' => '请输入课程数量', 'unit_price' => '请输入课程单价', 'total_price' => '请输入课程总价']);
		$this -> isInt(['count', 'unit_price', 'total_price']);

		if ($p['count'] * $p['unit_price'] != $p['total_price']) {
			$this -> e('您的订单价格有误！');
		}

		$time = time();
		$orderNum = date('YmdHis') . mt_rand(10, 99) . $time;
		$data = [
			'company_id' => $this -> ufo['id'],
			'order_num' => $orderNum,
			'created_time' => $time,
			'amount' => $p['total_price'],
			'count' => $p['count'],
		];

		$done = $this -> order -> add($data);
		if (!$done) {
			$this -> e('创建订单失败，请重试！');
		}

		vendor('Wxpay.example.WxPayJsApiPay');
		$tools = new \JsApiPay();
		$openId = $tools -> GetOpenid();

		$input = new \WxPayUnifiedOrder();
		$input -> SetBody("test");
		$input -> SetAttach("test");
		$input -> SetOut_trade_no("sdkphp".date("YmdHis"));
		$input -> SetTotal_fee("1");
		$input -> SetTime_start(date("YmdHis"));
		$input -> SetTime_expire(date("YmdHis", time() + 600));
		$input -> SetGoods_tag("test");
		$input -> SetNotify_url("http://wxpay.joinersafe.com/manage/pay/setpay");
		$input -> SetTrade_type("JSAPI");
		$input -> SetOpenid($openId);
		$config = new \WxPayConfig();
		$order = \WxPayApi::unifiedOrder($config, $input);

		$jsApiParameters = $tools -> GetJsApiParameters($order);

		$editAddress = $tools -> GetEditAddressParameters();
		pr($editAddress);

		$this -> rel(['self_order_sn' => $orderNum, 'price' => floatval($p['total_price'])]) -> e();
	}
}