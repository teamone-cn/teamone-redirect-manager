<?php
/**
 * Handle redirection
 *
 * @package safe-redirect-manager
 */
namespace TH\TeamOne\Redirect;
defined( 'ABSPATH' ) || exit;

class TeamOneRedirectFile {

    /**
	 * 单例模式
	 * @return false|TeamOneRedirectFile
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}


	/**
	 * 检测目录是否存在
	 *
	 * @param string $path 待创建文件目录
	 * @param string $create 是否创建
	 * @return bool 返回创建状态状态
	*/
	public static function check_dir($path, $create = false) {
	    if (is_dir($path)) {
	        return true;
	    } elseif ($create) {
	        return self::create_dir($path);
	    }
	}
	
	/**
	 * 创建目录
	 *
	 * @param string $path 待创建文件目录
	 * @return bool 返回创建状态状态
	*/
	public static function  create_dir($path) {
	    if (! file_exists($path)) {
	        if (@mkdir($path, 0755, true)) {
	            return true;
	        }
	    }
	    return false;
	}
	
	/**
	 * 检查文件是否存在
	 *
	 * @param string $path 待创建文件路径
	 * @param string $create 是否创建
	 * @param string $content 文件内容
	 * @return bool 返回创建状态状态
	*/
	public static function  check_file($path, $create = false, $content = null) {
	    if (file_exists($path)) {
	        return true;
	    } elseif ($create) {
	        return self::create_file($path, $content);
	    }
	}

	/**
	 * 创建文件
	 *
	 * @param string $path 待创建文件路径
	 * @param string $content 创建文件内容
	 * @return bool 返回创建状态状态
	 */
	public static function create_file($path, $content = null) {
		// 搜索字符串中以.conf结尾的文件
		$extension = pathinfo($path, PATHINFO_EXTENSION);

		// 检查是否存在.conf文件
		if (empty($extension)) {
			die('无法创建目录，因为不存在.conf文件。');
		} 
	    if (file_exists($path)) {
	        @unlink($path);
	    }
	    self::check_dir(dirname($path), true);
	    $handle = fopen($path, 'w') or die('创建文件失败，请检查目录权限！');
	    fwrite($handle, $content);
	    return fclose($handle);
	}
	
	
	/**
	 * 删除目录及目录下所有文件或删除指定文件
	 *
	 * @param string $path 待删除目录路径
	 * @param bool $delDir 是否删除目录，true删除目录，false则只删除文件保留目录
	 * @param array $exFile 排除的文件数组
	 * @return bool 返回删除状态
	 */
	public static function path_delete($path, $delDir = false, $exFile = array()) {
    $result = true; // 对于空目录直接返回true状态
    if (!file_exists($path)) {
        return $result;
    }
	$directory = dirname($path);
    if (is_dir($directory)) {
		$path = $directory;
        $files = scandir($path);
        if ($files !== false) {
			// var_dump($files);exit;
            foreach ($files as $file) {
                if ($file != "." && $file != ".." && !in_array($file, $exFile)) {
                    $filePath = $path . '/' . $file;
                    if (is_dir($filePath)) {
						var_dump(1);exit;
                        $result = self::path_delete($filePath, true, $exFile);
                    } else {
                        $result = unlink($filePath);
                    }
                    if (!$result) {
                        break;
                    }
                }
            }
            if ($result && $delDir) {
				var_dump($path);exit;
                // 删除目录本身
                $result = rmdir($path);
            }
        }
    }
    return $result;
}
	
}
