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
     * @return string|null
     */
    public function getExports()
    {
        return $this->exports;
    }

    /**
     * Adds JS file into a module
     *
     * @param string $file URL of a file
     * @param array $options options for given file
     *
     * @return $this
     * @throws InvalidParamException
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
        foreach ($files as $file) {
            $this->fallbackFiles[] = $this->removeJsExtension($file);
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
