<?php
    
    namespace Think;
    
    /**
     * 自动生成类
     * Class Build
     * @package Think
     */
    final class Build
    {
        
        protected static $controller = '<?php
namespace [MODULE]\Controller;
use Think\Controller;
class [CONTROLLER]Controller extends Controller {
    public function index(){
        $this->show(\'欢迎\');
    }
}';
        
        protected static $model = '<?php
namespace [MODULE]\Model;
use Think\Model;
class [MODEL]Model extends Model {

}';
        
        /**
         * 检测应用目录是否需要自动创建
         *
         * @param $module
         */
        public static function checkDir($module)
        {
            if ( ! is_dir(APP_PATH.$module)) {
                // 创建模块的目录结构
                self::buildAppDir($module);
            } elseif ( ! is_dir(LOG_PATH)) {
                // 检查缓存目录
                self::buildRuntime();
            }
        }
        
        /**
         * 创建应用和模块的目录结构
         *
         * @param $module
         */
        public static function buildAppDir($module)
        {
            // 没有创建的话自动创建
            if ( ! is_dir(APP_PATH)) {
                mkdir(APP_PATH, 0755, true);
            }
            if (is_writeable(APP_PATH)) {
                $dirs = [
                    COMMON_PATH,
                    COMMON_PATH.'Common/',
                    CONF_PATH,
                    APP_PATH.$module.'/',
                    APP_PATH.$module.'/Common/',
                    APP_PATH.$module.'/Controller/',
                    APP_PATH.$module.'/Model/',
                    APP_PATH.$module.'/Conf/',
                    APP_PATH.$module.'/View/',
                    RUNTIME_PATH,
                    CACHE_PATH,
                    CACHE_PATH.$module.'/',
                    LOG_PATH,
                    LOG_PATH.$module.'/',
                    TEMP_PATH,
                    DATA_PATH,
                ];
                foreach ($dirs as $dir) {
                    if ( ! is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                }
                // 写入应用配置文件
                if ( ! is_file(CONF_PATH.'config.php')) {
                    file_put_contents(CONF_PATH.'config.php', "<?php\nreturn [\n\t//'配置项'=>'配置值'\n];");
                }
                // 写入模块配置文件
                if ( ! is_file(APP_PATH.$module.'/Conf/config.php')) {
                    file_put_contents(APP_PATH.$module.'/Conf/config.php', "<?php\nreturn [\n\t//'配置项'=>'配置值'\n];");
                }
                // 生成模块的测试控制器
                if (defined('BUILD_CONTROLLER_LIST')) {
                    // 自动生成的控制器列表（注意大小写）
                    $list = explode(',', BUILD_CONTROLLER_LIST);
                    foreach ($list as $controller) {
                        self::buildController($module, $controller);
                    }
                } else {
                    // 生成默认的控制器
                    self::buildController($module);
                }
                // 生成模块的模型
                if (defined('BUILD_MODEL_LIST')) {
                    // 自动生成的控制器列表（注意大小写）
                    $list = explode(',', BUILD_MODEL_LIST);
                    foreach ($list as $model) {
                        self::buildModel($module, $model);
                    }
                }
            } else {
                header('Content-Type:text/html; charset=utf-8');
                Think::halt('应用目录['.APP_PATH.']不可写，目录无法自动生成！<BR>请手动生成项目目录~');
            }
        }
        
        /**
         * 检查缓存目录(Runtime) 如果不存在则自动创建
         * @return bool
         */
        public static function buildRuntime()
        {
            if ( ! is_dir(RUNTIME_PATH)) {
                mkdir(RUNTIME_PATH);
            } elseif ( ! is_writeable(RUNTIME_PATH)) {
                header('Content-Type:text/html; charset=utf-8');
                Think::halt('目录 [ '.RUNTIME_PATH.' ] 不可写！');
            }
            // 模板缓存目录
            mkdir(CACHE_PATH);
            // 日志目录
            if ( ! is_dir(LOG_PATH)) {
                mkdir(LOG_PATH);
            }
            // 数据缓存目录
            if ( ! is_dir(TEMP_PATH)) {
                mkdir(TEMP_PATH);
            }
            // 数据文件目录
            if ( ! is_dir(DATA_PATH)) {
                mkdir(DATA_PATH);
            }
            
            return true;
        }
        
        /**
         * 创建控制器类
         *
         * @param        $module
         * @param string $controller
         */
        public static function buildController($module, $controller = 'Index')
        {
            $file = APP_PATH.$module.'/Controller/'.$controller.'Controller.php';
            if ( ! is_file($file)) {
                $content = str_replace(['[MODULE]', '[CONTROLLER]'], [$module, $controller], self::$controller);
                $dir     = dirname($file);
                if ( ! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($file, $content);
            }
        }
        
        /**
         * 创建模型类
         *
         * @param $module
         * @param $model
         */
        public static function buildModel($module, $model)
        {
            $file = APP_PATH.$module.'/Model/'.$model.'Model.php';
            if ( ! is_file($file)) {
                $content = str_replace(['[MODULE]', '[MODEL]'], [$module, $model], self::$model);
                $dir     = dirname($file);
                if ( ! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($file, $content);
            }
        }
        
    }
