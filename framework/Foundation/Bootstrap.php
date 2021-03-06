<?php

namespace GlueNamespace\Framework\Foundation;

use GlueNamespace\App\Modules\Activator;
use GlueNamespace\App\Modules\Deactivator;

class Bootstrap
{
    /**
     * The main plugin file path
     *
     * @var strring
     */
    protected static $file = null;

    /**
     * The base dir path of the plugin
     *
     * @var strring
     */
    protected static $basePath = null;

    /**
     * The app config (/config/app.php)
     *
     * @var strring
     */
    protected static $config = [];

    /**
     * Conveniently start the framework
     *
     * @param string $file
     */
    public static function run($file)
    {
        static::init($file);
        static::registerHooks();
        static::registerAutoLoader();
        static::registerApplication();
    }

    /**
     * Initialize the framework
     *
     * @param string $file [the main plugin file path]
     */
    public static function init($file)
    {
        static::$file = $file;

        static::$basePath = plugin_dir_path($file);

        if (file_exists($activator = static::$basePath.'app/Modules/Activator.php')) {
            include_once $activator;
        }

        if (file_exists($deactivator = static::$basePath.'app/Modules/Deactivator.php')) {
            include_once $deactivator;
        }
    }

    /**
     * Register activation/deactivation hooks
     */
    public static function registerHooks()
    {
        static::registerActivationHook();
        static::registerDeactivationHook();
    }

    /**
     * Register activation hook
     *
     * @return bool
     */
    public static function registerActivationHook()
    {
        return register_activation_hook(
            static::$file,
            [__CLASS__, 'activate']
        );
    }

    /**
     * Register deactivation hook
     * @return bool
     */
    public static function registerDeactivationHook()
    {
        return register_deactivation_hook(
            static::$file,
            [__CLASS__, 'deactivate']
        );
    }

    /**
     * Validate and activate the plugin
     */
    public static function activate()
    {
        static::validatePlugin();
        if (class_exists('GlueNamespace\App\Modules\Activator')) {
            (new Activator)->handleActivation(static::$file);
        }
    }

    /**
     * Deactivate the plugin
     */
    public static function deactivate()
    {
        // Framework specific implementation if necessary...
        if (class_exists('GlueNamespace\App\Modules\Deactivator')) {
            (new Deactivator)->handleDeactivation(static::$file);
        }
    }

    /**
     * Validate the plugin by checking all rquired files/settings
     */
    public static function validatePlugin()
    {
        if (!file_exists($glueJson = static::$basePath.'glue.json')) {
            die('The [glue.json] file is missing from "'.static::$basePath.'" directory.');
        }

        static::$config = json_decode(file_get_contents($glueJson), true);

        $configPath = static::$basePath.'config';

        if (!file_exists($file = $configPath.'/app.php')) {
            die('The [config.php] file is missing from "'.$configPath.'" directory.');
        }

        static::$config = array_merge(include $file, static::$config);

        if (!($autoload = @static::$config['autoload'])) {
            die('The [autoload] key is not specified or invalid in "'.$glueJson.'" file.');
        }

        if (!($namespace = @$autoload['namespace'])) {
            die('The [namespace] key is not specified or invalid in "'.$glueJson.'" file.');
        }

        if (!($namespaceMapping = @$autoload['mapping'])) {
            die('The [mapping] key is not specified or invalid in "'.$glueJson.'" file.');
        }
    }

    /**
     * Register the autoloader
     */
    public static function registerAutoLoader()
    {
        if (!static::$config) {
            static::$config = json_decode(file_get_contents(static::$basePath.'glue.json'), true);
            static::$config = array_merge(include static::$basePath.'config/app.php', static::$config);
        }

        spl_autoload_register([__CLASS__, 'loader']);
    }

    /**
     * Framework's custom autoloader
     *
     * @param  string $class
     * @return mixed
     */
    public static function loader($class)
    {
        $namespace = static::$config['autoload']['namespace'];

        if (substr($class, 0, strlen($namespace)) !== $namespace) {
            return false;
        }

        foreach (static::$config['autoload']['mapping'] as $key => $value) {
            $className = str_replace(
                ['\\', $key, $namespace],
                ['/', $value, ''],
                $class
            );

            $file = static::$basePath.trim($className, '/').'.php';

            if (is_readable($file)) {
                return include $file;
            }
        }
    }

    /**
     * Register "plugins_loaded" hook to run the plugin
     */
    public static function registerApplication()
    {
        add_action('plugins_loaded', function () {
            return new Application(static::$file, static::$config);
        });
    }
}
