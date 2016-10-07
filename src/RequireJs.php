<?php
/**
 * @copyright Copyright (c) 2016 Roman Ishchenko
 * @license https://github.com/ischenko/yii2-jsloader-requirejs/blob/master/LICENSE
 * @link https://github.com/ischenko/yii2-jsloader-requirejs#readme
 */

namespace ischenko\yii2\jsloader;

use Yii;
use yii\web\View;
use yii\helpers\Json;
use yii\helpers\FileHelper;
use ischenko\yii2\jsloader\base\Loader;
use ischenko\yii2\jsloader\requirejs\Config;
use ischenko\yii2\jsloader\requirejs\Module;

/**
 * RequireJS implementation
 *
 * @author Roman Ishchenko <roman@ishchenko.ck.ua>
 * @since 1.0
 */
class RequireJs extends Loader
{
    const RUNTIME_PATH = '@runtime/jsloader';

    /**
     * @var string URL to be used to load the RequireJS library. If value is empty the loader will publish library from the bower package
     */
    public $libraryUrl;

    /**
     * @var string path to the RequireJS library
     */
    public $libraryPath = '@bower/requirejs/require.js';

    /**
     * @see http://requirejs.org/docs/api.html#data-main
     *
     * @var string|false URL of script file that will be used as value for the data-main entry. FALSE means do not use the data-main entry.
     */
    public $main;

    /**
     * @var Config
     */
    private $_config;

    /**
     * @inheritDoc
     *
     * @return Config
     */
    public function getConfig()
    {
        if (!$this->_config) {
            $this->_config = new Config();
        }

        return $this->_config;
    }

    /**
     * @inheritDoc
     */
    protected function doRender(array $codeBlocks)
    {
        $rjsCode = '';

        krsort($codeBlocks);

        foreach ($codeBlocks as $codeBlock) {
            $code = isset($codeBlock['code']) ? $codeBlock['code'] : '';
            $depends = isset($codeBlock['depends']) ? $codeBlock['depends'] : [];

            if (!empty($rjsCode)) {
                $code = "{$code}\n{$rjsCode}";
            }

            $rjsCode = $this->renderRequireBlock($code, (array)$depends);
        }

        $this->publishRequireJs($rjsCode);
    }

    /**
     * @param string $code
     */
    protected function publishRequireJs($code)
    {
        $view = $this->getView();
        $assetManager = $view->getAssetManager();

        if (empty($this->libraryUrl)) {
            list(, $this->libraryUrl) = $assetManager->publish($this->libraryPath);
        }

        $requireOptions = [
            'defer' => 'defer',
            'async' => 'async',
            'position' => View::POS_END
        ];

        if ($this->main === false) {
            $view->registerJs($code, $requireOptions['position']);
        } else {
            $requireOptions['data-main'] = trim($this->main);

            if (empty($requireOptions['data-main'])) {
                $mainPath = $this->writeFileContent('requirejs-main.js', $code);
                list(, $requireOptions['data-main']) = $assetManager->publish($mainPath);
            }
        }

        $view->registerJsFile($this->libraryUrl, $requireOptions);
        $view->registerJs($this->renderRequireConfig(), View::POS_HEAD);
    }

    /**
     * Performs rendering of configuration block for RequireJS
     *
     * @return string
     */
    protected function renderRequireConfig()
    {
        $config = $this->getConfig()->toArray();
        $config = Json::encode((object)array_filter($config));

        return "var require = {$config};";
    }

    /**
     * @param string $code
     * @param Module[] $depends
     *
     * @return string
     */
    protected function renderRequireBlock($code, array $depends)
    {
        if (empty($code)) {
            return '';
        }

        $pad = 0;
        $injects = [];
        $modules = [];

        foreach ($depends as $module) {
            if (!($module instanceof Module)) {
                continue;
            }

            if (($inject = $module->getExports()) !== null) {
                if ($pad > 0) {
                    $injects = array_merge(
                        $injects, array_fill(0, $pad, 'undefined')
                    );

                    $pad = 0;
                }

                $injects[] = $inject;
            } else {
                $pad++;
            }

            $modules[] = Json::encode($module->getName());
        }

        return 'require([' . implode(',', $modules) . '], function(' . implode(',', $injects) . ") {\n{$code}\n});";
    }

    /**
     * @param string $filename
     * @param string $content
     *
     * @return string full path to a file
     *
     * @throws \RuntimeException
     */
    private function writeFileContent($filename, $content)
    {
        static $runtimePath;

        if ($runtimePath === null) {
            $runtimePath = Yii::getAlias(self::RUNTIME_PATH);
            FileHelper::createDirectory($runtimePath);
        }

        $filePath = $runtimePath . DIRECTORY_SEPARATOR . $filename;

        if (@file_put_contents($filePath, $content, LOCK_EX) === false) {
            throw new \RuntimeException("Failed to write data into a file \"$filePath\"");
        }

        return $filePath;
    }
}
