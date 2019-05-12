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
    private $job;
    private $company;
    private $account;

    // 不需要验证TOKEN
    protected static $token = 0;

    public function _initialize()
    {

        parent::_initialize();
        $this->islogin();
        $this->company = new \Manage\Model\CompanyModel;
		$this->job = new \Manage\Model\JobModel;
		$this->account = new \Manage\Model\AccountModel;
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
    	$params = I('get.');
		$industry = $params['type'];
		$address = $params['address'];
		$province = $params['province'];
		$city = $params['city'];
		$county = $params['county'];

		if($industry){
			$where['industry'] = $industry;
		}

		if($province && $province != '-'){
			$where['province'] = $province;
		}

		if($city && $city != '-'){
			$where['city'] = $city;
		}

		if($county && $county != '-'){
			$where['county'] = $county;
		}

		if($address){
			$where['address'] = ['like', '%' . $address . '%'];
		}

        $page = I('page');

        $limit = pageLimit($page);
        $companys = $this->company->where($where)->getCompanys('id,code,company_name,created_time,status,credit_code,industry,province,city,address,active_time', $where, $limit);

		//取行业类型
		$data['industry'] = [
			1 => '冶金行业',
			2 => '有色行业',
			3 => '建材行业',
			4 => '机械行业',
			5 => '轻工行业',
			6 => '纺织行业',
			7 => '烟草行业',
		];

		$data['cond'] = [
			'type' => $industry,
			'address' => $address,
			'province' => $province,
			'city' => $city,
			'county' => $county,
			'current_time' => time(),
		];

        $data['page'] = page($companys['count'], $page);
        // pr($data['page']);
        $data['list'] = $companys['list'];
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

	/**
	 * 账户有效期设置页面
	 * @author cuirj
	 * @date   2019/4/16 上午1:26
	 *
	 * @param  int id
	 * @return  array
	 */
	public function active_time()
	{
		//判断当前传的参数和数据库中是否相同,如果相同则报错
		$where['id'] = I('get.id');
		$result = $this->company->getOne($where);

		$data['list'] = $result;
		$this->assign($data);
		$this->display();
	}

	/**
	 * 账户有效期设置页面
	 * @author cuirj
	 * @date   2019/4/16 上午1:26
	 *
	 * @param  int id
	 * @return  array
	 */
	public function active_time_ajax()
	{
		$data = I('post.');
		$data['active_time'] = strtotime($data['active_time']);

		//修改
		if($result = $this->company->save($data)){
			$this->e();
		}else{
			$this->e('修改失败');
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
	 * 考生列表
	 * @author cuirj
	 * @date   2019/4/16 上午2:06
	 * @method get
	 *
	 * @param  int param
	 * @return  array
	 */
    public function account_list(){
    	$company_id = I('get.company_id');
		$data = [];
//		$jobs = $this -> job -> getJobs('id, name');
		$list = $this -> account -> getAccount(['a.company_id' => $company_id]);

		$cour = [];
		$courses = $this -> account -> getCourses();
		if (count($courses)) {
			foreach ($courses as $k => $v) {
				if (!isset($cour[$v['account_id']])){
					$cour[$v['account_id']] = $v['course_name'];
				} else {
					$cour[$v['account_id']] .= '，' . $v['course_name'];
				}
			}
		}

		if (count($list)) {
			foreach ($list as &$items) {
				$items['course_name'] = '-';
				$items['job_name'] = '-';
				if (isset($jobs[$items['job_id']])) {
					$items['job_name'] = $jobs[$items['job_id']];
				}
				if (isset($cour[$items['account_id']])) {
					$items['course_name'] = $cour[$items['account_id']];
				}
				!empty($items['join_date']) && $items['join_date'] = date('Y-m-d', $items['join_date']);
			}
		}

		$data['list'] = $list;
		$this -> assign($data);
		$this -> display('Account/list');
	}

	/**
	 * 导出数据
	 * @author cuirj
	 * @date   2019/5/10 下午3:43
	 * @method get
	 *
	 * @param  int param
	 */
	public function import_data(){
		$params = I('get.');
		$industry = $params['type'];
		$address = $params['address'];
		$province = $params['province'];
		$city = $params['city'];
		$county = $params['county'];

		if($industry){
			$where['industry'] = $industry;
		}

		if($province && $province != '-'){
			$where['province'] = $province;
		}

		if($city && $city != '-'){
			$where['city'] = $city;
		}

		if($county && $county != '-'){
			$where['county'] = $county;
		}

		if($address){
			$where['address'] = ['like', '%' . $address . '%'];
		}

		$companys = $this->company->where($where)->getCompanys('id,code,company_name,created_time,status,credit_code,industry,province,city,address,active_time', $where, '');

		//取行业类型
		$industry = [
			1 => '冶金行业',
			2 => '有色行业',
			3 => '建材行业',
			4 => '机械行业',
			5 => '轻工行业',
			6 => '纺织行业',
			7 => '烟草行业',
		];

		$company_export = [];
		foreach($companys['list'] as $k => $v){
			$company_export[] = [
				'id' => $v['id'],
				'code' => $v['code'],
				'company_name' => $v['company_name'],
				'created_time' => date('Y-m-d H:i:s', $v['created_time']),
				'status' => $v['status'] ? '禁用' : '激活',
				'credit_code' => $v['credit_code'],
				'industry' => $industry[$v['credit_code']],
				'address' => $v['province'] . $v['city'] . $v['address'],
				'active_time' => date('Y-m-d H:i:s', $v['active_time']),
			];
		}

		$xlsCell = array(
			array('id', 'ID'),
			array('code', '企业账号'),
			array('company_name', '真实姓名'),
			array('created_time', '添加时间'),
			array('status', '激活状态'),
			array('credit_code', '企业信用代码'),
			array('industry', '行业类型'),
			array('address', '地址'),
			array('active_time', '账户有效期'),
		);

		$this->exportExcel('接入公司',$xlsCell,$company_export);
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
            $user = $this->company->getCompany(['code' => $post['company_name']]);
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