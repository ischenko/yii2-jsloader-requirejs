<?php
/**
 * @copyright Copyright (c) 2016 Roman Ishchenko
 * @license https://github.com/ischenko/yii2-jsloader-requirejs/blob/master/LICENSE
 * @link https://github.com/ischenko/yii2-jsloader-requirejs#readme
 */

namespace ischenko\yii2\jsloader;

use yii\web\View;
use yii\helpers\Json;
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
    private $config;

    /**
     * @inheritDoc
     *
     * @return Config
     */
    public function getConfig()
    {
        if (!$this->config) {
            $this->config = new Config();
        }

        return $this->config;
    }

    /**
     * @inheritDoc
     */
    protected function doRender(array $codeBlocks)
    {
        $rjsCode = '';
        $codeBlocks = $this->prepareCodeBlocks($codeBlocks);

        foreach ($codeBlocks as $position => $codeBlock) {
            $codeBlock = array_merge([
                'code' => '', 'depends' => []
            ], $codeBlock);

            $code = $codeBlock['code'];

            if ($position == View::POS_READY) {
                $code = $this->encloseJqueryReady($code);
            }

            if (!empty($rjsCode)) {
                $code = "{$code}\n{$rjsCode}";
            }

            $rjsCode = $this->renderRequireBlock($code, $codeBlock['depends']);
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
            'position' => View::POS_END
        ];

        if ($this->main === false) {
            $view->registerJs($code, $requireOptions['position']);
        } else {
            $requireOptions['async'] = 'async';
            $requireOptions['defer'] = 'defer';
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
        if (empty($code) && empty($depends)) {
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

        $requireBlock = 'require([' . implode(',', $modules) . ']';

        if (!empty($code)) {
            $requireBlock .= ', function(' . implode(',', $injects) . ") {\n{$code}\n}";
        }

        return $requireBlock . ');';
    }

    /**
     * @param string $code
     *
     * @return string
     */
    private function encloseJqueryReady($code)
    {
        return "jQuery(document).ready(function() {\n{$code}\n});";
    }

    /**
     * @param array $codeBlocks
     * @return array
     */
    private function prepareCodeBlocks(array $codeBlocks)
    {
        krsort($codeBlocks);

        for ($i = View::POS_HEAD; $i <= View::POS_LOAD; $i++) {
            if (!isset($codeBlocks[$i], $codeBlocks[$i + 1])) {
                continue;
            }

            $src = &$codeBlocks[$i];
            $dst = &$codeBlocks[$i + 1];

            if (empty($src['code']) && !empty($src['depends'])) {
                if (!isset($dst['depends'])) {
                    $dst['depends'] = [];
                }

                $dst['depends'] = array_merge($src['depends'], $dst['depends']);

                unset($codeBlocks[$i]);
            }
        }

        return $codeBlocks;
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
        $filePath = $this->getRuntimePath() . DIRECTORY_SEPARATOR . $filename;

        if (@file_put_contents($filePath, $content, LOCK_EX) === false) {
            throw new \RuntimeException("Failed to write data into a file \"$filePath\"");
        }

        return $filePath;
    }
}
