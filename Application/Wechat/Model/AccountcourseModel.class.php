<?php

/**
 * @Dec    课程模型
 * @Auther QiuXiangCheng
 * @Date   2019/01/17
 */
namespace Wechat\Model;
class AccountcourseModel extends CommonModel {

	protected $tableName = 'company_account_course_chapter';

    public function _initialize() {

        parent::_initialize();
    }

    /**
     * 根据条件取章节数据
     * @Author   邱湘城
     * @DateTime 2019-01-18T00:07:44+0800
     */
    public function getList($where, $fields = '*') {

        return $this -> where($where) -> select();
    }


    /**
     * 取主页课程列表数据
     * @Author   邱湘城
     * @DateTime 2019-01-18T00:57:00+0800
     */
    public function getListCourses($where) {

        // 取通用课ID
        $ids = $this -> table('course') -> where(['type' => 1]) -> getField('id', 100);
        $ids = implode(',', $ids);

        $sql = "SELECT cac.id, cac.course_id, c.name, cac.is_pass_exam, 
                (SELECT COUNT(*) FROM course_detail cd WHERE cd.course_id = cac.course_id OR cd.course_id IN ({$ids})) total_chapter,
                (SELECT COUNT(*) FROM company_account_course_chapter cacc WHERE cacc.status = 0 AND (cacc.course_id = cac.course_id OR cacc.course_id IN({$ids})) and cacc.account_id = cac.account_id) studied
                FROM company_account_course cac
                JOIN course c ON c.id = cac.course_id
                WHERE {$where}
                GROUP BY cac.course_id";

        return $this -> query($sql);
    }
}