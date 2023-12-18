<?php
/**
 * Plugin Name:       TeamOne Redirect Manager
 * Plugin URI:        https://www.teamonetech.cn
 * Description:       Easily and safely manage HTTP redirects.
 * Version:           1.0.0
 * Requires at least: 4.6
 * Requires PHP:      5.6
 * Author:            TeamOne
 * Author URI:        TeamOne
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       team-one-redirect-manager
 *
 * @package Team One Redirect Manager
 */

namespace TH\TeamOne\Redirect;

defined( 'ABSPATH' ) || exit;

class TeamOneRedirectManager {
	private static $instance = null;

	const PLUGIN_STATUS = 'th_team_one_redirect_manager_active';
	const PLUGIN_TEXT_DOMAIN = 'team-one-redirect-manager';
	const PLUGIN_VERSION = '1.0.0';
	const TH_TEAM_ONE_REDIRECT_RESOURCE = 'th_team_one_redirect_resource';


	protected static $plugin_base_name = '';
	protected static $plugin_dir = '';
	protected static $plugin_url = '';
	protected static $plugin_assets_url = '';


	public function __construct() {
		$this->init_config();
		$this->includes();
		$this->init_hooks();
	}

	public function __wakeup() {
	}

	private function __clone() {
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init_config() {
		self::$plugin_base_name  = plugin_basename( __FILE__ );
		self::$plugin_dir        = plugin_dir_path( __FILE__ );
		self::$plugin_url        = plugins_url( "/", __FILE__ );
		self::$plugin_assets_url = plugins_url( "/", __FILE__ ) . 'assets/';
	}

	/**
	 * 插件初始化钩子
	 * Hook into actions and filters.
	 * @since 2.0.0
	 * @access private
	 */
	private function init_hooks() {
		//启用
		register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );

		//停用
		register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );

		//卸载
		register_uninstall_hook( __FILE__, array( __CLASS__, 'plugin_uninstall' ) );

		//初始化
		add_action( 'after_setup_theme', array( $this, 'plugin_init' ) );

