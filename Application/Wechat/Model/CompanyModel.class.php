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

    /**
     * 检查当前邀请码是否可用
     * @Author   邱湘城
     * @DateTime 2019-04-03T00:08:39+0800
     */
    public function checkShardId($arr) {

        $err = ['id' => 0, 'err' => ''];
        $find = $this -> where(['share_id' => $arr['tk'], 'status' => 0]) -> find();
        if (is_null($find) || !count($find)) {
            $err['err'] = '未找到邀请码';
            return $err;
        }

        $amount = $this -> table('account') -> where(['company_id' => $find['id'], 'status' => 0]) -> count();
        if ($find['stu_amount'] <= $amount) {
            $err['err'] = '您当前的企业可绑定名额不足，无法进行注册。';
            return $err;
        }

        $err['id'] = $find['id'];
        return $err;
    }
}