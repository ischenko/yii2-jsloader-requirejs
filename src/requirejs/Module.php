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
    private $_exports;

    /**
     * @var array
     */
    private $_fallbackFiles = [];

    /**
     * Sets value for the exports section of shim config
     *
     * @param string $exports
     * @return $this
     *
     * @throws InvalidParamException
     *
     * @see http://requirejs.org/docs/api.html#config-shim
     */
    public function setExports($exports)
    {
        if ($exports === null) {
            $this->_exports = null;
        } else {
            if (!is_string($exports)) {
                throw new InvalidParamException('Exports must be a string');
            }

            $this->_exports = trim($exports);
            $this->_exports = $this->_exports ?: null;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getExports()
    {
        return $this->_exports;
    }

    /**
     * @inheritDoc
     */
    public function addFile($file, $options = [])
    {
        return parent::addFile($this->removeJsExtension($file), $options);
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
        $this->_fallbackFiles = array_merge(
            $this->_fallbackFiles, array_values(array_map([$this, 'removeJsExtension'], $files))
        );

        return $this;
    }

    /**
     * Clears all fallback files from the module
     *
     * @return $this
     */
    public function clearFallbackFiles()
    {
        $this->_fallbackFiles = [];

        return $this;
    }

    /**
     * @return array a list of fallback files
     */
    public function getFallbackFiles()
    {
        return $this->_fallbackFiles;
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
