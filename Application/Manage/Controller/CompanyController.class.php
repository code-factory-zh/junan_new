<?php

/**
 * 子帐户模块
 * @Auther cuiruijun
 * @Date 2018/12/10
 */

namespace Manage\Controller;

use Common\Controller\BaseController;

class CompanyController extends CommonController
{


    private $company;

    // 不需要验证TOKEN
    protected static $token = 0;

    public function _initialize()
    {

        parent::_initialize();
        $this->islogin();
        $this->company = new \Manage\Model\CompanyModel;
    }


    /**
     * 接入公司管理-列表
     * @author cuiruijun
     * @date   2018/12/10 下午11:59
     * @url    manage/company
     * @method get
     *
     * @return  array
     */
    public function index()
    {
        $companys = $this->company->getCompanys('id,code,company_name,created_time,status');

        $data['list'] = $companys;
        $this->assign($data);
        $this->display();
    }

    /**
     * 开启/禁止公司账号
     * @author cuiruijun
     * @date   2018/12/10 下午11:59
     * @url    manage/company/changeStatus
     * @method post
     *
     * @param  int status 1-启用,0-禁止
     * @return  array
     */
    public function changeStatus()
    {
        //判断当前传的参数和数据库中是否相同,如果相同则报错
        $where['id'] = I('post.id');
        $data['status'] = I('post.status');
        $result = $this->company->updateData($where, $data);
        if ($result) {
            $this->e(0, '修改成功');
        } else {
            $this->el($result, '修改失败,请重试');
        }
    }

    public function search()
    {
        $post = I('post.');
        $this->ignore_token();
        $companyList = $this->company->searchCompany(['company_name' => $post['company_name']]);
        $this->rel(['company_list' => $companyList])->e();
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
            $this->ignore_token();
            $user = $this->company->getCompany(['company_name' => $post['company_name']]);
            if (!($u = $this->company->check($post, $user))) {
                $this->e('密码不正确或公司被禁用！');
            }
            $token = $this->token_fetch($u);
            $this->save_token('token', 1);
            if (!$this->save_token($token, $u)) {
                $this->e('无法生成TOKEN');
            }
            $this->company->login($user); // 记录用户登录情况
            $this->rel(['token' => $token])->e();
        }
        $this->display('Company/login');
    }

    /**
     * 验证用户是否已登录
     * @param $token
     */
    public function lc()
    {
        $this->ignore_bsid()->_post($p);
        if (!($u = $this->getUserByToken($p['token']))) {
            $this->e('Token Invalid!');
        }
        $this->e(0, 'on-line');
    }

    /*********************** 注册功能 BEGIN *************************/
    // 注册新用户
    // 最后一步
    public function register()
    {
        if (IS_POST) {
            $post = I('post.');
            $this->ignore_token();
            if (!$this->company->registerCheck($post)) {
                $this->e('此公司已被注册');
            }
            $this->lenCheck('password', 6, 16);
            if ($post['password'] != $post['verify_password']) {
                $this->e('两次输入的密码不一样');
            }
            if (!preg_match("/^[\w\d\_]+$/si", $post['password'])) {
                $this->e('密码不规范');
            }
            $post['password'] = $this->_encrypt($post['password']);
            // 插入数据库
            if (!($id = $this->company->addCompany($post))) {
                $this->e('失败,未知错误');
            }
            $this->e();
        }
        $this->display('Company/register');
    }
    /*********************** 注册功能 END *************************/

    /**
     * 退出登录
     */
    public function logout()
    {
        if (!is_null(session('userinfo'))) {
            session('userinfo', null);
        }
        header('Location:/' . self::login_page_company);
    }
}