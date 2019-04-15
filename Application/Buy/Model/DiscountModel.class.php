<?php

/**
 * @Dec    优惠政策
 * @Auther QiuXiangCheng
 * @Date   2019/04/13
 */

namespace Buy\Model;
use Common\Model\BaseModel;

class DiscountModel extends BaseModel {

	protected $tableName = 'discount';

	public function _initialize() {

		parent::_initialize();
	}


	/**
	 * 取得优惠数据
	 * @Author   邱湘城
	 * @DateTime 2019-04-13T01:53:52+0800
	 */
	public function getList() {

		return $this -> select();
	}
}