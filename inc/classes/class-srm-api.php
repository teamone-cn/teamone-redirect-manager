<?php
/**
 * Handle redirection
 *
 * @package safe-redirect-manager
 */

namespace TH\TeamOne\Redirect;

defined( 'ABSPATH' ) || exit;
use TH\TeamOne\Redirect\TeamOneRedirectManagerRouter as TeamOneRedirectManagerRouter;
use TH\TeamOne\Redirect\Status as Status;
use TH\TeamOne\Redirect\TeamOneRedirectManager as TeamOneRedirectManager;


/**
 * Redirect Router Class
 */
class TeamOneRedirectApi {

     // 处理API请求的回调函数
     public static function redirect_api_callback($request) {

        // 定义规则
        $rule = array(
            'nonce'=>array('require'),
            'timestamp'=>array('require'),
            'signature'=>array('require')
        );
        // 开始验证
        $param_arr = array('nonce','timestamp','signature');

        // 验证参数
        $validata = self::check_validata($param_arr,$rule,$request);

        if ($validata['code']==Status::CODE_UNAUTHORIZED) {
            return $validata;   
        }

        // 签名验证
        $signature_data = self::check_signature($request);
        if($signature_data['code']==Status::CODE_UNAUTHORIZED){
            return $signature_data;   
        }

        // 获取数据返回
        $resource_data = TeamOneRedirectManager::get_all_resource_data();
        if($resource_data){
            $result_data = array('code'=>Status::CODE_OK,'result'=>$resource_data);
        }else{
            $result_data = array('code'=>Status::CODE_OK,'result'=>'');
        }
        return $result_data;
    }

    /**
     * 验证签名
     * @param array $data
     * @return bool|string
     */
    public static function check_signature($data){

        $secret_key = TeamOneRedirectManagerRouter::API_SECRET_KEY;
        $nonce = $data['nonce'];
        $timestamp = $data['timestamp'];
        $signature = $data['signature'];

        $status_txt = '';
        $status = Status::CODE_OK;
        // 验证签名
        $expected_signature = md5($nonce . $timestamp . $secret_key);
        if ($signature != $expected_signature) {
            $status_txt = '鉴权失败';
            $status = Status::CODE_UNAUTHORIZED;
        }

        // 验证时间戳
        $now = time();
        if (abs($now - $timestamp) > 300) { // 时间戳有效期为 300 秒
            $status_txt = '接口过期';
            $status = Status::CODE_UNAUTHORIZED;
        }
        $result = array('code' => $status, 'result' => $status_txt);

        return $result;
    }


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

        // 注册API端点
        add_action('template_redirect', array($this,'register_redirect_api_endpoints'));

	}


    /*
    * 注册端点
    */
    public  function register_redirect_api_endpoints() {
        $url = $_SERVER['REQUEST_URI'] ?? '';
        switch ($url) {
            case '/redirect/v1/getRedirect':
                $data = self::redirect_api_callback($_POST);
                wp_send_json($data);
                break;
            case '/redirect/v1/getRedirectData':
                $data = self::redirect_data_api_callback($_POST);
                wp_send_json($data);
                break;
        }
    }
    


    /**
     * 验证多个字段规则
     * 需要验证的字段，启用某条规则，该字段的限定值，它的提示信息
     * @access protected
     * @param string $field 字段名
     * @param mixed  $rules 验证规则
     * @param mixed  $value 字段值
     * @return mixed
     */
    public static function check_validata($field = array(),$rules,$value,$message ='')
    {
        foreach($field as $k=>$v){
            if(isset($value[$v])){
                if(isset($rules[$v])){
                    $result = self::is($rules[$v][0],$value[$v]);
                    if($result ===false){
                        $result  = array('code'=> Status::CODE_UNAUTHORIZED,'result'=> $v.'不能为空');
                        return $result;
                    }
                }
            }else{
                $result  = array('code'=> Status::CODE_UNAUTHORIZED,'result'=> $v.'不能为空');
                return $result;
            } 
        }
        $result  = array('code'=> Status::CODE_OK,'result'=> '授权成功');
        return $result;
    }

    /**
     * 验证字段值是否为有效格式
     * @access public
     * @param string $rule  验证规则
     * @param mixed  $value 字段值
     * @return bool
     */
    public static function is(string $rule,$value)
    {
        $result = false;
        switch ($rule){
            case 'require':
                // 必须有参数
                if(!empty($value)){
                    $result = true;
                }
                break;
            default:
                break;
        }
        return $result;
    }

    // 处理API请求的回调函数
    public static function redirect_data_api_callback($request) {

        // 定义规则
        $rule = array(
            'nonce'=>array('require'),
            'timestamp'=>array('require'),
            'signature'=>array('require'),
            'table_name'=>array('require'),
        );
        // 开始验证
        $param_arr = array('nonce','timestamp','signature','table_name');

        // 验证参数
        $validata = self::check_validata($param_arr,$rule,$request);

        if ($validata['code']==Status::CODE_UNAUTHORIZED) {
            return $validata;   
        }

        // 签名验证
        $signature_data = self::check_signature($request);
        if($signature_data['code']==Status::CODE_UNAUTHORIZED){
            return $signature_data;   
        }
        $table_name = $request['table_name'];
        $current_page = (int) ( $request['current_page'] ?? 1 );
        $per_page     = (int) ( $request['per_page'] ?? 10 );
        // 获取数据返回
        $resource_data = TeamOneRedirectManager::get_redirect_data($table_name,$per_page, $current_page);
        if($resource_data['redirect_data']){
            $result_data = array('code'=>Status::CODE_OK,'result'=>$resource_data);
        }else{
            $result_data = array('code'=>Status::CODE_OK,'result'=>'');
        }
        return $result_data;
    }
}