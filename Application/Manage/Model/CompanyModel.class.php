<?php

/**
 * @Dec    Manage模块主模型
 * @Auther cuiruijun
 * @Date   2018/12/10
 */

namespace Manage\Model;

use Common\Model\BaseModel;

class CompanyModel extends BaseModel
{
	const STATUS_DISABLE = 1;
    const STATUS_ACTIVE = 0;
    protected $tableName = 'company';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 取得账号信息
     * @DateTime 2018-12-08T18:09:05+0800
     */
    public function getCompany($where)
    {
        if (!isset($where['status'])) {
            $where['status'] = self::STATUS_ACTIVE;
        }
        return $this->table('company')->where($where)->find();
    }

	/**
	 * 取得所有公司名称
	 * @DateTime 2018-12-10
	 */
	public function getCompanys($fields, $where = []) {
		return $this -> where($where) -> getField($fields);
	}

	public function _before_update(&$data, $options)
	{
		$data['updated_time'] = time();
	}

    /**
     * 搜索公司
     * @DateTime 2018-12-08T18:09:05+0800
     */
    public function searchCompany($where)
    {
        if (!isset($where['status'])) {
            $where['status'] = self::STATUS_ACTIVE;
        }
        return $this->table('company')->where($where)->select();
    }

    public function check($data, $args)
    {
        if ($data['company_name'] != $args['company_name'] or !password_verify($data['password'], $args['password'])) {
            return false;
        }
        return true;
    }

    public function login($user)
    {
        session('userinfo', json_encode($user));
        return session('userinfo');
    }

    /**
     * 检查账号是否被注册过，注册过则返回false，没注册则返回true
     * @param $account
     * @return bool
     */
    public function registerCheck($data)
    {
        $admin = $this->table('company')->where(['company_name' => $data['company_name'], 'status' => self::STATUS_ACTIVE])->find();
        if ($admin) {
            return false;
        }
        return true;
    }

    public function addCompany($data)
    {
        $done = M('company')->table('company')->add([
            'code' => $data['code'],
            'company_name' => $data['company_name'],
            'password' => $data['password'],
            'status' => self::STATUS_ACTIVE,
            'created_time' => time(),
            'updated_time' => time(),
        ]);
        if (!$done) {
            return false;
        }
        return $done;
    }
}