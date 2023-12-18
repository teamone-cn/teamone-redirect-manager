<?php


namespace TH\TeamOne\Redirect;

use \linkcache\Cache;
use TH\TeamOne\Redirect\TeamOneRedirectManagerPostTable as TeamOneRedirectManagerPostTable;

defined( 'ABSPATH' ) || exit;

class TeamOneRedirectManagerCache extends Cache {
	/**
	 * 默认配置
	 * @var array
	 */
	static private $config = [];

	/**
	 * 错误日志标签
	 */
	const CACHE_DRIVER_KEY = 'cache_driver';
	const ERROR_LOG_KEY = 'cache_driver_log';
	const ERROR_LOG_MSG = 'Error：Cache driver initialization failed';

	/**
	 * 构造缓存
	 *
	 * @param string $type 缓存驱动类型
	 * @param array $config 驱动配置
	 *
	 * @throws \Exception   异常
	 */
	public function __construct( $type = '', $config = [] ) {
		try {
			self::setConfig();
			parent::__construct( $type, $config );
		} catch ( \Exception $e ) {
			$msg = [ 'error' => true, 'msg' => self::ERROR_LOG_MSG ];
			self::errorLog( $e );

			return $msg;
		}
	}

	/**
	 * 获取缓存类实例
	 *
	 * @param string $type 缓存驱动类型
	 * @param array $config 驱动配置
	 *
	 * @return Cache        缓存类实例
	 * @throws \Exception   异常
	 */
	public static function getInstance( $type = '', $config = [] ) {
		try {
			self::setConfig();

			return parent::getInstance( $type, $config );
		} catch ( \Exception $e ) {
			$msg = [ 'error' => true, 'msg' => self::ERROR_LOG_MSG ];
			self::errorLog( $e );

			return $msg;
		}
	}

	/**
	 * 记录缓存驱动错误日志
	 *
	 * @param null $e
	 *
	 * @throws \Exception
	 */
	public static function errorLog( $e = null ) {
		global $wpdb;

		$cache_key    = self::get_cach_key( self::ERROR_LOG_KEY );
		$cache_driver = parent::getInstance();
		$cache        = $cache_driver->get( $cache_key );
		$msg          = self::ERROR_LOG_MSG . '，' . $e->getMessage();
		$cache_msg    = json_decode( $cache, true )['msg'] ?? '';
		if ( ! $cache && $cache_msg != $msg ) {
			$error = [ 'error' => true, 'msg' => $msg ];
			$cache_driver->set( $cache_key, json_encode( $error ) );
		}
	}

	/**
	 * 删除缓存驱动错误日志
	 * @throws \Exception
	 */
	public static function delErrorLog() {
		global $wpdb;

		$cache_key    = self::get_cach_key( self::ERROR_LOG_KEY );
		$cache_driver = parent::getInstance();
		$status       = $cache_driver->has( $cache_key );
		if ( $status ) {
			$statu = $cache_driver->del( $cache_key );
		}

		return $statu ?? true;
	}

	/**
	 * 获取缓存驱动错误日志
	 * @return mixed
	 * @throws \Exception
	 */
	public static function getErrorLog() {
		global $wpdb;

		$cache_key    = self::get_cach_key( self::ERROR_LOG_KEY );
		$cache_driver = parent::getInstance();
		$cache        = $cache_driver->get( $cache_key );

		return json_decode( $cache, true );
	}

	/**
	 * 设置配置
	 *
	 * @param array $config 配置信息
	 */
	static public function setConfig( $config = [] ) {
		$config = array_merge( self::merge_config(), $config );
		parent::setConfig( $config );
	}

	/**
	 * 获取配置表名称
	 * @return string
	 */
	public static function get_redirect_setting_table() {
		global $wpdb;

		return $wpdb->prefix . TeamOneRedirectManagerPostTable::SETTING_TABLE;
	}

