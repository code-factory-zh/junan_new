<?php

	/**
	 * 操作数据库简单类 支持多数据库操作
	 * 单例模式实例化
	 * @Auther QiuXiangCheng
	 * @Date 2017/04/08
	 */

	/**
	 * 多数据库连接示例
	 $db2 = [
			'host' => '47.88.139.77',
			'db_name' => 'nongbo',
			'user_name' => 'root',
			'user_pwd' => 'nongbo_tech_2017!',
			'charset' => 'utf8',
			'port' => 3306
		];
	$sql -> open($db2);
	$result2 = $sql -> query('select name from nb_user where id = 6');*/

	/**
	 * --- 当调用一次open()函数时，下一次无需再调用，本类将继续保持上一次的连接 ---
	 * 工作原理：
	 * 在调用mysql::getInstance()之时，将对本类进行实例化，且在构造函数中调用一次open函数以打开mysql连接
	 * 在构造函数中默认使用$conf变量中的数据库配置信息进行mysql连接:
	 * 示例：
	 * $db = mysql::getInstance() // 此处将打开一个mysql连接 当再次调用时将继续使用该连接信息
	 * 语句一：$db -> query('select * from user');
	 * 语句二：$db -> open() -> query('select * from user');
	 * 上两组语句的工作原理完全一致，都不会因为调用了open函数而多次打开数据库，但例外情况是对open转输参数，如：
	 * $db -> open($db1) -> query('select * from user')
	 * $db -> open($db2) -> query('select * from user')
	 * $db -> open($db2) -> query('select * from user')
	 * $db -> open($db3) -> query('select * from user')
	 * $db -> open($db3) -> query('select * from user')
	 * $db -> open($db3) -> query('select * from user')
	 * 以上六组语句将进行六次打开数据库的操作！因为调用open函时传输了参数而致使该方法认为需要打开新的数据库连接
	 */

	// 取得数据结构
	/*$db2 = [
		'host' => '47.88.139.77',
		'db_name' => 'nongbo',
		'user_name' => 'root',
		'user_pwd' => 'nongbo_tech_2017!',
		'charset' => 'utf8',
		'port' => 3306
	];
	echo '<pre>';
	$db = mysql::getInstance();
	$result1 = $db -> open() -> query('SELECT u.* FROM nb_user u WHERE u.id = 6');
	$result2 = $db -> open() -> query('SELECT u.* FROM nb_user u WHERE u.id = 6');
	$result3 = $db -> open() -> query('SELECT u.* FROM nb_user u WHERE u.id = 6');
	$result4 = $db -> open($db2) -> query('SELECT u.* FROM nb_user u WHERE u.id = 7');
	$result5 = $db -> open() -> query('UPDATE nb_user SET name = "88888888" WHERE id ');
	print_r($result4);*/

	namespace Ali\Msg;
	class Db{

		/**
		 * @var $db
		 * 数据库资源
		 */
		private $db;

		/**
		 * @var $sql
		 * 当前sql语句
		 */
		public $sql;

		/**
		 * @var $result
		 * 数据库结果集
		 */
		private $result;

		/**
		 * @var $opened
		 * 用于判断是否已经打开过数据库
		 * 如果该变量的值不为0 则不再打开数据库 而是调用上一次的连接资源
		 */
		private $opened = 0;

		/**
		 * @var $getInstance
		 * 单例模式标记
		 */
		private static $getInstance = false;

		/**
		 * @var $conf
		 * 数据库默认连接信息
		 */
		private $conf;
		/*public $conf = [
			'host' => '127.0.0.1',
			'db_name' => 'nongbo',
			'user_name' => 'root',
			'user_pwd' => 'mysql_linux',
			'charset' => 'utf8',
			'table_prifix' => 'nb_',
			'port' => 3306
		];*/

		/**
		 * 实例化本类方法
		 */
		public static function getInstance(){

			if(!(self::$getInstance instanceof self)){
				self::$getInstance = new self;
			}
			return self::$getInstance;
		}

		/**
		 * 构造函数 打开数据库
		 * 将通过open方法连接至数据库
		 */
		private function __construct(){

			$this -> open();
		}

		/**
		 * 打开数据库链接
		 * 如果传输了参数，则在打开前设置默认数据库信息
		 * 否则将取默认的$this -> conf中的数据库信息
		 * @param $dbConf
		 * @return $this
		 */
		public function open($dbConf = 0){

			if($dbConf){
				!isset($dbConf['port']) && $dbConf = 3306;
				$this -> conf = $dbConf;
				/*
				 * 如果已经打开过数据库则直接返回上一次的类实例
				 * 或者如果没有默认的数据库信息 则先不打开数据库
				 */
			}else if(!$this -> conf || $this -> opened){
 				return $this;
			}
			//echo '打开一次数据库连接.<br />';
			$this -> db = new \mysqli(
				$this -> conf['host'],
				$this -> conf['user_name'],
				$this -> conf['user_pwd'],
				$this -> conf['db_name'],
				$this -> conf['port']
				);
			if(mysqli_connect_errno()){
				exit('无法连接到MYSQL！');
			}
			// 连接标识开启
			// 下一次调用本函数时将检查这个值 如果值不为0 则直接返回本对象 不再调用本函数
			$this -> opened = 1;
			$this -> db -> query('SET NAMES ' . $this -> conf['charset']);
			return $this;
		}

		/**
		 * 取得MYSQL语句
		 * @param $sql
		 * @return obj
		 */
		public function send($sql){

			$this -> sql = trim($sql);
			return $this;
		}

		/**
		 * 发送SQL语句到MYSQL
		 * @param sql语句
		 */
		public function query($sql = 0){

			$sql && $this -> sql = trim($sql);
			$this -> result = $this -> db -> query($this -> sql);
			if(!$this -> result){
				return false;
			}
			return $this -> fetch();
		}

		/**
		 * 根据语句类型对返回的结果作出合适的处理方法
		 * 为了提高效率在这里不强制将字母转换为大写或小写
		 * @return array/intval/boolean
		 */
		private function fetch(){

			$r = false;
			$t = substr($this -> sql, 0, 1);
			switch($t){
				case 'S':
				case 's':
				case 'C': // 调用存储过程
				case 'c':
					while($row = $this -> result -> fetch_assoc()){
						$r[] = $row;
					}
					break;
				case 'u':
				case 'U':
				case 'i':
				case 'I':
				case 'd':
				case 'D':
					return $this -> db -> affected_rows;
			}
			return $r;
		}

		/**
		 * 释放资源
		 * @return 0
		 */
		private function __destruct(){

			gettype($this -> result) == 'object' && mysqli_free_result($this -> result);
			mysqli_close($this -> db);
			return 0;
		}
	}
