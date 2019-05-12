<?php

/**
 * Question模块基类
 * @Auther Cuiruijun
 * @Date   2018/12/9
 */
namespace Manage\Controller;

use Common\Controller\BaseController;
use Manage\Model\CourseModel;
use Manage\Model\QuestionsModel;

class QuestionController extends CommonController
{

	private $question;
	private $course;

	// 针对HTTP接口的固定TOKEN
	CONST HTTP_TOKEN_N1 = '8FA02B017FCDE7836A6FDB5D00AC638F';

	protected $base_url = 'http://192.168.1.220';

	// 生成一个被组合好的JSON数据
	protected function postFetch(&$data)
	{
		$data['token'] = self::HTTP_TOKEN_N1;
		return $data;
	}

	public function _initialize()
	{
		parent::_initialize();

		$this->islogin();
		$this->question = new \Manage\Model\QuestionsModel;
		$this->course = new \Manage\Model\CourseModel;
	}

	/**
	 * 接入公司管理-列表
	 * @author cuiruijun
	 * @date   2018/12/08 下午10:20
	 * @url    manage/question
	 * @method get
	 *
	 * @return  array
	 */
	public function index()
	{
		//        $params = $this->_get($_GET);
		$params = I('get.');

		$page = I('page');

		$limit = pageLimit($page, 10);

		$list = $this->question->getAll2('*', 'is_deleted = 0', $limit);

		//将1,2,3,4转成ABCD
		//增加选项的匹配
		$answer_key_num = [
			1 => 'A',
			2 => 'B',
			3 => 'C',
			4 => 'D',
			5 => 'E',
			6 => 'F',
			7 => 'G',
		];

		//判断题只有两项
		$answer_judge_key_num = [
			1 => '正确',
			2 => '错误',
		];

		$courseList = $this->course->getList();
		$array = array_column($courseList, 'name', 'id');
		foreach ($list['list'] as &$val)
		{
			$val['course_id'] = $array[$val['course_id']];
			$option = json_decode($val['option'], true);
//			$val['option'] = implode('|', $option);

			if($val['type'] == 2){
				$answer = json_decode($val['answer'], true);
				$answer_tmp = [];
				foreach($answer as $a_k => $a_v){
					$answer_tmp[] = $answer_key_num[$a_v];
				}

				$val['answer'] = implode('', $answer_tmp);

			}elseif($val['type'] == 3){
				$val['answer'] = $answer_judge_key_num[$val['answer']];
			}else{
				$val['answer'] = $answer_key_num[$val['answer']];
			}

			if(in_array($val['type'], [1,2])){
				$option_tmp = [];
				foreach($option as $o_k => $o_v){
					$option_tmp[] = $answer_key_num[$o_k+1] . ':' . $o_v;
				}

				$val['option'] = implode('|', $option_tmp);
			}else{
				$val['option'] = implode('|', $option);
			}
		}

		$data['page'] = page($list['count'], $page);
		$this->assign(['data' => $list['list']]);
		$this->assign(['page' => $data['page']]);
		$this->display('Question/index');
	}