	/**
	 *  获取重定向配置信息
	 * @return array
	 */
	public static function get_redirect_setting( $var = null ) {
		global $wpdb;

		$table        = self::get_redirect_setting_table();
		$cache_key    = self::get_cach_key( self::CACHE_DRIVER_KEY );
		$cache_driver = parent::getInstance();
		$cache        = $cache_driver->get( $cache_key );

		if ( $cache && $cache != 'null' ) {
			$setting = json_decode( $cache, true );
		} else {
			$sql     = "SELECT * FROM {$table} ORDER BY createtime DESC LIMIT 1";
			$setting = $wpdb->get_row( $sql, ARRAY_A );
			$cache_driver->set( $cache_key, json_encode( $setting ) );
		}

		return $setting[ $var ] ?? $setting;
	}

	/**
	 * 删除重定向配置信息缓存
	 * @return bool
	 * @throws \Exception
	 */
	public static function del_redirect_setting() {
		$cache_key    = self::get_cach_key( self::CACHE_DRIVER_KEY );
		$cache_driver = parent::getInstance();
		$status       = $cache_driver->has( $cache_key );

		if ( $status ) {
			$statu = $cache_driver->del( $cache_key );
		}

//		$cache_driver_redis = self::getInstance( 'redis' );
//		if ( ! is_array( $cache_driver_redis ) || ! isset( $cache_driver_redis['error'] ) ) {
//			$status2 = $cache_driver_redis->has( $cache_key );
//			if ( $status2 ) {
//				$statu2 = $cache_driver_redis->del( $cache_key );
//			}
//		}

		return $statu ?? true;
	}

	/**
	 * 合并重定向配置参数到缓存驱动
	 * @return array
	 */
	public static function merge_config() {
		$setting = self::get_redirect_setting();

		$config = [
			//默认使用的缓存驱动
			'default'   => 'files',
			//当前缓存驱动失效时，使用的备份驱动
			'fallback'  => 'files',
			'memcache'  => [
				//host,port,weight,persistent,timeout,retry_interval,status,failure_callback
				'servers'  => [
					[
						'host'           => '127.0.0.1',
						'port'           => 11211,
						'weight'         => 1,
						'persistent'     => true,
						'timeout'        => 1,
						'retry_interval' => 15,
						'status'         => true
					],
				],
				'compress' => [ 'threshold' => 2000, 'min_saving' => 0.2 ],
			],
			'memcached' => [
				'servers' => [
					[ 'host' => '127.0.0.1', 'port' => 11211, 'weight' => 1 ],
				],
				//参考 Memcached::setOptions
				'options' => [],
			],
			'redis'     => [
				'host'     => $setting['host_txt'] ?? '127.0.0.1',
				'port'     => $setting['redis_port'] ?? 6379,
				'password' => $setting['redis_password'] ?? '',
				'database' => '',
				'timeout'  => ''
			],
			'ssdb'      => [
				'host'      => '127.0.0.1',
				'port'      => 8888,
				'password'  => '',
				'timeoutms' => ''
			],
		];

		return self::$config = $config;
	}

	/**
	 * 返回缓存key前缀层级
	 * @return string
	 */
	public static function get_cach_key( $id ) {
		global $wpdb;
		$cache_default_key = '';
		$set_data = self::get_set_data();
		$server_name = $_SERVER['SERVER_NAME'];
        $server_domain = preg_replace('/^(.*?)\.(.*?)\.(.*?)$/', '$2.$3', $server_name);
		$server_name_key = !empty($set_data)&& !empty($set_data['redis_domain_key'])?$set_data['redis_domain_key']:$server_domain;

		if ( ! empty( $id ) ) {
			$rediskey = MD5( sha1( $id ) );
			// 设置默认的存储key前缀
			$cache_default_key = 'redirect:' . $server_name_key . ':' . $wpdb->prefix . ':' . $rediskey;
		}

		return $cache_default_key;
	}

	 /*
	 * 返回过期时间（避免缓存雪崩）
     * return int
     */
    public static function get_time_out($expire=0){

		if(empty($expire)){
			// redis过期时间设置为24小时相差内
			$expire = 60 * rand(10,60) * 24;
		}
		return $expire;
    }

	/*
	* 获取配置数据
	*/
	public static function get_set_data(){
		global $wpdb;
		$table        = self::get_redirect_setting_table();
		$sql     = "SELECT * FROM {$table} ORDER BY createtime DESC LIMIT 1";
		$setting = $wpdb->get_row( $sql, ARRAY_A );
		return $setting;
	}
}