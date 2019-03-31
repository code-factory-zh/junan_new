<?php

/**
 * Manage模块基类
 * @Auther QiuXiangCheng
 * @Date 2017/12/12
 */
namespace Buy\Controller;
use Common\Controller\BaseController;
class CommonController extends BaseController {

	public function _initialize() {

		parent::_initialize();
		$this -> assign('uf', $this -> userinfo);
		$this -> assign('domain', $this -> select_domain());
	}
}