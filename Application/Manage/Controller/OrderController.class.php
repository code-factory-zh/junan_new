<?php

/**
 * 课程订单管理模块
 * @Auther cuiruijun
 * @Date 2018/12/11
 */
namespace Manage\Controller;
use Common\Controller\BaseController;
class OrderController extends CommonController {


	private $order;
	private $company_account;

	public function _initialize() {

		parent::_initialize();
		$this -> islogin();
		$this->order = new \Manage\Model\OrderModel;
		$this->company_account = new \Wechat\Model\CompanyAccountModel;
	}

	/**
	 * 课程-列表
	 * @author cuiruijun
	 * @date   2018/12/10 下午11:59
	 * @url    manage/job
	 * @return  array
	 */
	public function index() {
		$params = I('get.');
		$where = '1=1';
		$begin_time = $params['begin_time'];
		$end_time = $params['end_time'];

		if($begin_time){
			$where .= ' and o.created_time > '. strtotime($begin_time);
		}

		if($end_time ){
			$where .= ' and o.created_time < ' . strtotime($end_time);
		}

		$page = I('page');

		$limit = pageLimit($page);

		$courses = $this->order->getOrderList($where, $limit);

		$data['param'] = [
			'begin_time' => $begin_time,
			'end_time' => $end_time,
		];

		$data['list'] = $courses['list'];
		$data['page'] = page($courses['count'], $page);
		$this->assign($data);
		$this->display();
	}

	/**
	 * 确认支付页面
	 * @author cuirj
	 * @date   2019/4/21 下午11:40
	 */
	public function confirm_pay(){
		$data['list'] = I('get.id');
		$this->assign($data);
		$this->display('Order/confirm_pay');
	}

	/**
	 * 确认支付
	 * @author cuiruijun
	 * @date   2018/12/10 下午11:59
	 * @url    manage/order/change_status
	 * @method post
	 *
	 * @param  int status 1-启用,0-禁止
	 * @return  array
	 */
	public function change_status()
	{
		//判断当前传的参数和数据库中是否相同,如果相同则报错
		$where['id'] = I('post.id');
		$data['status'] = 1;
		$data['remark'] = I('post.remark');
		$result = $this->order->updateData($where, $data);
		if ($result) {
			$this->e();
		} else {
			$this->el($result, '修改失败,请重试');
		}
	}

	/**
	 * 导出数据
	 * @author cuirj
	 * @date   2019/5/10 下午4:45
	 */
	public function import_data(){
		$params = I('get.');
		$where = '1=1';
		$begin_time = $params['begin_time'];
		$end_time = $params['end_time'];

		if($begin_time){
			$where .= ' and o.created_time > '. strtotime($begin_time);
		}

		if($end_time ){
			$where .= ' and o.created_time < ' . strtotime($end_time);
		}

		$courses = $this->order->getOrderList($where);

		//付款类型
		$pay_type = [
			'wechat_sdk' => '在线支付',
			'card' => '银行转账',
		];

		$courses_export = [];
		foreach($courses['list'] as $k => $v){
			$courses_export[] = [
				'id' => $v['id'],
				'order_num' => $v['order_num'],
				'company_name' => $v['company_name'],
				'amount' => $v['amount'],
				'price' => $v['price'],
				'pay_type' => $v['pay_type'] ? $pay_type[$v['pay_type']] : '',
				'remark' => $v['remark'],
				'status' => $v['status'] ? '已支付' : '未支付',
				'created_time' => date('Y-m-d H:i:s', $v['created_time']),
			];
		}

		$xlsCell = array(
			array('id', 'ID'),
			array('order_num', '订单号'),
			array('company_name', '企业名称'),
			array('amount', '课程总价格'),
			array('price', '实付金额'),
			array('pay_type', '支付方式'),
			array('remark', '订单备注'),
			array('status', '支付状态'),
			array('created_time', '添加时间'),
		);

		$this->exportExcel('课程购买订单',$xlsCell,$courses_export);
	}

}