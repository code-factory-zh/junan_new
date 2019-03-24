<?php

/**
 * @Dec    Acount模块
 * @Auther QiuXiangCheng
 * @Date   2018/12/08
 */

namespace Manage\Model;

use Common\Model\BaseModel;

class AdminModel extends BaseModel
{
    const STATUS_DISABLE = 1;
    const STATUS_ACTIVE = 0;
    protected $tableName = 'admin';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 取得账号信息
     * @DateTime 2018-12-08T18:09:05+0800
     */
    public function getAdmin($where)
    {
        if (!isset($where['status'])) {
            $where['status'] = self::STATUS_ACTIVE;
        }
        return $this->table('admin')->where($where)->find();
    }

    public function check($data, $args)
    {
        if ($data['account'] != $args['account'] or !password_verify($data['password'], $args['password'])) {
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
    public function registerCompanyCheck($companyName)
    {
        $company = $this->table('company')->where(['company_name' => $companyName, 'status' => self::STATUS_ACTIVE])->find();
        if ($company) {
            return false;
        }
        return true;
    }

    /**
     * 检查账号是否被注册过，注册过则返回false，没注册则返回true
     * @param $account
     * @return bool
     */
    public function registerCheck($account)
    {
        $admin = $this->table('admin')->where(['account' => $account, 'status' => self::STATUS_ACTIVE])->find();
        if ($admin) {
            return false;
        }
        return true;
    }

    public function addUser($data)
    {
        $done = M('admin')->table('admin')->add([
            'account' => $data['account'],
            'password' => $data['password'],
            'created_time' => time(),
            'updated_time' => time(),
        ]);
        if (!$done) {
            return false;
        }
        return $done;
    }
}