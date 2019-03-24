<?php

/**
 * @Dec    User模型
 * @Auther QiuXiangCheng
 * @Date   2019/01/15
 */
namespace Wechat\Model;
// use Common\Model\BaseModel;

class CompanyModel extends CommonModel {

	protected $tableName = 'company';

    public function _initialize() {

        parent::_initialize();
    }


    /**
     * 根据条件查找用户表
     * @Author   邱湘城
     * @DateTime 2019-01-15T21:36:56+0800
     */
    public function getList($where, $fields = '*') {

    	return $this -> where($where) -> field($fields) -> select();
    }
}