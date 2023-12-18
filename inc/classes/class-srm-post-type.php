<?php

namespace TH\TeamOne\Redirect;

defined( 'ABSPATH' ) || exit;

use TH\TeamOne\Redirect\TeamOneRedirectManagerPostList as TeamOneRedirectManagerPostList;
use TH\TeamOne\Redirect\TeamOneRedirectManagerCache as TeamOneRedirectManagerCache;
use TH\TeamOne\Redirect\TeamOneRedirectManagerRouter as TeamOneRedirectManagerRouter;

/**
 * Setup SRM post type
 *
 * @package safe-redirect-manager
 */

/**
 * Post type class
 */
class TeamOneRedirectManagerPostType {


	/**
	 * Status code lables for reuse
	 *
	 * @var array
	 */
	public $status_code_labels = array(); // Defined later to allow i18n

	/**
	 * We have to store the redirect search so we can grab it later
	 *
	 * @var string
	 */
	private $redirect_search_term;

	// 状态变量
	const REDIRECT_STATUS_ACTIVE = '1';//激活
	const REDIRECT_STATUS_INACTIVE = '2';//关闭


	/**
	 * Sets up redirect manager
	 *
	 * @since 1.8
	 */
	public function setup() {
		$this->status_code_labels = teamone_redirect_srm_get_valid_status_codes_data();

		add_action( 'admin_enqueue_scripts', array( $this, 'load_resources' ), 10, 1 );
		add_action( 'wp_ajax_srm_validate_from_url', array( $this, 'srm_validate_from_url' ), 10, 0 );

		//添加设置菜单
		add_action( 'admin_menu', array( $this, 'add_manage_menu' ) );
	}

	/**
	 * Return singleton instance of class
	 *
	 * @return object
	 * @since 1.8
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
	 * Load scripts.
	 *
	 * @return void
	 */
	public function load_resources( $hook ) {
		$allowed_pages = array(
			'toplevel_page_th_team_one_redirect_manager',
			'admin_page_team-one-redirect-create',
			'admin_page_team-one-redirect-update',
		);
		wp_enqueue_script( 'nnr-redirect-showboxes-js',
			plugin_dir_url( 'th-team-one-redirect-manager/th_team_one_redirect_manager.php' ) . 'assets/js/nnr-redirect-showboxes.js',
			array(),
			'',
			true );
		wp_register_style( 'team-one-redirect_general_admin_assets',
			plugin_dir_url( 'th-team-one-redirect-manager/th_team_one_redirect_manager.php' ) . 'assets/css/style-general-admin.css' );
		wp_enqueue_style( 'team-one-redirect_general_admin_assets' );

		if ( in_array( $hook, $allowed_pages ) ) {
			// Plugin's CSS
			wp_register_style( 'team-one-redirect_assets',
				plugin_dir_url( 'th-team-one-redirect-manager/th_team_one_redirect_manager.php' ) . 'assets/css/style-admin.css' );
			wp_enqueue_style( 'team-one-redirect_assets' );
		}
	}

	/**
	 * Validate whether the from URL already exists or not.
	 *
	 * @return void
	 */
	public function srm_validate_from_url() {
		$_wpnonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING );
		if ( ! wp_verify_nonce( $_wpnonce, 'srm-save-redirect-meta' ) ) {
			echo 0;
			die();
		}

		$from = filter_input( INPUT_GET, 'from', FILTER_SANITIZE_STRING );

		/**
		 * SRM treats '/sample-page' and 'sample-page' equally.
		 * If the $from value does not start with a forward slash,
		 * then we normalize it by adding one.
		 */
		$from = '/' === substr( $from, 0, 1 ) ? $from : '/' . $from;