	/**
	 * 新增/修改题目
	 * @author cuiruijun
	 * @date   2018/12/8 下午21：03
	 * @url    manage/question/edit
	 * @method post
	 *
	 * @param  int    course_id 课程ID
	 * @param  int    type 类型
	 * @param  string title 标题
	 * @param  string answer 答案
	 *
	 * @return  array
	 */
	public function edit()
	{
		if (IS_POST)
		{
			//            $data = $this -> postFetch($_POST);
			//            $this -> _post($data, ['course_id', 'type', 'title', 'answer']);

			$data = I('post.');

			if ($data['type'] == 1 || $data['type'] == 3)
			{
				if (strlen($data['answer']) != 1)
				{
					$this->e('答案只能有一个');
				}
			}
			elseif ($data['type'] == 2)
			{
				if (!count($data['answer']))
				{
					$this->e('复选答案至少要有一个');
				}
				$data['answer'] = json_encode($data['answer']);
			}
			elseif ($data['type'] == 4)
			{
				if (strlen($data['answer']) == '')
				{
					$this->e('答案不能为空');
				}
			}

			if (!$data['course_id'])
			{
				$this->e('科目不能为空');
			}

			$data['created_time'] = time();
			$data['updated_time'] = time();
			$data['option'] = json_encode($data['option']);

			$Question = M('Questions');
			$final = $this->question->create($data);

			if (!empty($final['id']))
			{
				$record = $this->question->getOne('is_deleted = 0 AND id = ' . $final['id']);
				if (empty($record))
				{
					$this->e('记录为空');
				}

				unset($final['created_time']);
				$result = $this->question->save($final);
			}
			else
			{
				$result = $this->question->add($final);
			}

			if ($result)
			{
				$this->e();
			}
			else
			{
				$this->e('fail');
			}
		}

		//参数
		if (!empty(I('get.id')))
		{
			$exist = $this->question->getOne('id = ' . I('get.id'));
			$data['record'] = $exist;
			$data['type'] = $exist['type'];
			if ($exist['type'] < 3)
				$data['record']['option'] = json_decode($data['record']['option'], true);
			if ($exist['type'] == 2)
			{
				$data['record']['answer'] = json_decode($data['record']['answer'], true);
				$answer = [];
				foreach ($data['record']['answer'] as $v)
				{
					$answer[$v] = $v;
				}

				$option = [];
				foreach ($data['record']['option'] as $key => $value)
				{
					$tmp['value'] = $value;
					$tmp['answer'] = 1;
					if (isset($answer[$key + 1]))
					{
						$tmp['answer'] = 2;
					}
					$option[] = $tmp;
				}
				$data['record']['option'] = $option;
			}
		}
		else
		{
			$data['type'] = $_GET['type'];
		}

		//增加选项的匹配
		$answer_key_num = [
			1 => 'A',
			2 => 'B',
			3 => 'C',
			4 => 'D',
			5 => 'E',
			6 => 'F',
			7 => 'G',
		];

		$types = [
			1 => '单选',
			2 => '复选',
			3 => '判断',
			4 => '填空',
		];
		$data['type_name'] = $types[$data['type']];
		$data['answer_key_num'] = $answer_key_num;

		$data['course'] = $this->course->getList();
		$this->assign($data);
		$this->display('Question/edit');
	}

	/**
	 * 删除题目
	 * @author cuiruijun
	 * @date   2018/12/09 下午10:20
	 * @url    manage/question/del
	 * @method get
	 *
	 * @return  array
	 */
	public function del()
	{
		//        $this -> _get($p, ['id']);
		$p = I('post.');

		$record = $this->question->getOne('is_deleted = 0 AND id = ' . $p['id']);
		if (empty($record))
		{
			$this->el($record, '记录不存在');
		}

		//是否能够删除,判定条件:判断出题的时候，有没有随机到这道题。 如果没随机到，则可以直接删除。 如果有，还要根据exam_question_id去判断这套题有没有出考试结果，出了考试结果，不管过没过，都可以删除
		//删除
		$question_info = $this->question->isDelQuestion($p['id']);
		if ($question_info)
		{
			foreach ($question_info as $K => $v)
			{
				if ($v['account_id'] && ($v['question_id'] === null))
				{
					$this->e('有在使用这道题,不能删除');
				}
			}
		}

		$data = [
			'id' => $p['id'],
			'is_deleted' => 1,
		];
		$result = $this->question->save($data);
		if ($result)
		{
			$this->e();
		}
		else
		{
			$this->el($result, '删除失败');
		}
	}

	public function import()
	{
		$data['course'] = $this->course->getList();
		$this->assign($data);
		$this->display('Question/import');
	}

