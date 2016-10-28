<?php
/**
 * @copyright Copyright (c) 2016 Roman Ishchenko
 * @license https://github.com/ischenko/yii2-jsloader-requirejs/blob/master/LICENSE
 * @link https://github.com/ischenko/yii2-jsloader-requirejs#readme
 */

namespace ischenko\yii2\jsloader\requirejs;

use yii\base\InvalidParamException;

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
     * @var \yii\web\JsExpression
     */
    private $_init;

    /**
     * @var array
     */
    private $fallbackFiles = [];

    /**
     * Sets value for the exports section of shim config
     *
     * @param string|null $exports
     * @return $this
     *
     * @throws InvalidParamException
     *
     * @see http://requirejs.org/docs/api.html#config-shim
     */
    public function setExports($exports)
    {
        if ($exports === null) {
            $this->exports = null;
        } else {
            if (!is_string($exports)) {
                throw new InvalidParamException('Exports must be a string');
            }

            $this->exports = trim($exports);
            $this->exports = $this->exports ?: null;
        }

        return $this;
    }

    /**
     * @return \yii\web\JsExpression
     */
    public function getInit()
    {
        return $this->_init;
    }

    /**
     * @param string|\yii\web\JsExpression $init
     *
     * @return $this
     */
    public function setInit($init)
    {
        if (!($init instanceof \yii\web\JsExpression)) {
            $init = new \yii\web\JsExpression($init);
        }

        $this->_init = $init;

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
    public function setOptions(array $options)
    {
        if (isset($options['requirejs'])) {
            foreach ((array)$options['requirejs'] as $key => $value) {
                $this->$key = $value;
            }

            unset($options['requirejs']);
        }

        return parent::setOptions($options);
    }
}