		//插件管理项快捷入口
		add_filter( 'plugin_action_links_' . self::$plugin_base_name, array( $this, 'settings_link' ) );
	}

	public static function plugin_init() {
		if ( get_option( self::PLUGIN_STATUS ) != 1 ) {
			return false;
		}

		TeamOneRedirectManagerPostType::factory();
		TeamOneRedirectManagerRouter::factory();
		TeamOneRedirectApi::factory();
		TeamOneRedirectFile::factory();
		TeamOneRedirectNgnix::factory();
		TeamOneRedirectFile::factory();

	}

	/**
	 * 插件管理项快捷入口
	 *
	 * @param $links
	 *
	 * @return mixed
	 */
	public function settings_link( $links ) {
		$setting_link = '<a href="options-general.php?page=th_team_one_redirect_manager">' .
		                __( 'Settings' ) .
		                '</a>';
		array_unshift( $links, $setting_link );

		return $links;
	}

	/**
	 * 插件核心文件加载
	 * Include required core files used in admin and on the frontend.
	 * @access private
	 */
	private function includes() {
		//权限判断
		if ( get_option( self::PLUGIN_STATUS ) == 1 ) {
			//引入扩展
			require_once dirname( __FILE__ ) . '/vendor/autoload.php';
			require_once dirname( __FILE__ ) . '/inc/functions.php';
			require_once dirname( __FILE__ ) . '/inc/classes/class-srm-cache.php';
			require_once dirname( __FILE__ ) . '/inc/classes/class-srm-curl.php';
			require_once dirname( __FILE__ ) . '/inc/classes/class-srm-post-table.php';
			require_once dirname( __FILE__ ) . '/inc/classes/class-srm-post-list.php';
			require_once dirname( __FILE__ ) . '/inc/classes/class-srm-post-type.php';
			require_once dirname( __FILE__ ) . '/inc/classes/class-srm-redirect.php';
			require_once dirname( __FILE__ ) . '/inc/classes/class-srm-api.php';
			require_once dirname( __FILE__ ) . '/inc/classes/class-srm-status.php';
			require_once dirname( __FILE__ ) . '/inc/classes/class-srm-file.php';
			require_once dirname( __FILE__ ) . '/inc/classes/class-srm-ngnix.php';

		}
	}

	/**
	 * 启用
	 */
	public function plugin_activation() {
		// 更新插件集合表中的数据
		global $wpdb;
		$resourece_deta = self::get_resoure_data();
		$table_name = self::TH_TEAM_ONE_REDIRECT_RESOURCE;
		$redirect_table = $wpdb->prefix.'th_team_one_redirect_manager';
		// var_dump($resourece_deta);exit;
		if(empty($resourece_deta)){
			$wpdb->insert(
				$table_name, //table
				array(
					'resource_db_name'  => $redirect_table,
					'createtime'        =>  time(),
					'updatetime'	 	=>  time(),
					'status'		 	=>  '1',
				), array(
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);
		}else{
			$wpdb->update(
				$table_name, //table
				// Data
				array(
					'status' 	   => '1',
					'updatetime'   => time(),
				),
				// Where
				array( 'resource_db_name' => $redirect_table ),
				// Data format
				array(
					'%s',
					'%s',
				),
				// Where format
				array( '%s' )
			);
		}
		update_option( self::PLUGIN_STATUS, 1 );
	}

	/**
	 * 停用
	 */
	public function plugin_deactivation() {
		global $wpdb;
		$table_name = self::TH_TEAM_ONE_REDIRECT_RESOURCE;
		$redirect_table = $wpdb->prefix.'th_team_one_redirect_manager';
		$res = $wpdb->update(
			$table_name, //table
			// Data
			array(
				'status' 	   => '0',
				'updatetime'   => time(),
			),
			// Where
			array( 'resource_db_name' => $redirect_table ),
			// Data format
			array(
				'%s',
				'%s',
			),
			// Where format
			array( '%s' )
		);
		if ( ! $res ) {
			echo 'Update Error:' . $wpdb->last_error;
			return false;
		}
		update_option( self::PLUGIN_STATUS, 0 );
	}

	/**
	 * 卸载
	 */
	public function plugin_uninstall() {
		global $wpdb;
		$table_name = self::TH_TEAM_ONE_REDIRECT_RESOURCE;
		$redirect_table = $wpdb->prefix.'th_team_one_redirect_manager';
		$res = $wpdb->update(
			$table_name, //table
			// Data
			array(
				'status' 	   => '2',
				'updatetime'   => time(),
				'deletetime'   => time(),
			),
			// Where
			array( 'resource_db_name' => $redirect_table ),
			// Data format
			array(
				'%s',
				'%s',
				'%s',
			),
			// Where format
			array( '%s' )
		);
		if ( ! $res ) {
			echo 'Update Error:' . $wpdb->last_error;
			return false;
		}
		return delete_option( self::PLUGIN_STATUS );
	}

	/**
	 * 自动加载
	 */
	function load_file_func( $folder ) {
		if ( $globs = glob( "{$folder}/*.php" ) ) {
			foreach ( $globs as $file ) {
				require_once $file;
			}
		}
	}

	/**
	 * 获取本站点是否配置来源表
	 */
	public static function get_resoure_data(){
		global $wpdb;
		$redirect_args = array();
		$table_name = self::TH_TEAM_ONE_REDIRECT_RESOURCE;
		// 路由重定向表
		$redirect_table = $wpdb->prefix.'th_team_one_redirect_manager';
		//selecting value to update
		$redirect_args[] = $redirect_table;
		$sql = "SELECT * FROM `{$table_name}` WHERE resource_db_name = %s ";
		$redirect_data = $wpdb->get_results(
			$wpdb->prepare(
				$sql,
				$redirect_args
			)
		);
		return $redirect_data;
	}

	/**
	 * 获取全部配置来源表
	 */
	public static function get_all_resource_data(){
		global $wpdb;
		$redirect_args = array();
		$table_name = self::TH_TEAM_ONE_REDIRECT_RESOURCE;
		//selecting value to update
		$redirect_args[] = '1';
		$sql = "SELECT * FROM `{$table_name}` WHERE status = %s ";
		$redirect_data = $wpdb->get_results(
			$wpdb->prepare(
				$sql,
				$redirect_args
			)
		);
		return $redirect_data;
	}

	/**
	 * 获取指定表数据
	 * @param string $table_name
	*/
	public static function get_redirect_data($table_name,$per_page = 10, $current_page = 1){
		global $wpdb;
		$resource_data = self::get_all_resource_data();
		$resource_arr = array_column($resource_data,'resource_db_name');
		$redirect_data = false;
		$count = 0;
		if(!empty($resource_arr)){
			if(in_array($table_name,$resource_arr)){
				$querystr = "SELECT * from `{$table_name}` where status <> '0' ";
				$offset   = ( $current_page - 1 ) * $per_page;
         		$querystr .= " limit " . $offset . "," . $per_page;
				$redirect_data  = $wpdb->get_results( $querystr);

				$count_query = "SELECT COUNT(*) from `{$table_name}` where status <> '0'";
				$count       = $wpdb->get_var( $count_query );
			}
		}
		return array('redirect_data'=>$redirect_data,'total'=> $count);
	}
}

TeamOneRedirectManager::get_instance();