	public function download()
	{
		$file_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/')."/download/demo.xlsx";
		if(!file_exists($file_path)){
			echo $file_path . '地址不存在';
			die();
		}

		$file_name = "demo.xlsx";

		if(false !== ($fp=fopen($file_path,"r+"))){
			$file_size=filesize($file_path);
			//下载文件需要用到的头
			Header("Content-type: application/octet-stream");
			header("Content-Transfer-Encoding: binary");
			Header("Accept-Ranges: bytes");
			Header("Accept-Length:".$file_size);
			Header("Content-Disposition: attachment; filename=".$file_name);
			ob_clean();
			flush();
			$buffer=1024;
			$file_count=0;
			//向浏览器返回数据
			while(!feof($fp) && $file_count<$file_size){
				$file_con=fread($fp,$buffer);
				$file_count+=$buffer;
				echo $file_con;
			}
			fclose($fp);
		}else{
			echo '读取文件失败';
		}
	}

	/**
	 * 导入题库
	 * @author cuiruijun
	 * @date   2019/1/27 上午10:34
	 * @url    question/batch_add_questions
	 * @method post
	 *
	 * @param  int param
	 *
	 * @return  array
	 */
	public function batch_add_questions()
	{
		Vendor('PHPExcel.Classes.PHPExcel');
		Vendor('PHPExcel.Classes.PHPExcel.IOFactory');
		Vendor('PHPExcel.Classes.PHPExcel.Reader.Excel5');

//		$course_id = I('post.course_id');
//		if (!$course_id)
//		{
//			$this->e('请选择课程');
//		}

		$question_type = [
			'单选题' => 1,
			'复选题' => 2,
			'判断题' => 3,
		];

		if ($_FILES)
		{
			if(!move_uploaded_file($_FILES['file']['tmp_name'], rtrim($_SERVER['DOCUMENT_ROOT'], '/') .'/Uploads/file/' . $_FILES['file']['name'])){
				$this->e('文件上传失败');
			}
			$path = 'Uploads/file/' . $_FILES['file']['name'];
			$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

			if ($extension == 'xlsx')
			{
				$objReader = \PHPExcel_IOFactory::createReader('Excel2007');
			}
			else if ($extension == 'xls')
			{
				$objReader = \PHPExcel_IOFactory::createReader('Excel5');
			}

			$objPHPExcel = $objReader->load(rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $path);//$file_url即Excel文件的路径
			//循环读取excel文件,读取一条,插入一条
			$couse_title_id_arr = $this->get_course_title_id_arr();

			$radio_res = $this->radio($objPHPExcel, $couse_title_id_arr);
			$checkbox_res = $this->checkbox($objPHPExcel, $couse_title_id_arr);
			$judge_res = $this->judge($objPHPExcel, $couse_title_id_arr);


			if($radio_res !== true){
				//判断是否是全部成功
				$err_msg = '单选题导入失败的题目为第' . trim(implode(',', $radio_res), ',') . '行' . PHP_EOL;
			}


			if($checkbox_res !== true){
				//判断是否是全部成功
				$err_msg .= '复选题导入失败的题目为第' . trim(implode(',', $checkbox_res), ',') . '行' . PHP_EOL;
			}

			if($judge_res !== true){
				//判断是否是全部成功
				$err_msg .= '判断题导入失败的题目为第' . trim(implode(',', $judge_res), ',') . '行';
			}

			unlink($path);
			if($err_msg){
				$this->e($err_msg);
			}else{
				$this->e();
			}
		}
		else
		{
			$this->e('请上传文件');
		}

		$this->e('文件上传失败');
	}

