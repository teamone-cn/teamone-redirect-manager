<?php

namespace TH\TeamOne\Redirect;

defined( 'ABSPATH' ) || exit;

use TH\TeamOne\Redirect\TeamOneRedirectManagerPostTable as TeamOneRedirectManagerPostTable;
use TH\TeamOne\Redirect\TeamOneRedirectManager as TeamOneRedirectManager;
use TH\TeamOne\Redirect\TeamOneRedirectManagerCache as TeamOneRedirectManagerCache;
use TH\TeamOne\Redirect\TeamOneRedirectManagerRouter as TeamOneRedirectManagerRouter;

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

use WP_List_Table;

class TeamOneRedirectManagerPostList extends WP_List_Table {

	private static $instance;

	const DELETE_NONCE = 'team_one_redirect_manager_delete';


	/**
	 * Constructor.
	 *
	 * @param array $args An associative array of arguments.
	 *
	 * @see WP_List_Table::__construct() for more information on default arguments.
	 *
	 * @global int $post_id
	 *
	 * @since 3.1.0
	 *
	 */
	public function __construct( $args = array() ) {
		parent::__construct(
			array(
				'plural'   => 'team_one_redirect_manager',
				'singular' => 'team_one_redirect_manager',
				'ajax'     => false,
			)
		);
	}

