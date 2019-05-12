<?php

/**
 * @Dec    Manage模块主模型
 * @Auther cuiruijun
 * @Date   2018/12/10
 */

namespace Manage\Model;

use Common\Model\BaseModel;

class OrderModel extends BaseModel
{
    protected $tableName = '`order`';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 取得订单列表信息
     * @DateTime 2019/4/21 下午10:57
     */
    public function getOrderList($where = null, $limit = '')
    {
		$count = $this->alias('o')
			->field('o.order_num, o.id, o.amount, o.price, o.pay_type, o.remark, o.created_time, o.status, c.company_name')
			->join('company as c on o.company_id = c.id', 'left')
			->where($where)
			->limit($limit)
			->count();

		$list = $this->alias('o')
			->field('o.order_num, o.id, o.amount, o.price, o.pay_type, o.remark, o.created_time, o.status, c.company_name')
			->join('company as c on o.company_id = c.id', 'left')
			->where($where)
			->limit($limit)
			->select();

		return [
			'list' => $list,
			'count' => $count,
		];
    }

	public function _before_update(&$data, $options)
	{
		$data['updated_time'] = time();
	}
}