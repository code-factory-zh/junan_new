<?php

/**
 * Manage模块基类
 * @Auther QiuXiangCheng
 * @Date 2017/12/12
 */
namespace Buy\Controller;
use Common\Controller\BaseController;
use Buy\Model\UserModel;
class CommonController extends BaseController {

	// 当前通过微信open_id登录的用户信息
	protected $ufo = [];

	public function _initialize() {

		parent::_initialize();
		$this -> assign('uf', $this -> userinfo);
		$this -> assign('domain', $this -> select_domain());

		$open_id = $this -> requests('open_id');
		if ($open_id) {
			$user = new \Buy\Model\UserModel;
			$this -> ufo = $user -> getCompanyByWhere(['open_id' => $open_id], ['id', 'code', 'company_name', 'share_id']);
		}
		is_null($this -> ufo) && $this -> ufo = [];
	}
}