		$existing_post_ids = new WP_Query(
			[
				'meta_key'               => '_redirect_rule_from',
				'meta_value'             => $from,
				'fields'                 => 'ids',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'post_type'              => 'redirect_rule',
				'post_status'            => 'publish',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		// If no posts found, then bail out.
		if ( empty( $existing_post_ids->posts ) ) {
			echo 1;
			die();
		}

		$existing_post_id = $existing_post_ids->posts[0];

		echo esc_url( get_edit_post_link( $existing_post_id ) );
		die();
	}

	public function add_manage_menu() {
		/**********************一级菜单*********************/
		//列表菜单
		$hook_suffix = add_menu_page(
			__( 'TeamOne Redirect Manager', 'safe-redirect-manager' ),
			__( 'TeamOne Redirect', 'safe-redirect-manager' ),
			'administrator',
			'th_team_one_redirect_manager',
			array( TeamOneRedirectManagerPostList::get_instance(), 'display_list_table' ),
			'dashicons-controls-repeat',
			99 );
		add_action( 'load-' . $hook_suffix, array( TeamOneRedirectManagerPostList::get_instance(), 'screen_options' ) );

		// This is a submenu
		add_submenu_page(
			__( 'TeamOne Redirect Manager', 'safe-redirect-manager' ),
			__( 'TeamOne Redirect Manager', 'safe-redirect-manager' ),
			'Add New',
			'manage_options',
			'team-one-redirect-create',
			array( $this, 'redirect_create' )
		);

		// This submenu is HIDDEN, however, we need to add it anyways
		add_submenu_page(
			null,
			'Request Handler Script',
			'Request Handler',
			'manage_options',
			'team-one-redirect-request-handler',
			array( $this, 'redirect_request_handler' )
		);

		// This submenu is HIDDEN, however, we need to add it anyways
		add_submenu_page(
			__( 'TeamOne Redirect Manager', 'safe-redirect-manager' ),
			__( 'TeamOne Redirect Manager', 'safe-redirect-manager' ),
			'Edit Redirect',
			'manage_options',
			'team-one-redirect-update',
			array( $this, 'redirect_update' )
		);

		// This is a submenu
		add_submenu_page(
			'th_team_one_redirect_manager',
			'Tools',
			'Tools',
			'manage_options',
			'team-one-redirect-tools',
			array( $this, 'redirect_tools' )
		);

		//给redis、日志设置增加权限控制(仅限超级管理员)
		$capability = is_multisite() ? 'manage_site' : 'manage_options';
		if ( current_user_can( $capability ) ) {
			//设置
			add_submenu_page(
				'th_team_one_redirect_manager',
				'Setting',
				'Setting',
				'manage_options',
				'team-one-redirect-set',
				array( $this, 'redirect_set' )
			);
		}

		// hfcm_set_request
		add_submenu_page(
			null,
			'Set Request',
			'Set Request',
			'manage_options',
			'team-one-redirect-set-request',
			array( $this, 'redirect_set_request' )
		);

		// 导出方法
		self::team_one_redirect_export();

		// 导入方法
		self::team_one_redirect_import();
	}

	/*
	   * function for submenu "Add snippet" page
	   */
	public static function redirect_create() {
		// check user capabilities
		$nnr_redirect_can_edit = current_user_can( 'manage_options' );

		if ( ! $nnr_redirect_can_edit ) {
			echo 'Sorry, you do not have access to this page.';

			return false;
		}

		// prepare variables for redirect-add-edit.php
		$redirect_rule_from        = '';
		$redirect_rule_to          = '';
		$redirect_rule_status_code = '';
		$redirect_rule_from_regex  = '0';
		$redirect_rule_notes       = '';
		$redirect_name             = '';
		$status                    = '';
		$nnr_redirect_status_array = self::redirect_status();
		// Notify hfcm-add-edit.php NOT to make changes for update
		$update = false;

		include_once plugin_dir_path( __FILE__ ) . '/redirect-add-edit.php';
	}


	/*
	* function to handle add/update requests
	*/
	public static function redirect_request_handler() {
		// check user capabilities
		$nnr_redirect_can_edit = current_user_can( 'manage_options' );
		if ( ! $nnr_redirect_can_edit ) {
			echo 'Sorry, you do not have access to this page.';

			return false;
		}

		if ( isset( $_POST['insert'] ) ) {
			// Check nonce
			check_admin_referer( 'create-redirect' );
		} else {
			if ( empty( $_REQUEST['id'] ) ) {
				die( 'Missing ID parameter.' );
			}
			$id = absint( $_REQUEST['id'] );
		}
		if ( isset( $_POST['update'] ) ) {
			// Check nonce
			check_admin_referer( 'update-redirect_' . $id );
		}

		if ( isset( $_POST['insert'] ) || isset( $_POST['update'] ) ) {
			// Create / update snippet

			// Sanitize fields
			$redirect_rule_from_regex  = self::redirect_sanitize_text( 'redirect_rule_from_regex' );
			$redirect_rule_from        = self::redirect_sanitize_text( 'redirect_rule_from', false );
			$redirect_rule_to          = self::redirect_sanitize_text( 'redirect_rule_to', false );
			$redirect_rule_status_code = self::redirect_sanitize_text( 'redirect_rule_status_code' );
			$status                    = self::redirect_sanitize_text( 'status' );
			$redirect_rule_notes       = self::redirect_sanitize_text( 'redirect_rule_notes', true );
			$redirect_name             = self::redirect_sanitize_text( 'redirect_name', false );


			$list_obj   = new TeamOneRedirectManagerPostList();
			$table_name = $list_obj->get_table();
			// Global vars
			global $wpdb;
			global $current_user;
			// Update snippet
			if ( isset( $id ) ) {
				$res = $wpdb->update(
					$table_name, //table
					// Data
					array(
						'redirect_rule_from_regex'  => intval( $redirect_rule_from_regex ),
						'redirect_rule_from'        => $redirect_rule_from,
						'redirect_rule_to'          => $redirect_rule_to,
						'redirect_rule_status_code' => $redirect_rule_status_code,
						'status'                    => $status,
						'redirect_rule_notes'       => $redirect_rule_notes,
						'redirect_name'             => $redirect_name,
						'updatetime'                => current_time('timestamp'),
					),
					// Where
					array( 'id' => $id ),
					// Data format
					array(
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
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
				self::redirect( admin_url( 'admin.php?page=team-one-redirect-update&message=1&id=' . $id ) );
			} else {
				// Create new snippet
				$wpdb->insert(
					$table_name, //table
					array(
						'redirect_rule_from_regex'  => $redirect_rule_from_regex,
						'redirect_rule_from'        => $redirect_rule_from,
						'redirect_rule_to'          => $redirect_rule_to,
						'redirect_rule_status_code' => $redirect_rule_status_code,
						'status'                    => $status,
						'redirect_rule_notes'       => $redirect_rule_notes,
						'redirect_name'             => $redirect_name,
						'createtime'                =>current_time('timestamp'),
						'updatetime'                =>current_time('timestamp'),
					), array(
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
					)
				);
				$lastid = $wpdb->insert_id;
				self::redirect( admin_url( 'admin.php?page=team-one-redirect-update&message=6&id=' . $lastid ) );
			}
			
			$cache_driver = TeamOneRedirectManagerCache::getInstance( 'redis' );
			$cach_id = '';
			if(isset( $id ) ){
				$cach_id = $id;
			}else{
				$cach_id = $lastid;
			}
			$cache_key = TeamOneRedirectManagerCache::get_cach_key($cach_id);

			if($status==self::REDIRECT_STATUS_ACTIVE){
				if($redirect_rule_status_code==TeamOneRedirectManagerRouter::URL_REWRITE_CODE){
					
					if ( ! is_array( $cache_driver ) || ! isset( $cache_driver['error'] ) ) {
						// 获取缓存数据
						$page_data = TeamOneRedirectManagerRouter::get_page_data($redirect_rule_to);
						// var_dump($page_data);exit;
						$timeout = TeamOneRedirectManagerCache::get_time_out();
						if(!empty($cache_key)){
							$cache_driver->set($cache_key, $page_data,$timeout);
						}
					}
				}else{
					//更换规则清空对应数据
					if(!empty($cache_key)){
						$cache_driver->del( $cache_key );
					}
				}
			}elseif($status==self::REDIRECT_STATUS_INACTIVE){
				//更换状态清空对应数据
				if(!empty($cache_key)){
					$cache_driver->del( $cache_key );
				}
			}


			if ( ! is_array( $cache_driver ) || ! isset( $cache_driver['error'] ) ) {
				$redirects_cache_key = TeamOneRedirectManagerCache::get_cach_key( TeamOneRedirectManagerRouter::REDIRECTS_DATA_KEY );
				$cache_driver->del( $redirects_cache_key );
			}
			
			TeamOneRedirectManagerRouter::get_redirects();
			TeamOneRedirectNgnix::create_ngnix_file();
		}
	}

	/*
		* function to sanitize POST data
		*/
	public static function redirect_sanitize_text( $key, $is_not_snippet = true ) {
		if ( ! empty( $_POST['data'][ $key ] ) ) {
			$post_data = stripslashes_deep( $_POST['data'][ $key ] );
			if ( $is_not_snippet ) {
				$post_data = sanitize_text_field( $post_data );
			} else {
				$post_data = htmlentities( $post_data );
			}

			return $post_data;
		}

		return '';
	}

	/*
	* load redirection Javascript code
	*/
	public static function redirect( $url = '' ) {
		// Register the script
		wp_register_script( 'redirection',
			plugin_dir_url( 'th-team-one-redirect-manager/th_team_one_redirect_manager.php' ) . 'assets/js/location.js' );

		// Localize the script with new data
		$translation_array = array( 'url' => $url );
		wp_localize_script( 'redirection', 'redirect_location', $translation_array );

		// Enqueued script with localized data.
		wp_enqueue_script( 'redirection' );
	}


	/*
		* function for submenu "Update Redirect" page
		*/
	public static function redirect_update() {
		// check user capabilities
		$nnr_redirect_can_edit = current_user_can( 'manage_options' );

		if ( ! $nnr_redirect_can_edit ) {
			echo 'Sorry, you do not have access to this page.';

			return false;
		}

		if ( empty( $_GET['id'] ) ) {
			die( 'Missing ID parameter.' );
		}
		$id = absint( $_GET['id'] );

		global $wpdb;
		$list_obj   = new TeamOneRedirectManagerPostList();
		$table_name = $list_obj->get_table();

		//selecting value to update
		$nnr_redirect_data = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM `{$table_name}` WHERE id=%s", $id )
		);
		foreach ( $nnr_redirect_data as $s ) {
			$redirect_rule_from_regex  = $s->redirect_rule_from_regex;
			$redirect_rule_from        = $s->redirect_rule_from;
			$redirect_rule_to          = $s->redirect_rule_to;
			$redirect_rule_status_code = $s->redirect_rule_status_code;
			$status                    = $s->status;
			$redirect_rule_notes       = $s->redirect_rule_notes;
			$redirect_name             = $s->redirect_name;
		}
		// escape for html output
		$redirect_rule_from_regex  = esc_html( $redirect_rule_from_regex );
		$redirect_rule_from        = esc_html( $redirect_rule_from );
		$redirect_rule_to          = esc_html( $redirect_rule_to );
		$redirect_rule_status_code = esc_html( $redirect_rule_status_code );
		$status                    = esc_html( $status );
		$redirect_rule_notes       = esc_html( $redirect_rule_notes );
		$redirect_name             = esc_html( $redirect_name );

		// Notify hfcm-add-edit.php to make necesary changes for update
		$update = true;
		$nnr_redirect_status_array = self::redirect_status();
		include_once plugin_dir_path( __FILE__ ) . '/redirect-add-edit.php';
	}

	/*
   * function to get load tools page
   */
	public static function redirect_tools() {
		global $wpdb;
		$list_obj          = new TeamOneRedirectManagerPostList();
		$table_name        = $list_obj->get_table();
		$nnr_redirect_data = $wpdb->get_results( "SELECT * from `{$table_name}` where status <> '0' ");

		include_once plugin_dir_path( __FILE__ ) . '/redirect-tools.php';
	}

	/*
	* function to export redirect
	*/
	public static function team_one_redirect_export() {
		global $wpdb;
		$list_obj   = new TeamOneRedirectManagerPostList();
		$table_name = $list_obj->get_table();
		// var_dump($table_name);exit;
		if ( ! empty( $_POST['nnr_value'] ) && ! empty( $_POST['action'] ) && ( $_POST['action'] == "team_one_redirect_download" ) && check_admin_referer( 'redirect-nonce' ) ) {
			$nnr_redirect_comma_separated = "";
			foreach ( $_POST['nnr_value'] as $nnr_redirect_key => $nnr_redirect_value ) {
				$nnr_redirect_value = str_replace( "redirect_", "", sanitize_text_field( $nnr_redirect_value ) );
				$nnr_redirect_value = absint( $nnr_redirect_value );
				if ( ! empty( $nnr_redirect_value ) ) {
					if ( empty( $nnr_redirect_comma_separated ) ) {
						$nnr_redirect_comma_separated .= $nnr_redirect_value;
					} else {
						$nnr_redirect_comma_separated .= "," . $nnr_redirect_value;
					}
				}
			}
			if ( ! empty( $nnr_redirect_comma_separated ) ) {
				$nnr_redirect = $wpdb->get_results(
					"SELECT * FROM `{$table_name}` WHERE id IN (" . $nnr_redirect_comma_separated . ") AND status <> 0"
				);

				if ( ! empty( $nnr_redirect ) ) {
					$nnr_redirect_export = array( "title" => "Team One Redirect Manager" );

					foreach ( $nnr_redirect as $nnr_key => $nnr_item ) {
						unset( $nnr_item->id );
						unset( $nnr_item->createtime );
						unset( $nnr_item->updatetime );
						unset( $nnr_item->deletetime );
						$nnr_redirect_export['redirect'][ $nnr_key ] = $nnr_item;
					}
					$file_name = 'team-one-redirect-export-' . date( 'Y-m-d' ) . '.json';
					header( "Content-Description: File Transfer" );
					header( "Content-Disposition: attachment; filename={$file_name}" );
					header( "Content-Type: application/json; charset=utf-8" );
					echo json_encode( $nnr_redirect_export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
				}
			}
			die;
		}
	}


	/*
	* function to import snippets
	*/
	public static function team_one_redirect_import() {
		date_default_timezone_set('Asia/Shanghai');
		if ( ! empty( $_FILES['team_one_nnr_redirect_import_file']['tmp_name'] ) && check_admin_referer( 'redirect-nonce' ) ) {
			if ( ! empty( $_FILES['team_one_nnr_redirect_import_file']['type'] ) && $_FILES['team_one_nnr_redirect_import_file']['type'] != "application/json" ) {
				?>
                <div class="notice hfcm-warning-notice notice-warning">
                    Please upload a valid import file
                </div>
				<?php
				return;
			}

			global $wpdb;
			$list_obj   = new TeamOneRedirectManagerPostList();
			$table_name = $list_obj->get_table();

			$nnr_redirect_json = file_get_contents( $_FILES['team_one_nnr_redirect_import_file']['tmp_name'] );
			$nnr_redirect_data = json_decode( $nnr_redirect_json );

			if ( empty( $nnr_redirect_data->title ) || ( ! empty( $nnr_redirect_data->title ) && $nnr_redirect_data->title != "Team One Redirect Manager" ) ) {
				?>
                <div class="notice hfcm-warning-notice notice-warning">
                    Please upload a valid import file
                </div>
				<?php
				return;
			}

			$nnr_non_script_redirect = 1;
			foreach ( $nnr_redirect_data->redirect as $nnr_hfcm_key => $nnr_redirect_value ) {
				$nnr_redirect_value = (array) $nnr_redirect_value;


				$nnr_hfcm_sanitizes_snippet = [];
				$nnr_hfcm_keys              = array(
					"redirect_name",
					"redirect_rule_from",
					"redirect_rule_to",
					"redirect_rule_status_code",
					"redirect_rule_from_regex",
					"redirect_rule_notes",
					"status",
					'createtime',
				);
				foreach ( $nnr_redirect_value as $nnr_key => $nnr_item ) {
					$nnr_key = sanitize_text_field( $nnr_key );
					if ( in_array( $nnr_key, $nnr_hfcm_keys ) ) {
						if ( $nnr_key != "redirect_rule_notes" ) {
							$nnr_item = sanitize_text_field( $nnr_item );
						}
						if ( $nnr_key == "createtime" ) {
							$nnr_item = current_time('timestamp');
						}
						$nnr_hfcm_sanitizes_data[ $nnr_key ] = $nnr_item;
					}
				}
				$nnr_hfcm_sanitizes_data['status'] = '2';
				$nnr_hfcm_sanitizes_data['createtime'] = current_time('timestamp');
				$wpdb->insert(
					$table_name, $nnr_hfcm_sanitizes_data, array(
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
					)
				);
			}

			self::redirect( admin_url( 'admin.php?page=th_team_one_redirect_manager&import=' . $nnr_non_script_redirect ) );
		}
	}

	/**
	 * Teamone
	 * Redirect Set
	 */
	public static function redirect_set() {
		$nnr_redirect_can_edit = current_user_can( 'manage_options' );

		if ( ! $nnr_redirect_can_edit ) {
			echo 'Sorry, you do not have access to this page.';

			return false;
		}
		global $wpdb;
		$rule_domain        = '';
		$host_txt           = '';
		$redis_port         = '';
		$redis_password     = '';
		$redis_domain_key   = '';
		$redirect_module    = '';
		$id                 = '';
		$remodule_file_path = '';
		$update             = false;
		$list_obj           = new TeamOneRedirectManagerPostList();
		$table_name         = $list_obj->get_redirect_setting_table();

		//selecting value to update
		$nnr_redirect_data = $wpdb->get_results(
			"SELECT * FROM `{$table_name}`"
		);
		if ( $nnr_redirect_data ) {
			foreach ( $nnr_redirect_data as $s ) {
				$id                 = $s->id;
				$rule_domain        = $s->rule_domain;
				$host_txt           = $s->host_txt;
				$redis_port         = $s->redis_port;
				$redis_password     = $s->redis_password;
				$redis_domain_key   = $s->redis_domain_key;
				$redirect_module    = $s->redirect_module;
				$remodule_file_path = $s->remodule_file_path;
			}
			$update = true;
		}
		include_once plugin_dir_path( __FILE__ ) . '/redirect-setting.php';
	}

	public static function redirect_set_request() {
		// check user capabilities
		$nnr_redirect_can_edit = current_user_can( 'manage_options' );
		if ( ! $nnr_redirect_can_edit ) {
			echo 'Sorry, you do not have access to this page.';

			return false;
		}
		global $wpdb;
		$list_obj   = new TeamOneRedirectManagerPostList();
		$table_name = $list_obj->get_redirect_setting_table();

		if ( isset( $_POST['insert'] ) ) {
			// Check nonce
			check_admin_referer( 'create-set-redirect' );
		} else {
			if ( empty( $_REQUEST['id'] ) ) {
				die( 'Missing ID parameter.' );
			}
			$id = absint( $_REQUEST['id'] );
		}
		if ( isset( $_POST['update'] ) ) {
			// Check nonce
			check_admin_referer( 'update-set-redirect_' . $id );
		}

		$rule_domain    	= self::redirect_sanitize_text( 'rule_domain', false );
		$host_txt       	= self::redirect_sanitize_text( 'host_txt', false );
		$redis_port      	= self::redirect_sanitize_text( 'redis_port', false );
		$redis_password     = self::redirect_sanitize_text( 'redis_password', false );
		$redis_domain_key   = self::redirect_sanitize_text( 'redis_domain_key', false );
		$redirect_module    = self::redirect_sanitize_text( 'redirect_module', false );
		$remodule_file_path = self::redirect_sanitize_text( 'remodule_file_path', false );

		TeamOneRedirectManagerCache::delErrorLog();
		TeamOneRedirectManagerCache::del_redirect_setting();

		if ( isset( $id ) ) {
			$wpdb->update(
				$table_name, //table
				// Data
				array(
					'rule_domain'        => $rule_domain,
					'host_txt'           => $host_txt,
					'redis_port'         => $redis_port,
					'redis_password'     => $redis_password,
					'redis_domain_key'   => $redis_domain_key,
					'redirect_module'    => $redirect_module,
					'remodule_file_path' => $remodule_file_path,
					'updatetime'         => current_time('timestamp'),
				),
				// Where
				array( 'id' => $id ),
				// Data format
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				),
				// Where format
				array( '%s' )
			);
			TeamOneRedirectNgnix::create_ngnix_file();
			self::redirect( admin_url( 'admin.php?page=team-one-redirect-set&id=' . $id ) );
		} else {
			// Create
			$wpdb->insert(
				$table_name, //table
				array(
					'rule_domain'     	 => $rule_domain,
					'host_txt'        	 => $host_txt,
					'redis_port'      	 => $redis_port,
					'redis_password'  	 => $redis_password,
					'redis_domain_key'	 =>$redis_domain_key,
					'redirect_module'	 => $redirect_module,
					'remodule_file_path' => $remodule_file_path,
					'createtime'         => current_time('timestamp'),
					'updatetime'    	 => current_time('timestamp'),
				), array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);
			$lastid = $wpdb->insert_id;
			TeamOneRedirectNgnix::create_ngnix_file();
			self::redirect( admin_url( 'admin.php?page=team-one-redirect-set&id=' . $lastid ) );
		}
	}
	/**
	 * 
	 * return array
	 */
	public static function redirect_status() {
		$status = array(
			self::REDIRECT_STATUS_ACTIVE   => 'Active',
			self::REDIRECT_STATUS_INACTIVE   =>'Inactive'
		);
		return $status;
	}
}
