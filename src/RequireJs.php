<?php
/**
 * @copyright Copyright (c) 2016 Roman Ishchenko
 * @license https://github.com/ischenko/yii2-jsloader-requirejs/blob/master/LICENSE
 * @link https://github.com/ischenko/yii2-jsloader-requirejs#readme
 */

namespace ischenko\yii2\jsloader;

use ischenko\yii2\jsloader\base\Loader;
use ischenko\yii2\jsloader\helpers\JsExpression;
use ischenko\yii2\jsloader\requirejs\Config;
use ischenko\yii2\jsloader\requirejs\JsRenderer;
use RuntimeException;
use yii\di\Instance;
use yii\helpers\Json;
use yii\web\View;

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
     * @var string|callable|false URL of script file that will be used as value for the data-main entry. FALSE means do not use the data-main entry.
     */
    public $main;

    /**
     * @var string|array|JsRendererInterface
     */
    public $renderer = JsRenderer::class;

    /**
     * @var Config
     */
    private $config;

    public function init()
    {
        parent::init();

        $this->renderer = Instance::ensure($this->renderer, JsRendererInterface::class);
    }

    /**
     * @return Config
     */
    public function getConfig(): ConfigInterface
    {
        if (!$this->config) {
            $this->config = new Config();
        }

        return $this->config;
    }

    /**
     * Performs actual rendering of the JS loader
     *
     * @param JsExpression[] $jsExpressions a list of js expressions indexed by position
     */
    protected function doRender(array $jsExpressions)
    {
        $resultJsCode = '';

        krsort($jsExpressions);

        foreach ($jsExpressions as $position => $jsExpression) {
            if (!empty($resultJsCode)) {
                $expression = $jsExpression;

                while (($code = $expression->getExpression()) instanceof JsExpression) {
                    $expression = $code;
                }

                if ($position === View::POS_READY && !empty($code)) {
                    $code = $this->encloseJqueryReady($code);
                }

                $expression->setExpression("{$code}\n{$resultJsCode}");
            }

            $resultJsCode = $jsExpression->render($this->renderer);
        }

        $this->publishRequireJs($resultJsCode);
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

            $main = $this->resolveMainScript($this->main, $code);

            list(, $requireOptions['data-main']) = $assetManager->publish($main);
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
     *
     * @return string
     */
    private function encloseJqueryReady($code)
    {
        return "jQuery(document).ready(function() {\n{$code}\n});";
    }

    /**
     * @param string $filePath
     * @param string $content
     *
     * @return string full path to a file
     *
     * @throws RuntimeException
     */
    private function writeFileContent($filePath, $content)
    {
        if (!file_exists($filePath)) {
            if (@file_put_contents($filePath, $content, LOCK_EX) === false) {
                throw new RuntimeException("Failed to write data into a file \"$filePath\"");
            }
        }

        return $filePath;
    }

    /**
     * @param mixed $main
     * @param string $code
     * @return string
     */
    private function resolveMainScript($main, $code): string
    {
        if (is_callable($main)) {
            $main = call_user_func($main, $code, $this);
        }

        if (empty($main = trim($main))) {
            $main = md5($code) . '.js';
        }

        $path = $this->getRuntimePath()
            . DIRECTORY_SEPARATOR . ltrim($main, DIRECTORY_SEPARATOR);

        return $this->writeFileContent($path, $code);
    }
}
