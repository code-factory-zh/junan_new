<?php

/**
 * 岗位模块
 * @Auther cuiruijun
 * @Date 2018/12/11
 */
namespace Manage\Controller;
use Common\Controller\BaseController;
class ReceiptController extends CommonController {


	private $job;
	private $course;

	public function _initialize() {

		parent::_initialize();
		$this -> islogin();
		$this->receipt = new \Manage\Model\ReceiptModel;
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
			if($result = $this->receipt->save($data)){
				$this->e();
			}else{
				$this->e('修改失败');
			}
		}
		//参数
		$jobs = $this->receipt->getOne('id=1');

		$data['list'] = $jobs;
		$this->assign($data);
		$this->display();
	}

	/**
	 * 编辑收款账号
	 * @author cuiruijun
	 * @date   2018/12/10 下午11:59
	 * @url    manage/job/edit
	 * @return  array
	 */
	public function edit(){
		//参数
		$jobs = $this->receipt->getOne('id=1');

		$data['list'] = $jobs;
		$this->assign($data);
		$this->display();
	}
}