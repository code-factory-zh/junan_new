<?php

/**
 * @Dec    登录控制器
 * @Auther yangzhengyuan
 * @Date   2018/12/10
 */

namespace Manage\Controller;

use Manage\Model\AdminModel;

class AdminController extends CommonController
{

    private $user;

    // 不需要验证TOKEN
    protected static $token = 0;

    public function _initialize()
    {
        parent::_initialize();
        $this->user = new AdminModel();
    }

    public function index()
    {
        $this->_post();
    }

    /**
     * 用户登录
     * @param md5 ($verify)
     * @param $phone
     * @param $pwd
     */
    public function login()
    {
        if (IS_POST) {
            $post = I('post.');
            if (empty($post['account']) || empty($post['password'])) {
                $this -> e('用户名或密码不得为空！');
            }
            $this->ignore_token();
            $user = $this->user->getAdmin(['account' => $post['account']]);
            if (!($u = $this->user->check($post, $user))) {
                $this->e('用户名或密码不正确');
            }
            $token = $this->token_fetch($u);
            $this->save_token('token', 1);
            if (!$this->save_token($token, $u)) {
                $this->e('无法生成TOKEN');
            }
            $userinfo = json_decode($this->user->login($user)); // 记录用户登录情况
            $this->rel(['token' => $token, 'userinfo' => $userinfo])->e();
        }
        $this->display('Admin/login');
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        if (!is_null(session('userinfo'))) {
            session('userinfo', null);
        }
        header('Location:/manage/admin/login');
    }
}