	//判断题
	public function judge($objPHPExcel, $couse_title_id_arr)
	{
		$sheet = $objPHPExcel->getSheet(2);//获取第三个工作表
		$highestRow = $sheet->getHighestRow();//取得总行数
		$highestColumn = $sheet->getHighestColumn(); //取得总列数
		//			$data = array();
		$answer_letter_num_arr = [
			'正确' => 1,
			'错误' => 2,
		];
		//循环读取excel文件,读取一条,插入一条
		for ($j = 3; $j <= $highestRow; $j++)
		{
			//从第一行开始读取数据
			$str = '';
			$d = [];
			for ($k = 'A'; $k <= $highestColumn; $k++)
			{ //从A列读取数据
				//这种方法简单，但有不妥，以'\\'合并为数组，再分割\为字段值插入到数据库,实测在excel中，如果某单元格的值包含了\\导入的数据会为空
				$str .= $sheet->getCell("$k$j")->getValue() . '\\';//读取单元格
			}
			//explode:函数把字符串分割为数组。
			$strs = explode("\\", $str);

			// 试题名称 课程名称 答错的解释 答案 选项写死为正确、错误
			$d["title"] = $strs[0];
			$d["explain"] = $strs[2];
			$d["type"] = 3;
			$d["answer"] = $answer_letter_num_arr[$strs[3]];

			//根据名称查course_id
			$d["course_id"] = $couse_title_id_arr[$strs[1]];
			$d["source"] = 2;
			$options = [
				'正确',
				'错误'
			];
			$d["option"] = json_encode($options);
			//				array_push($data,$d);

			$err = [];
			if (!$d['title'])
			{
				//题目不能为空
				$err[] = [
					'err_type' => 1,
					'err_pos' => $j,
				];
				$pos[] = $j;
			}

			if (!in_array(strtoupper($strs[3]), array_keys($answer_letter_num_arr)))
			{
				//答案不在正确错误范围内
				$err[] = [
					'err_type' => 2,
					'err_pos' => $j,
				];
				$pos[] = $j;
			}

			//先一条一条的插入
			$result = $this->question->add($d);
			if (!$result)
			{
				$err[] = [
					'err_type' => 3,
					'err_pos' => $j,
				];

				$pos[] = $j;
			}
		}

		if(!$err)
		{
			return true;
		}
		else
		{
			return $pos;
		}
	}

	//单选题
	public function radio($objPHPExcel, $couse_title_id_arr)
	{
		$sheet = $objPHPExcel->getSheet(0);//获取第一个工作表
		$highestRow = $sheet->getHighestRow();//取得总行数
		$highestColumn = $sheet->getHighestColumn(); //取得总列数

		//选项和存入数据库中对应关系
		$answer_letter_num_arr = [
			'A' => 1,
			'B' => 2,
			'C' => 3,
			'D' => 4,
		];
		//循环读取excel文件,读取一条,插入一条
		for ($j = 3; $j <= $highestRow; $j++)
		{
			//从第一行开始读取数据
			$str = '';
			$d = [];
			for ($k = 'A'; $k <= $highestColumn; $k++)
			{ //从A列读取数据
				//这种方法简单，但有不妥，以'\\'合并为数组，再分割\为字段值插入到数据库,实测在excel中，如果某单元格的值包含了\\导入的数据会为空
				$str .= $sheet->getCell("$k$j")->getValue() . '\\';//读取单元格
			}
			//explode:函数把字符串分割为数组。
			$strs = explode("\\", $str);

			// 试题名称 课程名称 答错的解释 答案 选项a b c d
			$d["title"] = $strs[0];
			$d["explain"] = $strs[2];
			$d["type"] = 1;
			$d["answer"] = $answer_letter_num_arr[$strs[3]];

			//根据名称查course_id
			$d["course_id"] = $couse_title_id_arr[$strs[1]];
			$d["source"] = 2;
			$options = [
				$strs[4],
				$strs[5],
				$strs[6],
				$strs[7],
			];
			$d["option"] = json_encode($options);
			//				array_push($data,$d);

			$err = [];
			if (!$d['title'])
			{
//				$this->e('第' . $j . '行数据不符合输入规范!试题名称不能为空');
				$err[] = [
					'err_type' => 1,
					'err_pos' => $j,
				];
				$pos[] = $j;
			}

			if (!in_array(strtoupper($strs[3]), array_keys($answer_letter_num_arr)))
			{
				//答案不在abcd范围内
				$err[] = [
					'err_type' => 2,
					'err_pos' => $j,
				];
				$pos[] = $j;
			}

			//先一条一条的插入
			$result = $this->question->add($d);
			if (!$result)
			{
				$err[] = [
					'err_type' => 3,
					'err_pos' => $j,
				];

				$pos[] = $j;
			}
		}

		if(!$err)
		{
			return true;
		}
		else
		{
			return $pos;
		}
	}

