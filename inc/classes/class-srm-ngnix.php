<?php
/**
 * Handle redirection
 *
 * @package safe-redirect-manager
 */
namespace TH\TeamOne\Redirect;
defined( 'ABSPATH' ) || exit;

class TeamOneRedirectNgnix {

    const NGNIX_FILE_PATH = '/redirect-ngnix/redirect.conf';

    /**
	 * 单例模式
	 * @return false|TeamOneRedirectNgnix
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

    /**
	 * 创建文件
	 *
	 * @param string $path 待创建文件目录
	 * @param string $content 待创建文件内容
	 * @return bool 返回创建状态状态
	*/
    public static function create_ngnix_file($path = ''){

        $content = '';
        $wordpress_root = ABSPATH;
        $path = '';
       
        // 是否开启ngnix模式
        $redirectset_data = TeamOneRedirectManagerRouter::get_redirect_setting();
        
        // 获取拼凑重定向规则数据
        if($redirectset_data['redirect_module']=='2'){
            $redirects_data = TeamOneRedirectManagerRouter::get_redirects();
            $content = self::generateRewriteRules($redirects_data);
        }
        
        $path = $wordpress_root . $redirectset_data['remodule_file_path'];
        TeamOneRedirectFile::create_file($path, $content);

    }

    /**
	 * 拼接ngnix伪静态数据
	 *
	 * @param array $data 重定向规则数据
	 * @return bool 返回创建状态状态
	*/
    public static  function generateRewriteRules($data) {
        $rules = '';
        foreach ($data as $item) {
            if($item['status_code']!=TeamOneRedirectManagerRouter::URL_REWRITE_CODE){
                $redirectFrom = $item['redirect_from'];
                $redirectTo = $item['redirect_to'];
                $rules .= "rewrite $redirectFrom $redirectTo permanent;\n";
            }
           
        }
        return $rules;
    }
}