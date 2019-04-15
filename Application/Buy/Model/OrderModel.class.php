<?php

/**
 * @Dec    订单相关
 * @Auther QiuXiangCheng
 * @Date   2019/03/31
 */

namespace Buy\Model;
use Common\Model\BaseModel;

class OrderModel extends BaseModel {

	protected $tableName = 'order';

	public function _initialize() {

		parent::_initialize();
	}

	/**
	 * 查询订单信息
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T15:08:50+0800
	 */
	public function getOrderList($where, $type = 1) {

		$this -> where($where);

		if ($type == 1) {
			return $this -> find();
		}

		return $this -> select();
	}

	/**
	 * 查询某公司下单购买的课程总量 (可能有多个订单)
	 * @Author   邱湘城
	 * @DateTime 2019-03-31T15:10:27+0800
	 */
	public function getTotalOrderAmount($company_id) {

		$sql = "SELECT SUM(count) c FROM `{$this -> tableName}` WHERE company_id = {$company_id} AND status = 1";
		$data = $this -> query($sql);

		if (is_null($data) || !isset($data[0])) {
			return 0;
		}
		return is_null($data[0]['c']) ? 0 : intval($data[0]['c']);
	}

	/**
	 * 保存订单详情
	 * @Author   邱湘城
	 * @DateTime 2019-04-16T00:56:40+0800
	 */
	public function saveOrderDetail($orderInfo) {

		return $this -> table('order_detail') -> add($orderInfo);
	}

	/**
	 * 根据用户订单号发送一个
	 * @Author   邱湘城
	 * @DateTime 2019-04-15T23:57:32+0800
	 */
	public function sendGoodsToUserFromOrderSn($orderSN) {


	}
}