	//多选题
	public function checkbox($objPHPExcel, $couse_title_id_arr)
	{
		$sheet = $objPHPExcel->getSheet(1);//获取第二个工作表
		$highestRow = $sheet->getHighestRow();//取得总行数
		$highestColumn = $sheet->getHighestColumn(); //取得总列数
		//			$data = array();
		$answer_letter_num_arr = [
			'A' => 1,
			'B' => 2,
			'C' => 3,
			'D' => 4,
			'E' => 5,
			'F' => 6,
			'G' => 7,
		];
		//循环读取excel文件,读取一条,插入一条
		for ($j = 3; $j <= $highestRow; $j++)
		{
			//从第一行开始读取数据
			$str = '';
			$d = [];
			for ($k = 'A'; $k <= $highestColumn; $k++)
			{ //从A列读取数据
				//这种方法简单，但有不妥，以'\\'合并为数组，再分割\为字段值插入到数据库,实测在excel中，如果某单元格的值包含了\\导入的数据会为空
				$str .= $sheet->getCell("$k$j")->getValue() . '\\';//读取单元格
			}
			//explode:函数把字符串分割为数组。
			$strs = explode("\\", $str);

			// 试题名称 课程名称 答错的解释 答案 选项 ABCDEFG
			$d["title"] = $strs[0];
			$d["explain"] = $strs[2];
			$d["type"] = 2;

			//将答案json划
			$answer_json_arr = explode(',', $strs[3]);
			$answer_arr = [];
			foreach($answer_json_arr as $answer_json_k => $answer_json_v){
				$answer_arr[] = (string)$answer_letter_num_arr[$answer_json_v];
			}
			$d["answer"] = json_encode($answer_arr);

			//根据名称查course_id
			$d["course_id"] = $couse_title_id_arr[$strs[1]];
			$d["source"] = 2;
			$options = [
				$strs[4],
				$strs[5],
				$strs[6],
				$strs[7],
				$strs[8],
				$strs[9],
				$strs[10],
			];

			$options = array_filter($options);
			$d["option"] = json_encode($options);

			$err = [];
			if (!$d['title'])
			{
				//题目不能为空
				$err[] = [
					'err_type' => 1,
					'err_pos' => $j,
				];
				$pos[] = $j;
			}

//			if (!in_array(strtoupper($strs[3]), array_keys($answer_letter_num_arr)))
//			{
//				//答案不在正确错误范围内
//				$err[] = [
//					'err_type' => 2,
//					'err_pos' => $j,
//				];
//				$pos[] = $j;
//			}

			//先一条一条的插入
			$result = $this->question->add($d);
			if (!$result)
			{
				$err[] = [
					'err_type' => 3,
					'err_pos' => $j,
				];

				$pos[] = $j;
			}
		}

		if(!$err)
		{
			return true;
		}
		else
		{
			return $pos;
		}
	}

