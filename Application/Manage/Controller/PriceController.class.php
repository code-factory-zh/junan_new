<?php

/**
 * 岗位模块
 * @Auther cuiruijun
 * @Date 2018/12/11
 */
namespace Manage\Controller;
use Common\Controller\BaseController;
class PriceController extends CommonController {


	private $job;
	private $course;

	public function _initialize() {

		parent::_initialize();
		$this -> islogin();
		$this->sys = new \Manage\Model\SysModel;
	}

	/**
	 * 编辑收款账号
	 * @author cuiruijun
	 * @date   2018/12/10 下午11:59
	 * @url    manage/job/edit
	 * @return  array
	 */
	public function edit_ajax(){
		if (IS_POST) {
			$data = I('post.');

			//修改
			if($result = $this->sys->save($data)){
				$this->e();
			}else{
				$this->e('修改失败');
			}
		}
	}

	/**
	 * 编辑价格
	 * @author cuiruijun
	 * @date   2018/12/10 下午11:59
	 * @url    manage/job/edit
	 * @return  array
	 */
	public function conf(){
		//参数
		$jobs = $this->sys->getOne('cfg_key="price"');

		$data['list'] = $jobs;
		$this->assign($data);
		$this->display();
	}
}