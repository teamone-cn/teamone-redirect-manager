<?php
/**
 * Handle redirection
 *
 * @package safe-redirect-manager
 */

namespace TH\TeamOne\Redirect;

defined( 'ABSPATH' ) || exit;

use TH\TeamOne\Redirect\TeamOneRedirectManagerPostList as TeamOneRedirectManagerPostList;
use TH\TeamOne\Redirect\TeamOneRedirectManagerCurl as TeamOneRedirectManagerCurl;
use TH\TeamOne\Redirect\TeamOneRedirectManagerCache as TeamOneRedirectManagerCache;

/**
 * Redirect Router Class
 */
class TeamOneRedirectManagerRouter {
	/**
	 * 跳转链接域名
	 * @var
	 */
	private $whitelist_host;

	// 秘钥
	const SECRET_KEY = '8649ed42dfb7593ff20b1bd34920ebfd';
	const API_SECRET_KEY = 'shoxkZsholo5oRkZoZoVolkZs9olohoBs9olodshkZo5s1oB';

	// 表单验证nonce
	const SECRET_NONCE = 'th_team_one_redirect_api';

	// 请求中间服务器api
	// const API_URL = "http://redirect.shiyongfeng.cn:9501/Api/Index";
	const API_URL = "http://redirect-manager-proxy.thwpmanage.com:9501/Api/Index";

	// 重写规则状态码
	const URL_REWRITE_CODE = '3001';

	// 启用的重定向数据缓存key
	const REDIRECTS_DATA_KEY = 'redirects_data';

	/**
	 * 单例模式
	 * @return false|TeamOneRedirectManagerRouter
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}

	/**
	 * 初始化钩子函数
	 */
	public function setup() {
		add_action( 'init', array( $this, 'setup_redirect' ), 0 );
	}