	/**
	 * 导入题库
	 * @author cuiruijun
	 * @date   2019/1/27 上午10:34
	 * @url    question/batch_add_questions
	 * @method post
	 *
	 * @param  int param
	 *
	 * @return  array
	 */
	public function batch_add_questions_1()
	{
		Vendor('PHPExcel.Classes.PHPExcel');
		Vendor('PHPExcel.Classes.PHPExcel.IOFactory');
		Vendor('PHPExcel.Classes.PHPExcel.Reader.Excel5');

		$course_id = I('post.course_id');
		if (!$course_id)
		{
			$this->e('请选择课程');
		}

		$question_type = [
			'单选题' => 1,
			'复选题' => 2,
			'判断题' => 3,
		];

		if ($_FILES)
		{
			move_uploaded_file($_FILES['file']['tmp_name'], 'Uploads/file/' . $_FILES['file']['name']);
			$path = 'Uploads/file/' . $_FILES['file']['name'];
			$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

			if ($extension == 'xlsx')
			{
				$objReader = \PHPExcel_IOFactory::createReader('Excel2007');
			}
			else if ($extension == 'xls')
			{
				$objReader = \PHPExcel_IOFactory::createReader('Excel5');
			}

			$objPHPExcel = $objReader->load($path);//$file_url即Excel文件的路径
			$sheet = $objPHPExcel->getSheet(0);//获取第一个工作表
			$highestRow = $sheet->getHighestRow();//取得总行数
			$highestColumn = $sheet->getHighestColumn(); //取得总列数
			//			$data = array();
			//循环读取excel文件,读取一条,插入一条
			//开启事务
			$this->question->startTrans();

			for ($j = 2; $j <= $highestRow; $j++)
			{
				//从第一行开始读取数据
				$str = '';
				for ($k = 'A'; $k <= $highestColumn; $k++)
				{ //从A列读取数据
					//这种方法简单，但有不妥，以'\\'合并为数组，再分割\为字段值插入到数据库,实测在excel中，如果某单元格的值包含了\\导入的数据会为空
					$str .= $objPHPExcel->getActiveSheet()->getCell("$k$j")->getValue() . '\\';//读取单元格
				}
				//explode:函数把字符串分割为数组。
				$strs = explode("\\", $str);
				$d["title"] = $strs[0];
				$d["explain"] = $strs[1];
				$d["type"] = $question_type[$strs[2]];
				$options = explode('||', $strs[3]);
				$d["option"] = json_encode($options);
				$d["answer"] = str_replace('，', ',', $strs[4]);
				$d["course_id"] = $course_id;
				$d["source"] = 2;
				//				array_push($data,$d);

				if (!$d['title'])
				{
					$this->e('第' . $j . '行数据不符合输入规范!试题名称不能为空');
				}

				if (!$d['type'])
				{
					$this->e('第' . $j . '行数据不符合输入规范!试题类型有误');
				}

				if (count($options) < 2)
				{
					$this->e('第' . $j . '行数据不符合输入规范!选项必须大于两项');
				}

				$answer_arr = explode(',', $d['answer']);
				//判断是否符合规则
				$max_answer_value = max($answer_arr);
				if ($max_answer_value > count($options) || count($answer_arr) > count($options))
				{
					$this->e('第' . $j . '行数据不符合输入规范!请检查题目数和答案数');
				}

				switch ($d['type'])
				{
					case 1:
					case 3:
						if (count($answer_arr) > 1)
						{
							$this->e('第' . $j . '行数据不符合输入规范!答案只能有1个');
						}
					break;

					case 2:
						if (count($answer_arr) < 2)
						{
							$this->e('第' . $j . '行数据不符合输入规范!复选答案不能小于1个');
						}
					break;
				}

				//如果是复选,答案json_encode下
				if ($d['type'] == 2)
				{
					$d['answer'] = json_encode($answer_arr);
				}

				//				array_push($data,$d);
				//先一条一条的插入
				//开启事务
				$result = $this->question->add($d);
				if (!$result)
				{
					//如果有提交不成功的,就回滚
					$this->question->rollback();
					$this->e('文件解析失败,出错行数为:' . $j);
				}
			}

			$this->question->commit();
			unlink($path);
			$this->e();
		}
		else
		{
			$this->e('请上传文件');
		}

		$this->e('文件上传失败');
	}

	public function get_course_title_id_arr(){
		$course_model = new \Manage\Model\CourseModel;

		$courses = $course_model->getList();

		foreach($courses as $k => $v)
		{
			$result[$v['name']] = $v['id'];
		}

		return $result;
	}

}
