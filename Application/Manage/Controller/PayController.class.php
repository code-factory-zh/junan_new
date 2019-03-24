<?php

/**
 * 子帐户模块
 * @Auther QiuXiangCheng
 * @Date 2018/12/08
 */
namespace Manage\Controller;
use Common\Controller\BaseController;
header("Content-type:text/html;charset=utf-8");
class PayController extends CommonController {

	public function _initialize() {

		parent::_initialize();
		$this -> ignore_token();
	}


	/**
	 * 微信回调
	 * @Author   邱湘城
	 * @DateTime 2019-01-10T02:20:51+0800
	 */
	public function setpay() {

		vendor('Wxpay.example.WxPayConfig');
		$config = new \WxPayConfig();

		$notifiedData = file_get_contents('php://input');
		$xmlObj = simplexml_load_string($notifiedData, 'SimpleXMLElement', LIBXML_NOCDATA);
		M('tmp') -> add(['str' => json_encode($xmlObj)]);

        $xmlObj = json_decode(json_encode($xmlObj), true);
		if ($xmlObj['return_code'] == "SUCCESS" && $xmlObj['result_code'] == "SUCCESS") {
			foreach($xmlObj as $k => $v) {
				if($k == 'sign') {
					$xmlSign = $xmlObj[$k];
					unset($xmlObj[$k]);
				};
			}

			$sign = http_build_query($xmlObj);
            $sign = md5($sign . '&key=' . $config -> GetKey());
            $sign = strtoupper($sign);

            if ($sign === $xmlSign) {
                $trade_no = $xmlObj['out_trade_no']; // 总订单号
            	M('order') -> where(['order_num' => $trade_no]) -> save(['status' => 1, 'updated_time' => time()]);
                $this -> callback_ok();
            }
		}
	}


	/**
	 * 回调微信  完成
	 * @Author   邱湘城
	 * @DateTime 2019-01-10T01:41:53+0800
	 */
	private function callback_ok() {

		vendor("Wxpay.lib.WxPayData");
		$notify = new \WxPayNotifyReply;
		$notify -> SetReturn_code("SUCCESS");
		$notify -> SetReturn_msg("OK");
		$xml = $notify -> ToXml();
		echo $xml;
	}


	/**
	 * 检查是否已完成
	 * @Author   邱湘城
	 * @DateTime 2019-01-10T02:21:17+0800
	 */
	public function checkCallBack() {

		$this -> _get($g, ['od']);
		$count = M('order') -> where(['order_num' => $g['od'], 'status' => 0]) -> count();
		if ($count) {
			$this -> e(-1);
		}

		$this -> e(0);
	}


	/**
	 * 跳转用于操作SESSION
	 * @Author   邱湘城
	 * @DateTime 2019-01-11T23:49:36+0800
	 * @return   [type]                   [description]
	 */
	public function finished() {

		$this -> _get($g, ['od']);
		$session_key = 'company_id:order:' . $this -> userinfo['id'];
		$list = session($session_key);
		if (is_null($list)) {
			M('order') -> where(['order_num' => $g['od']]) -> save(['status' => 3]);
			echo '<script>alert("出错，信息已记录，请联系管理员！");window.location.href="/manage/curriculum/list"</script>';
			exit;
		}

		$time = time();
		foreach ($list as $items) {
			$data = [
				'course_id' => $items['course_id'],
				'company_id' => $this -> userinfo['id'],
				'created_time' => $time,
				'updated_time' => $time,
				'status' => 0,
			];
			foreach ($items['phone_list'] as $mobile) {
				$data['account_id'] = M('account') -> where(['mobile' => $mobile, 'company_id' => $this -> userinfo['id'], 'status' => 0]) -> getField('id');
				M('company_account_course') -> add($data);
			}
		}

		session($session_key, null);
		$this -> redirect('/manage/curriculum/list');
	}


	/**
	 * 显示二维码 返回PNG资源
	 * @Author   邱湘城
	 * @DateTime 2019-01-08T23:06:59+0800
	 */
	public function show_wxpay_pic() {

		vendor('Wxpay.example.phpqrcode.phpqrcode');
		$url = urldecode($_GET["data"]);
		if(substr($url, 0, 6) == "weixin") {
			\QRcode::png($url);
		} else {
			header('HTTP/1.1 404 Not Found');
		}
		return ;
	}


	/**
	 * 根据当前订单生成二维码
	 * @Author   邱湘城
	 * @DateTime 2019-01-08T23:57:11+0800
	 */
	public function getCodeUrl() {

		$session_key = 'company_id:order:' . $this -> userinfo['id'];
		$list = session($session_key);
		if (is_null($list) || !count($list)) {
			$this -> e('没有订单信息');
		}

		$totalPrice = 0.00;
		foreach ($list as $items) {
			$totalPrice += floatval($items['price']);
		}
		$totalPrice = intval(bcmul($totalPrice, 100, 2));

// $totalPrice = 1;

		vendor("Wxpay.lib.WxPayApi");
		vendor("Wxpay.example.WxPayNativePay");
		vendor("Wxpay.example.log");

		$tmpInfo = $this -> fetch_order_num();

		$time = time();
		$data = [
			'company_id' => $this -> userinfo['id'],
			'order_num' => $tmpInfo['orderNum'],
			'created_time' => $time,
			'updated_time' => $time,
			'amount' => $totalPrice,
		];
		M('order') -> add($data);

		$notify = new \NativePay();
		$input = new \WxPayUnifiedOrder();
		$input -> SetBody(C('BODY_NAME'));
		$input -> SetAttach("BUY");
		$input -> SetOut_trade_no($tmpInfo['orderNum']);
		$input -> SetTotal_fee($totalPrice);
		$input -> SetTime_start(date("YmdHis"));
		$input -> SetTime_expire(date("YmdHis", time() + 300));

		// $input -> SetGoods_tag("test");
		$input -> SetNotify_url(C('CALL_BACK_URL'));
		$input -> SetTrade_type(C('TRADE_TYPE'));
		$input -> SetProduct_id("123456789");
		$result = $notify -> GetPayUrl($input);
		$url = '/manage/pay/show_wxpay_pic?data=' . urlencode($result["code_url"]);
		$this -> rel(['url' => $url, 'ordernum' => $tmpInfo['orderNum']]) -> e(0, 'Success');
	}
}