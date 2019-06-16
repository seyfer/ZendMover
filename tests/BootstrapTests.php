<?php

/**
 * Description of BootstrapTests
 *
 * @author seyfer
 */

use Zend\Loader\AutoloaderFactory;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;

error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

/**
 * Test bootstrap, for setting up autoloading
 */
class BootstrapTests
{

    static $modulesToInclude = [];
    static $modulesToTest = ['ZendMover'];

    /**
     * @var ServiceManager
     */
    protected static $serviceManager;

    /**
     * @return ServiceManager
     */
    public static function getServiceManager()
    {
        return static::$serviceManager;
    }

    /**
     *
     */
    public static function init()
    {
        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING | E_STRICT);
        chdir(__DIR__);

        $zf2ModulePaths = [dirname(dirname(__DIR__))];
        if (($path = static::findParentPath('vendor'))) {
            $zf2ModulePaths[] = $path;
        }
        if (($path = static::findParentPath('module')) !== $zf2ModulePaths[0]) {
            $zf2ModulePaths[] = $path;
        }

        static::initAutoloader();

        $modules = array_merge(self::$modulesToInclude, self::$modulesToTest);
        // use ModuleManager to load this module and it's dependencies
        $config = [
            'module_listener_options' => [
                'module_paths' => $zf2ModulePaths,
                'config_glob_paths' => [
                    dirname(dirname(dirname(__DIR__))) . '/config/autoload/{,*.}{global,local}.php',
                ],
            ],
            'modules' => $modules,
        ];

        $serviceManager = new ServiceManager(new ServiceManagerConfig());
        $serviceManager->setService('ApplicationConfig', $config);
        $serviceManager->get('ModuleManager')->loadModules();

        static::$serviceManager = $serviceManager;
    }

    /**
     *
     */
    public static function chroot()
    {
        $rootPath = dirname(static::findParentPath('module'));
        chdir($rootPath);
    }

    /**
     *
     */
    protected static function initAutoloader()
    {
        $vendorPath = static::findParentPath('vendor');

        $zf2Path = getenv('ZF2_PATH');
        if (!$zf2Path) {
            if (defined('ZF2_PATH')) {
                $zf2Path = ZF2_PATH;
            } elseif (is_dir($vendorPath . '/ZF2/library')) {
                $zf2Path = $vendorPath . '/ZF2/library';
            } elseif (is_dir($vendorPath . '/zendframework/zendframework/library')) {
                $zf2Path = $vendorPath . '/zendframework/zendframework/library';
            }
        }

        if (!$zf2Path) {
            throw new \RuntimeException(
                'Unable to load ZF2. Run `php composer.phar install` or'
                . ' define a ZF2_PATH environment variable.'
            );
        }

        if (file_exists($vendorPath . '/autoload.php')) {
            include $vendorPath . '/autoload.php';
        }

        $namespaces = [
            __NAMESPACE__ => __DIR__ . '/' . __NAMESPACE__,
        ];

        //find all modules tests foler and namespace
        $modulesPath = self::findParentPath('module');
        foreach (self::$modulesToTest as $moduleToTest) {
            $moduleToTestName = $moduleToTest . "Test";
            $namespaces = array_merge($namespaces, [
                $moduleToTestName => $modulesPath . DIRECTORY_SEPARATOR . $moduleToTest . DIRECTORY_SEPARATOR .
                    'tests' . DIRECTORY_SEPARATOR . $moduleToTestName,
            ]);
        }

        include $zf2Path . '/Zend/Loader/AutoloaderFactory.php';
        AutoloaderFactory::factory([
            'Zend\Loader\StandardAutoloader' => [
                'autoregister_zf' => true,
                'namespaces' => $namespaces,
            ],
        ]);
    }

    /**
     * @param $path
     * @return bool|string
     */
    protected static function findParentPath($path)
    {
        $dir = __DIR__;
        $previousDir = '.';
        while (!is_dir($dir . '/' . $path)) {
            $dir = dirname($dir);
            if ($previousDir === $dir) {
                return false;
            }
            $previousDir = $dir;
        }

        return $dir . '/' . $path;
    }

}

BootstrapTests::init();
BootstrapTests::chroot();
