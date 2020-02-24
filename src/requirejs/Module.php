<?php
/**
 * @copyright Copyright (c) 2016 Roman Ishchenko
 * @license https://github.com/ischenko/yii2-jsloader-requirejs/blob/master/LICENSE
 * @link https://github.com/ischenko/yii2-jsloader-requirejs#readme
 */

namespace ischenko\yii2\jsloader\requirejs;

use ischenko\yii2\jsloader\ModuleInterface;
use yii\base\InvalidArgumentException;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;

/**
 * Implementation of a module for RequireJS
 *
 * @author Roman Ishchenko <roman@ishchenko.ck.ua>
 * @since 1.0
 */
class Module extends \ischenko\yii2\jsloader\base\Module
{
    /**
     * @var string
     */
    private $exports;

    /**
     * @var JsExpression
     */
    private $initScript;

    /**
     * @var array
     */
    private $fallbackFiles = [];

    /**
     * @return JsExpression
     */
    public function getInit()
    {
        return $this->initScript;
    }

    /**
     * @param string|JsExpression $init
     *
     * @return $this
     */
    public function setInit($init)
    {
        if (!($init instanceof JsExpression)) {
            $init = new JsExpression($init);
        }

        $this->initScript = $init;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExports()
    {
        return $this->exports;
    }

    /**
     * Sets value for the exports section of shim config
     *
     * @param string|null $exports
     * @return $this
     *
     * @throws InvalidArgumentException
     *
     * @see http://requirejs.org/docs/api.html#config-shim
     */
    public function setExports($exports)
    {
        if ($exports === null) {
            $this->exports = null;
        } else {
            if (!is_string($exports)) {
                throw new InvalidArgumentException('Exports must be a string');
            }

            $this->exports = trim($exports);
            $this->exports = $this->exports ?: null;
        }

        return $this;
    }

    /**
     * Adds fallback files for the module
     *
     * @param array $files
     *
     * @return $this
     */
    public function addFallbackFiles(array $files)
    {
        foreach ($files as $file) {
            $this->fallbackFiles[] = $file;
        }

        return $this;
    }

    /**
     * Clears all fallback files from the module
     *
     * @return $this
     */
    public function clearFallbackFiles()
    {
        $this->fallbackFiles = [];

        return $this;
    }

    /**
     * @return array a list of fallback files
     */
    public function getFallbackFiles()
    {
        return $this->fallbackFiles;
    }

    /**
     * @param array $options options for a module. Loads settings from requirejs key
     * @return $this
     */
    public function setOptions(array $options): ModuleInterface
    {
        $rjsOptions = ArrayHelper::remove($options, 'requirejs', []);

        foreach ($rjsOptions as $key => $value) {
            $this->$key = $value;
        }

        return parent::setOptions($options);
    }
}
