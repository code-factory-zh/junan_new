<?php

/**
 * @Dec    Manage模块主模型
 * @Auther QiuXiangCheng
 * @Date   2017/12/12
 */
namespace Manage\Model;
use Common\Model\BaseModel;

class CommonModel extends BaseModel {

	public function _initialize() {

		parent::_initialize();
	}

	public function baseAuthUsingPnId($pn_id, $bs_id) {

		return $this -> table('pn_ponds') -> where(['id' => $pn_id, 'base_id' => $bs_id]) -> count();
	}
}