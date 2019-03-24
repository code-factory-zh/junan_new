<?php

/**
 * 课程基本信息模块
 * @Auther cuiruijun
 * @Date 2018/12/11
 */
namespace Manage\Controller;
use Common\Controller\BaseController;
class DiscountController extends CommonController {

	private $discount;

	public function _initialize() {

		parent::_initialize();
		$this -> islogin();
		$this->discount = new \Manage\Model\DiscountModel();
	}

	/**
	 * 课程-列表
	 * @author cuiruijun
	 * @date   2018/12/10 下午11:59
	 * @url    manage/job
	 * @return  array
	 */
	public function index() {
		$discount = $this->discount->getList();

		$data['list'] = $discount;
		$this->assign($data);
		$this->display();
	}

	/**
	 * 编辑课程
	 * @author cuiruijun
	 * @date   2018/12/10 下午11:59
	 * @url    manage/job/edit
	 * @return  array
	 */
	public function edit(){
		if (IS_POST) {
			$data = ($_POST);
//			$this->_post($data, ['name']);

			if (!$data['discount_min_num']) {
				$this->el(0, '优惠下限不能为空!');
			}

			if(!$data['id']){
				//新增
				if($result = $this->discount->add($data)){
					$this->e();
				}else{
					$this->e('fail');
				}
			}else{
				//修改
				if($result = $this->discount->save($data)){
					$this->e();
				}else{
					$this->el($result, 'fail');
				}
			}
		}

		//参数
		if (!empty(I('get.id'))){
			$discount_info = $this->discount->getOne('id = ' . I('get.id'));
		}

		$data['list'] = $discount_info;
		$this->assign($data);
		$this->display();
	}

	/**
	 * 删除课程
	 * @author cuiruijun
	 * @url    manage/course/del
	 * @return  array
	 */
	public function del()
	{
		if (!empty(I('post.id'))){
			$data = [
				'id' =>  I('post.id')
			];

			$result = $this->discount->where($data)->delete();
			if($result){
				$this->e();
			}else{
				$this->e('删除失败');
			}
		}
	}

}