<?php
/**
 * @copyright Copyright (c) 2016 Roman Ishchenko
 * @license https://github.com/ischenko/yii2-jsloader-requirejs/blob/master/LICENSE
 * @link https://github.com/ischenko/yii2-jsloader-requirejs#readme
 */

namespace ischenko\yii2\jsloader\requirejs;

use ischenko\yii2\jsloader\ModuleInterface;

/**
 * RequireJs-specific implementation of the configuration
 *
 * @author Roman Ishchenko <roman@ishchenko.ck.ua>
 * @since 1.0
 *
 * @method Module|null getModule($name)
 * @method Module[] getModules(\ischenko\yii2\jsloader\FilterInterface $filter = null)
 */
class Config extends \ischenko\yii2\jsloader\base\Config
{
    /**
     * @var array a list of other configuration options
     */
    protected $attributes = [];

    /**
     * @var array a list of valid options
     *
     * TODO: create setter and getter for map and config
     */
    static private $validOptions = [
        'map' => 1,
        'config' => 1,
        'enforceDefine' => 1,
        'waitSeconds' => 1,
        'context' => 1,
        'deps' => 1,
        'xhtml' => 1,
        'urlArgs' => 1,
        'scriptType' => 1,
        'skipDataMain' => 1,
    ];

    /**
     * Common setter for configuration options
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if (isset(self::$validOptions[$name])) {
            $this->attributes[$name] = $value;
            return;
        }

        parent::__set($name, $value);
    }

    /**
     * Common getter for configuration options
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset(self::$validOptions[$name])) {
            return isset($this->attributes[$name])
                ? $this->attributes[$name] : null;
        }

        return parent::__get($name);
    }

    /**
     * Setter for callback option
     *
     * @param string|\yii\web\JsExpression $value
     *
     * @return $this
     */
    public function setCallback($value)
    {
        if (!($value instanceof \yii\web\JsExpression)) {
            $value = new \yii\web\JsExpression($value);
        }

        $this->attributes['callback'] = $value;

        return $this;
    }

    /**
     * @return \yii\web\JsExpression|null
     */
    public function getCallback()
    {
        return isset($this->attributes['callback'])
            ? $this->attributes['callback'] : null;
    }

    /**
     * Builds configuration set into an array
     *
     * @return array
     */
    public function toArray()
    {
        $config = [];

        foreach ($this->attributes as $option => $value) {
            $config[$option] = $value;
        }

        if (!empty($this->baseUrl)) {
            $config['baseUrl'] = $this->baseUrl;
        }

        foreach ($this->getModules() as $module) {
            // Generate shim section
            if (($shimConfig = $this->renderShim($module)) !== []) {
                $config['shim'][$module->getAlias()] = $shimConfig;
            }

            // Generate paths section
            if (($pathsConfig = $this->renderPaths($module, $config)) !== []) {
                $config['paths'][$module->getAlias()] = $pathsConfig;
            }
        }

        return $config;
    }

    /**
     * Adds new module into configuration
     *
     * If passed a string a new module will be created
     *
     * @param ModuleInterface|string $module an instance of module to be added or name of a module to be created and added
     *
     * @return Module
     */
    public function addModule($module)
    {
        if (!($module instanceof ModuleInterface)) {
            $module = new Module($module);
        }

        return parent::addModule($module);
    }

    /**
     * @see http://requirejs.org/docs/api.html#config-paths
     *
     * @param array $data
     *
     * @return $this
     */
    public function setPaths(array $data)
    {
        foreach ($data as $moduleName => $files) {
            if (empty($files)) {
                continue;
            }

            $file = $files;
            $fallback = [];

            if (is_array($files)) {
                $file = array_shift($files);
                $fallback = $files;
            }

            if (!($module = $this->getModule($moduleName))) {
                $module = $this->addModule($moduleName);
            }

            $module->clearFiles();
            $module->clearFallbackFiles();
            $module->addFile($file);

            if ($fallback !== []) {
                $module->addFallbackFiles($fallback);
            }
        }

        return $this;
    }

    /**
     * @see http://requirejs.org/docs/api.html#config-shim
     *
     * @param array $data
     *
     * @return $this
     */
    public function setShim(array $data)
    {
        foreach ($data as $moduleName => $properties) {
            if (empty($properties)) {
                continue;
            }

            if (!($module = $this->getModule($moduleName))) {
                $module = $this->addModule($moduleName);
            }

            $module->setExports(null)->clearDependencies();

            if (!empty($properties['deps'])) {
                foreach ((array)$properties['deps'] as $dep) {
                    if (!($depModule = $this->getModule($dep))) {
                        $depModule = $this->addModule(md5($dep))->addFile($dep);
                    }

                    $module->addDependency($depModule);
                }
            }

            if (!empty($properties['init'])) {
                $module->setInit($properties['init']);
            }

            if (!empty($properties['exports'])) {
                $module->setExports($properties['exports']);
            }
        }

        return $this;
    }

    /**
     * Performs generation of the shim section
     *
     * @param Module $module
     * @return array
     */
    private function renderShim(Module $module)
    {
        $shimConfig = [];

        foreach ($module->getDependencies() as $dependency) {
            if (!isset($shimConfig['deps'])) {
                $shimConfig['deps'] = [];
            }

            $shimConfig['deps'][] = $dependency->getAlias();
        }

        if (($exports = $module->getExports()) !== null) {
            $shimConfig['exports'] = $exports;
        }

        if (($init = $module->getInit()) !== null) {
            $shimConfig['init'] = $init;
        }

        return $shimConfig;
    }

    /**
     * Performs generation of the paths section
     *
     * @param Module $module
     * @param array $config
     *
     * @return array
     */
    private function renderPaths(Module $module, &$config)
    {
        $options = $module->getOptions();
        $files = array_keys($module->getFiles());

        if (!empty($options['baseUrl']) && $files === []) {
            return $options['baseUrl'];
        }

        $paths = [$this->removeJsExtension(array_pop($files))];

        foreach ($module->getFallbackFiles() as $file) {
            $paths[] = $this->removeJsExtension($file);
        }

        // Add rest of the files as dependencies
        if ($files !== []) {
            $moduleName = $module->getAlias();

            if (!isset($config['shim'][$moduleName]['deps'])) {
                $config['shim'][$moduleName]['deps'] = [];
            }

            foreach ($files as $file) {
                if (!isset($parentDepends) || !isset($options['async'])) {
                    $parentDepends = $config['shim'][$moduleName]['deps'];
                }

                $config['shim'][$file]['deps'] = $parentDepends;
                $config['shim'][$moduleName]['deps'][] = $file;
            }
        }

        return $paths;
    }

    /**
     * Removes .js extension for a file
     *
     * @param string $file
     * @return string
     */
    private function removeJsExtension($file)
    {
        return preg_replace('/\.js$/', '', $file);
    }
}
