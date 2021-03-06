<?php
/**
 * Created by JetBrains PhpStorm.
 * User: steffenbrem
 * Date: 7/19/13
 * Time: 7:46 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Rest;
use Rest\Core\Bootstrap;

/**
 * Class Application
 * @package Library
 */
class Application
{
    const PRODUCTION    = 'production';
    const DEVELOPMENT   = 'development';

    /**
     * Only set when the application actually runs
     *
     * @var Application
     */
    public static $shared;

    /**
     * @var Array
     */
    public $config = array();

    /**
     * @var Bootstrap
     */
    public $bootstrap;

    /**
     * @var Autoloader
     */
    public $autoloader;

    /**
     * @var string
     */
    private $environment = self::DEVELOPMENT;


    /**
     * Constructor
     */
    public function __construct(array $settings = array(), $environment = Application::PRODUCTION)
    {
        ob_start();

        $this->environment = $environment;

        $this->_prepare();

        // Parse settings
        $this->config = $this->_parseSettings($settings);
    }

    /**
     * Parse settings
     *
     * @param array $settings
     */
    private function _parseSettings(array $settings)
    {
        $settings = $this->_merge($this->_defaultSettingsTemplate(), $settings);

        $file = $settings['application']['path'] . '/' . $settings['application']['namespace'] . '/config/settings.ini';

        if (file_exists($file))
        {
            $settings = $this->_merge($settings, parse_ini_file($file, true));
        }

        return $settings;
    }

    /**
     * Default settings
     *
     * @return array
     */
    private function _defaultSettingsTemplate()
    {
        return array(
            'application' => array(
                'namespace' => 'App',
                'path' => dirname(__DIR__),
                'debug' => false
            ),
            'database' => array(
                'driver' => 'pdo_mysql',
                'user' => '',
                'password' => '',
                'dbname' => ''
            ),
            'doctrine' => array(
                'entity_namespace' => 'Entity'
            ),
            'xml' => array(
                'schemas_path' => null,
                'templates_path' => null
            )
        );
    }

    /**
     * Merge the first array into the second
     *
     * @param $array
     * @param $array2
     * @param bool $recursive
     */
    private function _merge(array $array, array $array2)
    {
        foreach ($array2 as $k => $v)
        {
            if (is_array($v) && isset($array[$k]) && is_array($array[$k]))
            {
                $array[$k] = $this->_merge($array[$k], $v);
            }
            else
            {
                $array[$k] = $v;
            }
        }

        return $array;
    }

    /**
     * Prepare application
     */
    private function _prepare()
    {
        define("SYS_PATH", __DIR__);

        if ($this->environment == Application::DEVELOPMENT)
        {
            ini_set("display_errors", 1);
            error_reporting(E_ALL);
        }

        require_once __DIR__ . "/Autoloader.php";
        $this->autoloader = new Autoloader();

        $this->autoloader->registerNamespace('Rest', dirname(__DIR__));
    }

    /**
     * Run Application
     */
    public function run()
    {
        self::$shared = $this;

        $this->autoloader->registerNamespace($this->config['application']['namespace'], $this->config['application']['path']);

        $path = $this->config['application']['path'] . '/' . $this->config['application']['namespace'] . '/Bootstrap.php';

        if (file_exists($path))
        {
            include $path;

            $class = '\\' . $this->config['application']['namespace'] . '\\Bootstrap';
        }
        else
        {
            $class = '\\Rest\\Core\\Bootstrap';
        }

        $this->bootstrap = new $class;
        $this->bootstrap->dispatch();

        ob_end_flush();
    }

    public function getFullPath()
    {
        return $this->config['application']['path'] . '/' . $this->config['application']['namespace'];
    }
}