	/**
	 * 单例模式
	 * @return TeamOneRedirectManagerPostList
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * 获取数据表名称
	 * @return string
	 */
	public static function get_table() {
		global $wpdb;

		return $wpdb->prefix . TeamOneRedirectManagerPostTable::MANAGER_TABLE;
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
	 * 状态 status
	 *
	 * @param false $status
	 *
	 * @return string|string[]
	 */
	public static function get_status( $status = false ) {
		$arr = array(
			'0' => '已删除',
			'1' => '启用',
			'2' => '禁用',
		);

		return $status ? ( $arr[ $status ] ?? '' ) : $arr;
	}


	/**
	 * 权限判断
	 * @return bool
	 */
	public function ajax_user_can() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * 插件语言包标记
	 * @return string
	 */
	public static function plugin_text_domain() {
		return TeamOneRedirectManager::PLUGIN_TEXT_DOMAIN;
	}

	/**
	 * 列表表头字段
	 */
	public function get_columns() {
		$columns = array(
			'cb'                        => '<input type="checkbox" />',
			'id'                        => esc_html__( 'ID', self::plugin_text_domain() ),
			'status'                    => esc_html__( 'Status', self::plugin_text_domain() ), //'状态',
			'redirect_name'          => esc_html__( 'Redirect Name', self::plugin_text_domain() ), //'重定向目标',
			'redirect_rule_from'        => esc_html__( 'Redirect From', self::plugin_text_domain() ), //'重定向规则',
			'redirect_rule_to'          => esc_html__( 'Redirect To', self::plugin_text_domain() ), //'重定向目标',
			'redirect_rule_status_code' => esc_html__( 'HTTP Status Code', self::plugin_text_domain() ), //'重定向状态码',
			'createtime'                => esc_html__( 'Date', self::plugin_text_domain() ), //'创建时间',
			'operate'                   => esc_html__( 'Operate', self::plugin_text_domain() ), //'操作',
		);

		return $columns;
	}

	/**
	 * 列表字段
	 *
	 * @param array|object $item
	 * @param string $column_name
	 *
	 * @return mixed|string|void
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'status':
				if ( '2' === $item[ $column_name ] ) {
					return '<div class="nnr-switch">
                        <label for="nnr-round-toggle' . esc_attr( $item['id'] ) . '">OFF</label>
                        <input id="nnr-round-toggle' . esc_attr(
							$item['id']
						) . '" class="round-toggle round-toggle-round-flat" type="checkbox" data-id="' . esc_attr(
						       $item['id']
					       ) . '" />
                        <label for="nnr-round-toggle' . esc_attr( $item['id'] ) . '"></label>
                        <label for="nnr-round-toggle' . esc_attr( $item['id'] ) . '">ON</label>
                    </div>
                    ';
				} elseif ( '1' === $item[ $column_name ] ) {
					return '<div class="nnr-switch">
                        <label for="nnr-round-toggle' . esc_attr( $item['id'] ) . '">OFF</label>
                        <input id="nnr-round-toggle' . esc_attr(
							$item['id']
						) . '" class="round-toggle round-toggle-round-flat" type="checkbox" data-id="' . esc_attr(
						       $item['id']
					       ) . '" checked="checked" />
                        <label for="nnr-round-toggle' . esc_attr( $item['id'] ) . '"></label>
                        <label for="nnr-round-toggle' . esc_attr( $item['id'] ) . '">ON</label>
                    </div>
                    ';
				} else {
					return esc_html( $item[ $column_name ] );
				}
			case 'createtime':
				return date( 'Y-m-d H:i:s', $item[ $column_name ] ?? $_SERVER['REQUEST_TIME'] );
			case 'operate':
				$delete_nonce = wp_create_nonce( self::DELETE_NONCE );
				$edit_page    = 'team-one-redirect-update';
				$delete_page  = sanitize_text_field( $_GET['page'] );

				$actions = array(
					'edit'   => sprintf(
						'<span class="edit"><a href="?page=%s&message=1&id=%s">' . esc_html__(
							'Edit',
							self::plugin_text_domain()
						) . '</a></span>  | ',
						esc_attr( $edit_page ),
						absint( $item['id'] )
					),
					'delete' => sprintf(
						'<span class="trash"><a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . esc_html__(
							'Delete',
							self::plugin_text_domain()
						) . '</a></span>',
						$delete_page,
						'delete',
						absint( $item['id'] ),
						$delete_nonce
					),
				);

				return '<div class="row-actions" style="left: 0;">' . $actions['edit'] . $actions['delete'] . '</div>';
			default:
				return $item[ $column_name ] ?? '';
		}
	}


	/**
	 * 列表隐藏字段
	 * @return array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * 列表排序字段
	 * @return array[]
	 */
	public function get_sortable_columns() {
		return array(
			'id'         => array( 'id', false ),     //true means it's already sorted
			'createtime' => array( 'createtime', false ),
		);
	}

	/**
	 * 列表操作多选按钮 checkbox
	 *
	 * @param array|object $item
	 *
	 * @return string|void
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="redirects[]" value="%s" />',
			$item['id']
		);
	}


	/**
	 * 列表为空时提示
	 */
	public function no_items() {
		esc_html_e( 'No Redirects available.', self::plugin_text_domain() );
	}

	/**
	 * 列表每页显示数量
	 *
	 * @param string $comment_status
	 *
	 * @return int
	 */
	public function get_per_page( $status = 'all' ) {
		$per_page = $this->get_items_per_page( "{$this->screen->id}_per_page" );

		/**
		 * Filters the number of redirects listed per page in the redirects list table.
		 *
		 * @param int $comments_per_page The number of redirects to list per page.
		 * @param string $comment_status The redirect status name. Default 'All'.
		 *
		 * @since 2.6.0
		 *
		 */
		return apply_filters( "{$this->screen->id}_per_page", $per_page, $status );
	}

	/**
	 * 列表批量操作类型
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-activate'   => esc_html__( 'Activate', self::plugin_text_domain() ),
			'bulk-deactivate' => esc_html__( 'Deactivate', self::plugin_text_domain() ),
			'bulk-delete'     => esc_html__( 'Remove', self::plugin_text_domain() ),
		);

		return $actions;
	}

	/**
	 * 列表操作方法
	 */
	public function process_bulk_action() {
		$page = sanitize_text_field( $_GET['page'] );

		//Detect when a bulk action is being triggered...
		if ( 'delete' === $this->current_action() ) {
			// In our file that handles the request, verify the nonce.
			$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );

			if ( ! wp_verify_nonce( $nonce, self::DELETE_NONCE ) ) {
				die( 'Go get a life script kiddies' );
			} else {
				if ( ! empty( $_GET['id'] ) ) {
					$id = absint( $_GET['id'] );
					if ( ! empty( $id ) ) {
						self::delete( $id );
					}
				}

				self::update_redirect_cache( $id );
				TeamOneRedirectNgnix::create_ngnix_file();
				return true;
			}
		}

		if ( isset( $_REQUEST['toggle'] ) && ! empty( $_REQUEST['togvalue'] ) ) {
			// Check nonce
			check_admin_referer( 'bulk-' . $this->_args['plural'], 'security' );

			$id     = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0;
			$action = '';

			if ( ! empty( $id ) ) {
				if ( 'on' === $_REQUEST['togvalue'] ) {
					$action = 'activate';
					self::activate( $id );
				} else {
					self::deactivate( $id );
				}
			}

			self::update_redirect_cache( $id, $action );
			TeamOneRedirectNgnix::create_ngnix_file();
			return true;
		}

		// If the delete bulk action is triggered
		if (
			( isset( $_POST['action'] ) && 'bulk-delete' === $_POST['action'] )
			|| ( isset( $_POST['action2'] ) && 'bulk-delete' === $_POST['action2'] )
		) {
			// Check nonce
			check_admin_referer( 'bulk-' . $this->_args['plural'] );

			$delete_ids = $_POST['redirects'] ?? [];

			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				$id = absint( $id );
				if ( ! empty( $id ) && is_int( $id ) ) {
					self::delete( $id );
				}
			}

			self::update_redirect_cache( $delete_ids );
			TeamOneRedirectNgnix::create_ngnix_file();
			return true;
		} elseif (
			( isset( $_POST['action'] ) && 'bulk-activate' === $_POST['action'] )
			|| ( isset( $_POST['action2'] ) && 'bulk-activate' === $_POST['action2'] )
		) {
			// Check nonce
			check_admin_referer( 'bulk-' . $this->_args['plural'] );

			$activate_ids = $_POST['redirects'] ?? [];

			// loop over the array of record IDs and activate them
			foreach ( $activate_ids as $id ) {
				$id = absint( $id );
				if ( ! empty( $id ) && is_int( $id ) ) {
					self::activate( $id );
				}
			}

			self::update_redirect_cache( $activate_ids, 'activate' );
			TeamOneRedirectNgnix::create_ngnix_file();
			return true;
		} elseif (
			( isset( $_POST['action'] ) && 'bulk-deactivate' === $_POST['action'] )
			|| ( isset( $_POST['action2'] ) && 'bulk-deactivate' === $_POST['action2'] )
		) {
			// Check nonce
			check_admin_referer( 'bulk-' . $this->_args['plural'] );

			$deactivate_ids = $_POST['redirects'] ?? [];

			// loop over the array of record IDs and deactivate them
			foreach ( $deactivate_ids as $id ) {
				$id = absint( $id );
				if ( ! empty( $id ) && is_int( $id ) ) {
					self::deactivate( $id );
				}
			}

			self::update_redirect_cache( $deactivate_ids );
			TeamOneRedirectNgnix::create_ngnix_file();
			return true;
		}
	}


	/**
	 * 搜索框
	 *
	 * @param string $text The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 *
	 * @since 3.1.0
	 */
	public function search_box( $text, $input_id ) {
		$input_id     = $input_id . '-search-input';
		$search_query = isset( $_REQUEST['s'] ) ? esc_sql( wp_unslash( $_REQUEST['s'] ) ) : '';
		$output       = '<div class="search-box">';
		$output       .= '<label class="screen-reader-text" for="' . esc_attr( $input_id ) . '">' . esc_html(
				$text
			) . ':</label>';
		$output       .= '<input type="search" id="' . esc_attr( $input_id ) . '" name="s" value="' . esc_attr(
				$search_query
			) . '"/>';
		$output       .= get_submit_button( $text, 'button', '', false, array( 'id' => 'search-submit' ) ) . '</div>';

		return $output;
	}

	/**
	 * 在表上方和下方添加筛选器和额外操作
	 *
	 * @param string $which Are the actions displayed on the table top or bottom
	 */
	public function extra_tablenav( $which ) {
		$html = '';
		if ( 'top' === $which ) {
			$query         = isset( $_REQUEST['redirect_code'] ) ? sanitize_text_field( $_REQUEST['redirect_code'] ) : '';
			$redirect_code = teamone_redirect_srm_get_valid_status_codes_data();

			$html .= '<div class="alignleft actions">';
			$html .= '<select name="redirect_code">';
			$html .= '<option value="">' . esc_html__( 'All HTTP Status Code',
					self::plugin_text_domain() ) . '</option>';

			foreach ( $redirect_code as $key => $code ) {
				if ( $key == $query ) {
					$html .= '<option value="' . esc_attr( $key ) . '" selected>' . esc_html(
							$key . ' ' . $code
						) . '</option>';
				} else {
					$html .= '<option value="' . esc_attr( $key ) . '">' . esc_html( $key . ' ' . $code ) . '</option>';
				}
			}

			$html .= '</select>';
			$html .= get_submit_button( __( 'Filter', self::plugin_text_domain() ), 'button', 'filter_action', false );
			$html .= '</div>';

			// Searchbox
			$html .= '<div class="alignleft">';
			$html .= $this->search_box( __( 'Search Redirects', self::plugin_text_domain() ), 'search_id' );
			$html .= '</div>';
		}

		echo $html;
	}

	/**
	 * 列表根据状态（status）筛选返回总数
	 *
	 * @param string $customvar
	 *
	 * @return string|null
	 */
	public function record_count( $customvar = 'all' ) {
		global $wpdb;
		$table_name       = self::get_table();
		$sql              = "SELECT COUNT(*) FROM `{$table_name}`";
		$displayed_status = '';

		$customvar      = sanitize_text_field( strval( $customvar ) );
		$default_status = teamone_redirect_srm_var_to_string( self::get_status() );
		$status         = $default_status;
		if ( isset( $status[0] ) ) {
			unset( $status[0] );
		}

		if ( in_array( $customvar, $status ) ) {
			$displayed_status = "'" . esc_sql( $customvar ) . "'";
		} else {
			$displayed_status = "'" . implode( "', '", $status ) . "'";
		}
		if ( ! empty( $displayed_status ) ) {
			$sql .= " WHERE status IN ( $displayed_status )";
		}

		return $wpdb->get_var( $sql );
	}

	/**
	 * 列表根据状态（status）筛选器
	 * @return array
	 */
	public function get_views() {
		$page    = sanitize_text_field( $_GET['page'] );
		$views   = array();
		$current = 'all';
		if ( ! empty( $_GET['status'] ) ) {
			$current = sanitize_text_field( $_GET['status'] );
		}

		//All link
		$class        = 'all' === $current ? 'current' : '';
		$all_url      = sprintf( '?page=%s', $page );
		$views['all'] = '<a href="' . esc_html( $all_url ) . '" class="' . esc_html( $class ) . '">' . esc_html__(
				'All',
				'header-footer-code-manager'
			) . ' (' . esc_html__( $this->record_count() ) . ')</a>';

		//Foo link
		$foo_url         = sprintf( '?page=%s&status=1', $page );
		$class           = ( '1' === $current ? 'current' : '' );
		$views['active'] = '<a href="' . esc_html( $foo_url ) . '" class="' . esc_html( $class ) . '">' . esc_html__(
				'Active',
				'header-footer-code-manager'
			) . ' (' . esc_html__( $this->record_count( '1' ) ) . ')</a>';

		//Bar link
		$bar_url           = sprintf( '?page=%s&status=2', $page );
		$class             = ( '2' === $current ? 'current' : '' );
		$views['inactive'] = '<a href="' . esc_html( $bar_url ) . '" class="' . esc_html( $class ) . '">' . esc_html__(
				'Inactive',
				'header-footer-code-manager'
			) . ' (' . esc_html__( $this->record_count( '2' ) ) . ')</a>';

		return $views;
	}

	/**
	 * 组装列表分页查询功能
	 */
	public function prepare_items() {
		global $wpdb;

		// Process bulk action.
		$this->process_bulk_action();

		$default_status = self::get_status();
		$default_status = teamone_redirect_srm_var_to_string( $default_status );
		$status         = $default_status;
		if ( isset( $status[0] ) ) {
			unset( $status[0] );
		}
		$default_redirect_code = teamone_redirect_srm_get_valid_status_codes_data();
		$default_redirect_code = teamone_redirect_srm_var_to_string( $default_redirect_code );

		$displayed_status        = isset( $_REQUEST['status'] ) && in_array(
			$_REQUEST['status'],
			$default_status
		) ? "'" . esc_sql( $_REQUEST['status'] ) . "'" : "'" . implode( "', '", $status ) . "'";
		$displayed_redirect_code = isset( $_REQUEST['redirect_code'] ) && in_array(
			$_REQUEST['redirect_code'],
			$default_redirect_code
		) ? "'" . esc_sql( $_REQUEST['redirect_code'] ) . "'" : "'" . implode(
				"', '",
				$default_redirect_code
			) . "'";

		// Get query variables
		$columns      = $this->get_columns();
		$hidden       = $this->get_hidden_columns();
		$sortable     = $this->get_sortable_columns();
		$current_page = $this->get_pagenum();

		$per_page = $this->get_per_page();
		$table    = self::get_table();

		// SQL query parameters
		$order        = ( isset( $_REQUEST['order'] ) && in_array(
				$_REQUEST['order'],
				array( 'asc', 'desc', 'ASC', 'DESC' )
			) ) ? sanitize_sql_orderby( $_REQUEST['order'] ) : 'desc';
		$orderby      = ( isset( $_REQUEST['orderby'] ) ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'ID';
		$offset       = ( $current_page - 1 ) * $per_page;
		$search_query = ( ! empty( $_REQUEST['s'] ) ) ? esc_sql( $_REQUEST['s'] ) : "";

		// Grab posts from database
		$sql_parts['start'] = "SELECT * FROM {$table} ";
		if ( $search_query ) {
			$sql_parts['where'] = "WHERE ( LOWER(redirect_rule_from) LIKE LOWER('%{$search_query}%') ";
			$sql_parts['where'] .= "OR LOWER(redirect_rule_to) LIKE LOWER('%{$search_query}%') ";
			$sql_parts['where'] .= "OR LOWER(redirect_name) LIKE LOWER('%{$search_query}%') ";
			$sql_parts['where'] .= " ) AND ((status IN ($displayed_status) AND redirect_rule_status_code IN ($displayed_redirect_code)) ) ";
		} else {
			$sql_parts['where'] = "WHERE ( (status IN ($displayed_status) AND redirect_rule_status_code IN ($displayed_redirect_code)) ) ";
		}

		$sql_parts['end'] = "ORDER BY {$orderby} {$order}";

		// Prepare the SQL query
		$sql_query = implode( "", $sql_parts );

		// Count items
		$count_query = str_replace( 'SELECT *', 'SELECT COUNT(*)', $sql_query );
		$total_items = $wpdb->get_var( $count_query );

		// Pagination support
		$sql_query .= sprintf( " LIMIT %d, %d", $offset, $per_page );

		// Get items
		$sql_query = apply_filters(
			"{$this->screen->id}_filter_list_query",
			$sql_query,
			$this,
			$sql_parts,
		);
		$all_items = $wpdb->get_results( $sql_query, ARRAY_A );

		// Debug SQL query
		if ( isset( $_REQUEST['debug_editor_sql'] ) ) {
			$debug_txt = "<textarea style=\"width:100%;height:300px\">{$sql_query} \n\nOffset: {$offset} \nPage: {$current_page}\nPer page: {$per_page} \nTotal: {$total_items}</textarea>";
			wp_die( $debug_txt );
		}

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		) );

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $all_items;
		$this->views();
	}

	/**
	 * 删除重定向（软删除）
	 *
	 * @param int $id snippet ID
	 */
	public static function delete( $id ) {
		$id = (int) $id;
		if ( empty( $id ) ) {
			return;
		}

		global $wpdb;
		$table_name = self::get_table();
		$wpdb->update(
			$table_name,
			array(
				'status'     => '0',
				'updatetime' => $_SERVER['REQUEST_TIME'],
				'deletetime' => $_SERVER['REQUEST_TIME'],

			),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * 启用重定向
	 *
	 * @param int $id snippet ID
	 */
	public static function activate( $id ) {
		$id = (int) $id;
		if ( empty( $id ) ) {
			return;
		}

		global $wpdb;
		$table_name = self::get_table();

		$wpdb->update(
			$table_name,
			array(
				'status'     => '1',
				'updatetime' => $_SERVER['REQUEST_TIME'],
			),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * 禁用重定向
	 *
	 * @param int $id snippet ID
	 */
	public static function deactivate( $id ) {
		$id = (int) $id;
		if ( empty( $id ) ) {
			return;
		}

		global $wpdb;
		$table_name = self::get_table();

		$wpdb->update(
			$table_name,
			array(
				'status'     => '2',
				'updatetime' => $_SERVER['REQUEST_TIME'],
			),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * 更新重定向数据&重写页面缓存
	 *
	 * @param $id
	 * @param string $action
	 *
	 * @return false|void
	 * @throws \Exception
	 */
	public static function update_redirect_cache( $id, $action = '' ) {
		if ( ! $id ) {
			return false;
		}

		if ( ! is_array( $id ) ) {
			$id = (array) $id;
		}

		global $wpdb;
		$table_name = self::get_table();

		$cache_driver = TeamOneRedirectManagerCache::getInstance( 'redis' );

		$redirects_cache_key = TeamOneRedirectManagerCache::get_cach_key( TeamOneRedirectManagerRouter::REDIRECTS_DATA_KEY );
		foreach ( $id as $item ) {
			$page_cache_key = TeamOneRedirectManagerCache::get_cach_key( $item );
			$cache_driver->del( $page_cache_key );

			if ( ! empty( $action ) && $action == 'activate' ) {
				$redirect = $wpdb->get_row( $wpdb->prepare( "SELECT id ID,redirect_rule_to redirect_to,redirect_rule_status_code status_code FROM {$table_name} WHERE id = %d",
					$item ),
					ARRAY_A );

				if ( ! empty( $redirect ) ) {
					$status_code = $redirect['status_code'] ?? 0;
					$redirect_to = $redirect['redirect_to'] ?? '';
					if ( $status_code == '3001' && ! empty( $redirect_to ) ) {
						$page_data = TeamOneRedirectManagerRouter::get_page_data( $redirect_to );
						if ( $page_data && ! empty( $page_data ) ) {
							$page_cache_key = TeamOneRedirectManagerCache::get_cach_key( $item );
							$cache_driver->set( $page_cache_key,
								$page_data,
								TeamOneRedirectManagerCache::get_time_out() );
						}
					}
				}
			}
		}

		$cache_driver->del( $redirects_cache_key );

		TeamOneRedirectManagerRouter::get_redirects();
	}

	/**
	 * 列表展示输出
	 */
	public function display_list_table() {
		global $wpdb;
		$errorLog = TeamOneRedirectManagerCache::getErrorLog();
		if ( isset( $errorLog['error'] ) && $errorLog['error'] === true ) {
			$error = $errorLog['msg'] ?? '';
			echo '<div class="notice notice-error is-dismissible"><p>' . $error . '</p></div>';
		}

		global $wpdb;
		?>
        <div class="wrap">
        <h1><?php
			echo esc_html__( 'Safe Redirect Manager', self::plugin_text_domain() ) ?>
            <a href="<?php
			echo admin_url( 'admin.php?page=team-one-redirect-create' ) ?>" class="page-title-action">
				<?php
				echo _x( 'Create Redirect Rule', 'redirect rule', self::plugin_text_domain() ); ?>
            </a>
        </h1>
		<?php
		$output = "<form id=\"table\" class=\"slugs-table\" method=\"post\">";
		// Bypass
		ob_start();

		$this->prepare_items();
		$this->display();
		$output .= ob_get_contents();

		ob_end_clean();

		$output .= "</form></div>";

		echo $output;
	}

	/**
	 * Handle displaying and saving screen options.
	 *
	 * @return void
	 * @since 2.0.0
	 * @access private
	 *
	 */
	public function screen_options() {
		$per_page_option = "{$this->screen->id}_per_page";

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Save screen options if the form has been submitted.
		if ( isset( $_POST['screen-options-apply'] ) ) {
			// Save posts per page option.
			if ( isset( $_POST['wp_screen_options']['value'] ) ) {
				update_user_option(
					get_current_user_id(),
					$per_page_option,
					sanitize_text_field( wp_unslash( $_POST['wp_screen_options']['value'] ) )
				);
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Add per page option to the screen options.
		add_screen_option(
			'per_page',
			array(
				'option' => $per_page_option,
			)
		);
	}
}
