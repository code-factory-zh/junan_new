<?php

/**
 * 章节模块
 * @Auther cuiruijun
 * @Date 2018/12/11
 */
namespace Manage\Controller;
use Common\Controller\BaseController;

class CourseDetailController extends CommonController
{
    private $courseDetail;
    private $course;
    private $company_account;

    public function _initialize() {

        parent::_initialize();
		$this -> islogin();
        $this -> courseDetail = new \Manage\Model\CourseDetailModel;
        $this -> course = new \Manage\Model\CourseModel;
		$this->company_account = new \Wechat\Model\CompanyAccountModel;
    }

    /**
     * 课程章节-列表
     * @DateTime 2018-12-08T17:58:00+0800
     */
    public function index()
    {
		$id = I('get.course_id');

        $chapters = $this -> courseDetail -> getChapter('course_id = ' . $id . ' and is_deleted = 0', 'id,chapter,sort');

        $this -> assign(['data' => $chapters, 'id' => $id]);
        $this -> display('Course_detail/index');
    }

    /**
     * 添加章节
     * @author cuirj
     * @date   2018/12/11 下午11:59
     * @url    manage/course_detail/edit
     * @method post
     *
     * @param string chapter 标题
     * @param int type 1文本框 2ppt 3 视频
     * @param int course_id
     * @param int sort 排序
     * @param string content ppt或者视频地址
     * @param string detail 文本内容
     * @return  array
     */
    public function edit(){
        if (IS_GET) {
//            $this -> _get($a, ['course_id']);
            $data['course_id'] = I('get.course_id');
            $id = I('get.id');
            $this -> assign('courseid', $data['course_id']);
            $this -> assign('id', $id);
        }

        if (IS_POST) {
//            $this -> _post($p, ['chapter', 'type', 'course_id', 'sort', 'course_id']);
			$p = I('post.');

            if ($p['type'] != 1) {
                if (empty($p['content'])) {
                    $this->e('请上传文件');
                }
                $p['detail'] = '';

                $ext = substr(strrchr($p['content'], '.'), 1);

                if ($p['type'] == 2 && !in_array($ext, ['ppt', 'pptx'])) {
                    $this -> e('文件必须是PPT');
                } elseif ($p['type'] == 3 && !in_array($ext, ['mp4', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'])) {
                    $this->e('文件必须是视频');
                }
            }

            $id = I('post.id');
            if (!empty($id)) {
                $exist = $this -> courseDetail -> getDetail('sort = ' . $p['sort'] . ' and course_id = ' . $p['course_id'] . ' and id != ' . $id);
                if ($exist) {
                    $this -> e('章节[' . $p['sort'] . ']已存在');
                }

                $p['updated_time'] = time();
                if (!$this -> courseDetail -> updateData('id = ' . $id, $p)) {
                    $this -> e('失败');
                }
                $this -> e();
            } else {
                //查询章节是否已存在
                $exist = $this -> courseDetail -> getDetail('sort = ' . $p['sort'] . ' and course_id = ' . $p['course_id']);
                if (!empty($exist)) {
                    $this -> e('章节[' . $p['sort'] . ']已存在');
                }

                $p['created_time'] = time();
                $p['updated_time'] = time();
                if (!$this -> courseDetail -> add($p)) {
                    $this -> e('失败');
                }
            }

            $this -> e();
        }

        if (!empty($id)) {
            $data['record'] = $this -> courseDetail -> getDetail('id = ' . $id);
            $data['course_id'] = $data['record']['course_id'];
        }

        $this -> assign($data);
        $this->display('Course_detail/edit');
    }


    /**
     * 预览
     * @Author   邱湘城
     * @DateTime 2019-01-27T15:02:57+0800
     */
    public function preview() {

        $this -> ignore_token() -> _post($p, ['course_id']);
        $this -> isint(['course_id']);

        $data = [
            'company_id'   => 1,
            'account_id'   => 1,
            'status'       => 0,
            'course_id'    => $p['course_id'],
            'created_time' => time(),
            'updated_time' => time(),
        ];

        // 新增记录
        $done = M('company_account_course') -> add($data);
        if (!$done) {
            $this -> e('新增预览失败，可能您已经添加过预览，请登录小程序查看！');
        }
        $this -> e(0, '新增预览完成，请在小程序查看。');
    }

	/**
	 * webuploader 上传文件
	 */
	public function upload(){
		// 根据自己的业务调整上传路径、允许的格式、文件大小
		$p = I('post.');
        $ext = substr(strrchr($p['name'], '.'), 1);

        if (in_array($ext, ['ppt', 'pptx'])) {
            $type = 2;
            $dir = 'file';
        } elseif (in_array($ext, ['mp4', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'])) {
            $type = 3;
            $dir = 'media';
        } else {
            $this -> e('上传类型必须是PPT或者视频文件');
        }

        $path = $dir .'/';
		if (!file_exists($path))
		{
			mkdir($path, 0777, true);
		}

        ajaxUpload($path, $dir, $type);
	}

	/**
	 * 删除
	 * @author cuiruijun
	 * @date   2019/1/27 下午1:39
	 * @url    coursedetail/del
	 * @method post
	 *
	 * @param  int param
	 * @return  array
	 */
	public function del(){
		if (!empty(I('post.id'))){
			$data = [
				'is_deleted' => 1,
				'id' =>  I('post.id')
			];
			//1.该课程是否有有人购买且学习进度还有效。
			//2.该课程是否有人购买，在学习进度无效的时候是否考试通过了，如果没通过也不给删除。
			$is_not_pass_list = $this->company_account->getRecord(['course_id' => I('post.course_id'), 'is_pass_exam' => 0]);

			if($is_not_pass_list){
				$this->e('此课程正在被使用,不能被删除');
			}

			$result = $this->courseDetail->save($data);
			if($result){
				$this->e();
			}else{
				$this->e('删除失败');
			}
		}
	}
}