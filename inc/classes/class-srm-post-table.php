<?php

namespace TH\TeamOne\Redirect;

defined( 'ABSPATH' ) || exit;

class TeamOneRedirectManagerPostTable {

	const Install_Table_Status = 'th_team_one_redirect_manager_tables_install';
	const Redirect_Version = 'th_team_one_redirect_manager_version';
	const MANAGER_TABLE = 'th_team_one_redirect_manager';
	const SETTING_TABLE = 'th_team_one_redirect_setting';
	const UPDATE_SET_FIELD = 'th_team_one_redirect_setting_version';

	/**
	 * Get tables.
	 */
	private static function get_sql() {
		global $wpdb;

		$charset_collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			$charset_collate = $wpdb->get_charset_collate();
		}

		//$base_prefix prefix
		$tables = "CREATE TABLE `{$wpdb->prefix}th_team_one_redirect_manager` (
                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                      `redirect_name` varchar(255) NOT NULL COMMENT '重定向名称',
                      `redirect_rule_from` varchar(255) NOT NULL COMMENT '重定向规则',
                      `redirect_rule_to` varchar(255) NOT NULL COMMENT '重定向目标',
                      `redirect_rule_status_code` varchar(10) NOT NULL COMMENT '重定向状态码',
                      `redirect_rule_from_regex` tinyint(1) NOT NULL DEFAULT '0' COMMENT '重定向启用正则',
                      `redirect_rule_notes` varchar(255) DEFAULT NULL COMMENT '重定向备注',
                      `status` enum('0','1','2') NOT NULL DEFAULT '2' COMMENT '状态',
                      `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
                      `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
                      `deletetime` bigint(16) DEFAULT NULL COMMENT '删除时间',
                      PRIMARY KEY (`id`),
                      KEY `INDEX` (`status`,`createtime`)
                    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT='重定向管理表';
                    
                    CREATE TABLE `{$wpdb->prefix}th_team_one_redirect_setting` (
                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
                      `rule_domain` varchar(100) NOT NULL COMMENT '路由规则域名',
                      `host_txt` varchar(100) NOT NULL COMMENT 'Redis 服务器的IP或主机名 Host',
                      `redis_port` varchar(255) NOT NULL COMMENT 'Redis 端口 Port',
                      `redis_password` varchar(100) NOT NULL COMMENT 'Redis 密码 PassWord',
                      `redirect_module` varchar(100) NOT NULL DEFAULT '1' COMMENT '重定向模式',
					  `remodule_file_path` varchar(255) NOT NULL COMMENT '重定向模式配置文件地址',
                      `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
                      `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT='重定向配置表';
                    ";
		return $tables;
	}

	/**
	 * Create tables.
	 */
	public static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( self::get_sql() );

		update_option( self::Install_Table_Status, '1.1' );
	}

	/**
	 * Install Table.
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		if ( get_option( self::Install_Table_Status ) == '1.1') {
			return;
		}
		
		self::create_tables();
	}

	/**
	 * Update Table.
	 */
	public static function update_sql(){

		if(get_option(self::Redirect_Version) == '1.0' ){
			return;
		}

		self::update_tables();
	}

	/**
	 * update_tables
	 */
	public static function update_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table_name = 'th_team_one_redirect_resource';
		// Check if the table exists
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
		// If the table doesn't exist, create it
		if (!$table_exists) {
			$charset_collate = '';
			if ( $wpdb->has_cap( 'collation' ) ) {
				$charset_collate = $wpdb->get_charset_collate();
			}

			//$base_prefix prefix
			$tables = "CREATE TABLE `th_team_one_redirect_resource` (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
				`resource_db_name` varchar(255) NOT NULL COMMENT '表名',
				`createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
				`updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
				`deletetime` bigint(16) DEFAULT NULL COMMENT '删除时间',
				`status` enum('0','1','2') NOT NULL DEFAULT '2' COMMENT '状态',
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci COMMENT='重定向插件集合表'";
			dbDelta( $tables );
			update_option( self::Redirect_Version, '1.0' );
		};
	}

	/**
	 * Update Table.
	 */
	public static function update_sql_setting(){

		if(get_option(self::UPDATE_SET_FIELD) == '1.0' ){
			return;
		}

		self::update_tables_setting();
	}

	/**
	 * update_tables
	 */
	public static function update_tables_setting() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table_name = self::SETTING_TABLE;
		$sql = "ALTER TABLE `{$wpdb->prefix}th_team_one_redirect_setting` ADD redis_domain_key varchar(255) NOT NULL COMMENT 'Redis域名缓存KEY'";
		$result = $wpdb->query($sql);
		if($result){
			update_option( self::UPDATE_SET_FIELD, '1.0' );
		}
	}
}

TeamOneRedirectManagerPostTable::install();
TeamOneRedirectManagerPostTable::update_sql();
TeamOneRedirectManagerPostTable::update_sql_setting();