	/**
	 * 检测重定向命中
	 */
	public function setup_redirect() {
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 0 );
	}

	/**
	 * 获取数据表名称
	 * @return string
	 */
	private static function get_table() {
		return TeamOneRedirectManagerPostList::get_table();
	}

	/**
	 * 获取配置表名称
	 * @return string
	 */
	private static function get_redirect_setting_table() {
		return TeamOneRedirectManagerPostList::get_redirect_setting_table();
	}

	/**
	 * 重定向主机白名单
	 *
	 * @param array $hosts Array of hosts
	 *
	 * @return array
	 * @since 1.8
	 */
	public function filter_allowed_redirect_hosts( $hosts ) {
		$without_www = preg_replace( '/^www\./i', '', $this->whitelist_host );
		$with_www    = 'www.' . $without_www;

		$hosts[] = $without_www;
		$hosts[] = $with_www;

		return array_unique( $hosts );
	}

	/**
	 * 获取所有启用的重定向
	 * @return array|object|\stdClass[]|null
	 */
	public static function redirects() {
		global $wpdb;
		$table     = self::get_table();
		$sql       = "SELECT id ID,redirect_rule_from redirect_from,redirect_rule_to redirect_to,redirect_rule_status_code status_code,redirect_rule_from_regex enable_regex FROM {$table} WHERE status = '1' ORDER BY createtime ASC";
		$redirects = $wpdb->get_results( $sql, ARRAY_A );

		return $redirects;
	}

	/**
	 * 获取所有启用的重定向缓存
	 * @return array
	 */
	public static function get_redirects() {
		global $wpdb;
		$table        = self::get_table();
		$cache_key    = TeamOneRedirectManagerCache::get_cach_key( self::REDIRECTS_DATA_KEY );
		$cache_driver = TeamOneRedirectManagerCache::getInstance( 'redis' );

		if ( ! is_array( $cache_driver ) || ! isset( $cache_driver['error'] ) ) {
			$cache = $cache_driver->get( $cache_key );

			if ( $cache && $cache != 'null' ) {
				$redirects = json_decode( $cache, true );
			} else {
				$redirects = self::redirects();
				$cache_driver->set( $cache_key, json_encode( $redirects ) );
			}

			return $redirects;
		}

		$redirects = self::redirects();

		return $redirects;
	}

	/**
	 *  获取重定向配置信息
	 * @return array
	 */
	public static function get_redirect_setting( $var = null ) {
//		global $wpdb;
//
//		$table        = self::get_redirect_setting_table();
//		$cache_key    = TeamOneRedirectManagerCache::get_cach_key( TeamOneRedirectManagerCache::CACHE_DRIVER_KEY );
//		$cache_driver = TeamOneRedirectManagerCache::getInstance( 'redis' );
//
//		if ( ! is_array( $cache_driver ) || ! isset( $cache_driver['error'] ) ) {
//			$cache = $cache_driver->get( $cache_key );
//
//			if ( $cache && $cache != 'null' ) {
//				$setting = json_decode( $cache, true );
//			} else {
//				$sql     = "SELECT * FROM {$table} ORDER BY createtime DESC LIMIT 1";
//				$setting = $wpdb->get_row( $sql, ARRAY_A );
//				$cache_driver->set( $cache_key, json_encode( $setting ) );
//			}
//
//			return $setting[ $var ] ?? $setting;
//		}

		return TeamOneRedirectManagerCache::get_redirect_setting( $var );
	}


	/**
	 * 重定向路径命中匹配
	 *
	 * @param string $requested_path The path to check redirects for.
	 *
	 * @return array|bool The redirect url. False if no redirect is found.
	 */
	public function match_redirect( $requested_path ) {
		// 获取启用的重定向数据
		$redirects = self::get_redirects();
		//重定向匹配模式(1[php模式],2[ngnix模式])
		$redirect_module = self::get_redirect_setting( 'redirect_module' );

		// 没有重定向数据，跳出逻辑
		if ( empty( $redirects ) || $redirect_module=='2') {
			return false;
		}

		// 如果WordPress驻留在一个不是公共根目录中，WP路径偏离所请求的路径，我们必须砍掉
		// 前台域名：路由规则域名|首页地址
		$rule_domain = self::get_redirect_setting( 'rule_domain' );
		$home_url    = ! empty( $rule_domain ) ? $rule_domain : home_url();
		if ( function_exists( 'wp_parse_url' ) ) {
			$parsed_home_url = wp_parse_url( $home_url );
		} else {
			$parsed_home_url = parse_url( $home_url );
		}

		// 站点前台存在子目录时并且非主页时
		if ( isset( $parsed_home_url['path'] ) && '/' !== $parsed_home_url['path'] ) {
			// @界定符同'//'，i忽略大小写，将前台目录替换成请求链接路径
			$requested_path = preg_replace( '@' . $parsed_home_url['path'] . '@i', '', $requested_path, 1 );
		}

		if ( empty( $requested_path ) ) {
			$requested_path = '/';
		}

		// 过滤重定向数据
		$redirects = apply_filters( 'srm_registered_redirects', $redirects, $requested_path );

		// 过滤重定向匹配启用不区分大小写
		$case_insensitive = apply_filters( 'srm_case_insensitive_redirects', true );

		// 匹配允许不区分大小写的请求路径
		if ( $case_insensitive ) {
			$regex_flag = 'i';
			// 规范化路径用于匹配，请求路径转小写
			$normalized_requested_path = strtolower( $requested_path );
		} else {
			$regex_flag                = '';
			$normalized_requested_path = $requested_path;
		}

		if ( function_exists( 'wp_parse_url' ) ) {
			$parsed_requested_path = wp_parse_url( $normalized_requested_path );
		} else {
			$parsed_requested_path = parse_url( $normalized_requested_path );
		}

		// 规范化包含和不包含查询字符串的请求路径，以便稍后进行比较
		$normalized_requested_path_no_query = '';
		$requested_query_params             = '';

		// 请求路径
		if ( ! empty( $parsed_requested_path['path'] ) ) {
			$normalized_requested_path_no_query = untrailingslashit( stripslashes( $parsed_requested_path['path'] ) );
		}

		// 请求参数
		if ( ! empty( $parsed_requested_path['query'] ) ) {
			$requested_query_params = $parsed_requested_path['query'];
		}

		// 循环重定向数据匹配命中
		foreach ( (array) $redirects as $redirect ) {
			$redirect_from = untrailingslashit( $redirect['redirect_from'] );
			if ( empty( $redirect_from ) ) {
				// 只有在根上有重定向的情况下才会发生这种情况
				$redirect_from = '/';
			}

			// 过滤重定向规则，去除域名，保留匹配路径
			if ( function_exists( 'wp_parse_url' ) ) {
				$parsed_redirect_from = wp_parse_url( $redirect_from );
			} else {
				$parsed_redirect_from = parse_url( $redirect_from );
			}
			$redirect_from = isset( $parsed_redirect_from['path'] ) && ! empty( $parsed_redirect_from['path'] ) ? $parsed_redirect_from['path'] : $redirect_from;

			$redirect_to  = $redirect['redirect_to'];
			$status_code  = $redirect['status_code'];
			$enable_regex = ( isset( $redirect['enable_regex'] ) ) ? $redirect['enable_regex'] : false;
			$redirect_id  = $redirect['ID'];

			// 检查重重定向目标是否有效，否则请跳过
			if ( empty( $redirect_to ) ) {
				continue;
			}

			// 检查请求的路径是否与重定向规则相同
			if ( $enable_regex ) {
				// 正则匹配
				$match_query_params = false;
				// 正则匹配请求路径是否包含重定向规则
				$matched_path = preg_match( '@' . $redirect_from . '@' . $regex_flag, $requested_path );
			} else {
				// 非正则匹配
				if ( $case_insensitive ) {
					$redirect_from = strtolower( $redirect_from );
				}

				// 如果重定向规则值包含参数，则仅比较查询参数
				$match_query_params = apply_filters( 'srm_match_query_params', strpos( $redirect_from, '?' ) );

				// 规范化匹配路径，区分全路径或请求参数除外，完成初步匹配
				$to_match     = ( ! $match_query_params && ! empty( $normalized_requested_path_no_query ) ) ? $normalized_requested_path_no_query : $normalized_requested_path;
				$matched_path = ( $to_match === $redirect_from );

				// 检查重定向规则是否以通配符结尾
				if ( ! $matched_path && ( strrpos( $redirect_from, '*' ) === strlen( $redirect_from ) - 1 ) ) {
					$wildcard_base = substr( $redirect_from, 0, strlen( $redirect_from ) - 1 );

					// 如果请求的路径与重定向的基匹配，则标记为路径匹配。
					$matched_path = ( substr( trailingslashit( $normalized_requested_path ),
							0,
							strlen( $wildcard_base ) ) === $wildcard_base );
					if ( ( strrpos( $redirect_to, '*' ) === strlen( $redirect_to ) - 1 ) ) {
						$redirect_to = rtrim( $redirect_to, '*' ) . ltrim( substr( $requested_path,
								strlen( $wildcard_base ) ),
								'/' );
					}
				}
			}

			if ( $matched_path ) {
				// 请求路径匹配命中，重定向目标二次过滤
				if ( function_exists( 'wp_parse_url' ) ) {
					$parsed_redirect = wp_parse_url( $redirect_to );
				} else {
					$parsed_redirect = parse_url( $redirect_to );
				}

				// 重定向主机白名单验证，未完善
				if ( is_array( $parsed_redirect ) && ! empty( $parsed_redirect['host'] ) ) {
					$this->whitelist_host = $parsed_redirect['host'];
					add_filter( 'allowed_redirect_hosts', array( $this, 'filter_allowed_redirect_hosts' ) );
				}

				// 正则匹配，请求路径中包含的重定向规则替换成重定向目标
				// if ($enable_regex) {
				//     $redirect_to = preg_replace('@' . $redirect_from . '@' . $regex_flag, $redirect_to, $requested_path);
				// }

				// 如果通配符尚未添加查询参数，请重新添加它们
				// 查询参数未命中，但存在并且跳转路径也存在的情况，补充查询参数
				if ( ! $match_query_params && ! empty( $requested_query_params ) && ! strpos( $redirect_to, '?' ) ) {
					$redirect_to .= '?' . $requested_query_params;
				}

				// 过滤重定向目标，如果不带域名自动拼上站点前台域名
				$sanitized_redirect_to = esc_url_raw( apply_filters( 'srm_redirect_to', $redirect_to ) );
				if ( function_exists( 'wp_parse_url' ) ) {
					$parsed_redirect_to_path = wp_parse_url( $sanitized_redirect_to );
				} else {
					$parsed_redirect_to_path = parse_url( $sanitized_redirect_to );
				}
				if ( is_array( $parsed_redirect_to_path ) && empty( $parsed_redirect_to_path['host'] ) ) {
					$sanitized_redirect_to = $home_url . $sanitized_redirect_to;
				}

				return [
					'redirect_to'  => $sanitized_redirect_to,
					'status_code'  => $status_code,
					'enable_regex' => $enable_regex,
					'redirect_id'  => $redirect_id,
				];
			}
		}

		return false;
	}

	/**
	 * 执行重定向&重写
	 */
	public function maybe_redirect() {
		// 后台&404 页面禁用重定向功能，除开过滤钩子启用
		if ( is_admin() || ( apply_filters( 'srm_redirect_only_on_404', false ) && ! is_404() ) ) {
			return;
		}

		// 请求路径
		$requested_path = esc_url_raw( apply_filters( 'srm_requested_path', $_SERVER['REQUEST_URI'] ) );

		// 请求路径 （去除反斜杠和路径末尾斜杠）
		// $requested_path = untrailingslashit( stripslashes( $requested_path ) );

		// 请求路径重定向命中
		$matched_redirect = $this->match_redirect( $requested_path );
		if ( empty( $matched_redirect ) ) {
			return;
		}

		do_action(
			'srm_do_redirect',
			$requested_path,
			$matched_redirect['redirect_to'],
			$matched_redirect['status_code']
		);

		if ( defined( 'PHPUNIT_SRM_TESTSUITE' ) && PHPUNIT_SRM_TESTSUITE ) {
			// 如果我们正在测试，请不要实际重定向
			return;
		}

		header( 'X-Safe-Redirect-Manager: true' );
		header( 'X-Safe-Redirect-ID: ' . esc_attr( $matched_redirect['redirect_id'] ) );
		header( 'X-Safe-Request-Page: ' . esc_attr( $matched_redirect['redirect_to'] ) );
		header( 'Cache-Control: no-cache' );

		// 无效的重定向状态码，默认 302 状态码
		if ( ! in_array( $matched_redirect['status_code'], teamone_redirect_srm_get_valid_status_codes(), true ) ) {
			$matched_redirect['status_code'] = apply_filters( 'srm_default_direct_status', 302 );
		}

		// 重定向重写逻辑
		if ( $matched_redirect['status_code'] == '3001' ) {
			$page_cache_key = TeamOneRedirectManagerCache::get_cach_key( $matched_redirect['redirect_id'] );
			$cache_driver   = TeamOneRedirectManagerCache::getInstance( 'redis' );

			if ( ! is_array( $cache_driver ) || ! isset( $cache_driver['error'] ) ) {
				$cache = $cache_driver->get( $page_cache_key );
				if ( $cache && $cache != 'null' ) {
					$page_data = $cache;
				} else {
					$page_data = self::get_page_data( $matched_redirect['redirect_to'] );
					if ( $page_data && ! empty( $page_data ) ) {
						$cache_driver->set( $page_cache_key, $page_data, TeamOneRedirectManagerCache::get_time_out() );
					}
				}
			} else {
				$page_data = self::get_page_data( $matched_redirect['redirect_to'] );
			}
			if ( $page_data && ! empty( $page_data ) ) {
				status_header(200);
				echo $page_data;
				exit();
			}
		} else {
			// 防止重定向路径出错，跳转站点后台，默认跳转站点前台
			add_filter( 'wp_safe_redirect_fallback', function () {
				$rule_domain = self::get_redirect_setting( 'rule_domain' );

				return ! empty( $rule_domain ) ? $rule_domain : home_url();
			} );

			wp_safe_redirect( $matched_redirect['redirect_to'],
				$matched_redirect['status_code'],
				'Safe Redirect Manager' );
			exit();
		}
	}

	/**
	 * 获取重写页面数据
	 *
	 * @param null $redirect_to
	 *
	 * @return bool|string
	 */
	public static function get_page_data( $redirect_to = null ) {
		if ( empty( $redirect_to ) ) {
			return false;
		}
		$timestamp = time();
		// 获取签名
		$signature = self::get_signature( $timestamp );

		// 请求数据
		$data = array(
			'url'       => $redirect_to,
			'nonce'     => self::SECRET_NONCE,
			'timestamp' => $timestamp,
			'signature' => $signature,
		);

		$curl     = new TeamOneRedirectManagerCurl();
		$response = $curl->post( self::API_URL, $data );

		$url_data = json_decode( $response, true );

		if ( $url_data['code'] == '200' ) {
			$url_data = $url_data['result'];
		}

		return $url_data;
	}

	/**
	 * 生成签名
	 * @param string $timestamp 
	 * @param string $secret_nonce 
	 * @param string $secret_key 
	 * @return string
	 */
	public static function get_signature( $timestamp,$secret_nonce='',$secret_key='') {
		$secret_nonce = !empty($secret_nonce)?$secret_nonce:self::SECRET_NONCE;
		$secret_key = !empty($secret_key)?$secret_key:self::SECRET_KEY;
		// 签名
		$signature = md5( $secret_nonce . $timestamp . $secret_key );

		return $signature;
	}
}

