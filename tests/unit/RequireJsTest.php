<?php

namespace ischenko\yii2\jsloader\tests\unit\requirejs;

use Codeception\Util\Stub;
use ischenko\yii2\jsloader\helpers\JsExpression;
use ischenko\yii2\jsloader\RequireJs;
use yii\web\View;

class RequireJsTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    /**
     * @var \ischenko\yii2\jsloader\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        parent::_before();
    }

    protected function mockLoader($params = [], $testCase = false)
    {
        if (isset($params['view'])) {
            $view = $params['view'];
            unset($params['view']);
        } else {
            $view = $this->tester->mockView();
        }

        return Stub::construct('ischenko\yii2\jsloader\RequireJs', [$view], $params, $testCase);
    }

    /** Tests go below */

    public function testInstance()
    {
        $loader = $this->mockLoader();

        verify($loader)->isInstanceOf('ischenko\yii2\jsloader\base\Loader');
        verify($loader)->isInstanceOf('ischenko\yii2\jsloader\LoaderInterface');
    }

    public function testConfigGetter()
    {
        $loader = $this->mockLoader();
        $config = $loader->getConfig();

        verify($config)->notEmpty();
        verify($config)->isInstanceOf('ischenko\yii2\jsloader\ConfigInterface');
        verify($config)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Config');
        verify($config)->same($loader->getConfig());
    }

    /**
     * @dataProvider doRenderDataProvider
     */
    public function testDoRender($expressions, $expected)
    {
        $loader = $this->mockLoader([
            'publishRequireJs' => Stub::once(function ($code) use ($expected) {
                verify($code)->equals($expected);
            })
        ], $this);

        $doRender = $this->tester->getMethod($loader, 'doRender');

        $doRender->invokeArgs($loader, [$expressions]);
    }

    public function doRenderDataProvider()
    {
        return [
            [
                [
                    View::POS_END => new JsExpression('end code block'),
                    View::POS_LOAD => new JsExpression('load code block', [Stub::construct('ischenko\yii2\jsloader\requirejs\Module', ['/file1'])]),
                    View::POS_BEGIN => new JsExpression('begin code block'),
                    View::POS_READY => new JsExpression(null, [Stub::construct('ischenko\yii2\jsloader\requirejs\Module', ['/file1'])]),
                ],
                "begin code block\nend code block\n\nload code block"
            ],
            [
                [
                    View::POS_END => new JsExpression('end code block'),
                    View::POS_LOAD => new JsExpression('load code block', [Stub::construct('ischenko\yii2\jsloader\requirejs\Module', ['file1'], ['getFiles' => ['/file' => []]])]),
                    View::POS_BEGIN => new JsExpression('begin code block'),
                    View::POS_READY => new JsExpression(null, [Stub::construct('ischenko\yii2\jsloader\requirejs\Module', ['file1'], ['getFiles' => ['/file' => []]])]),
                ],
                "begin code block\nend code block\nrequire([\"file1\"], function() {\n\nrequire([\"file1\"], function() {\nload code block\n});\n});"
            ],
            [
                [
                    View::POS_END => new JsExpression('end code block'),
                    View::POS_LOAD => new JsExpression('load code block', [Stub::construct('ischenko\yii2\jsloader\requirejs\Module', ['/file1'], ['getFiles' => ['file' => []]])]),
                    View::POS_BEGIN => new JsExpression('begin code block', [Stub::construct('ischenko\yii2\jsloader\requirejs\Module', ['mod'], ['getFiles' => ['file' => []]])]),
                    View::POS_READY => new JsExpression(null, [Stub::construct('ischenko\yii2\jsloader\requirejs\Module', ['/file1'], ['getFiles' => ['file' => []]])]),
                ],
                "require([\"mod\"], function() {\nbegin code block\nend code block\nrequire([\"\\/file1\"], function() {\n\nrequire([\"\\/file1\"], function() {\nload code block\n});\n});\n});"
            ],
            [
                [
                    View::POS_END => new JsExpression('end code block'),
                    View::POS_LOAD => new JsExpression('load code block', [Stub::construct('ischenko\yii2\jsloader\requirejs\Module', ['/file1'], ['getFiles' => ['file' => []]])]),
                    View::POS_BEGIN => new JsExpression('begin code block', [Stub::construct('ischenko\yii2\jsloader\requirejs\Module', ['mod'], ['getFiles' => ['file' => []]])]),
                    View::POS_READY => new JsExpression(new JsExpression('test'), [Stub::construct('ischenko\yii2\jsloader\requirejs\Module', ['ready'], ['getFiles' => ['file' => []]])]),
                ],
                "require([\"mod\"], function() {\nbegin code block\nend code block\nrequire([\"ready\"], function() {\njQuery(document).ready(function() {\ntest\n});\nrequire([\"\\/file1\"], function() {\nload code block\n});\n});\n});"
            ]
        ];
    }

    public function testPublishRequireJs()
    {
        $this->specify('it publishes and registers the requirejs library from bower package', function () {
            $loader = $this->mockLoader([
                'main' => false,
                'view' => $this->tester->mockView([
                    'registerJsFile' => Stub::once(function ($path, $options) {
                        verify($path)->equals('/require.js');
                        verify($options)->hasKey('position');
                        verify($options)->hasntKey('defer');
                        verify($options)->hasntKey('async');
                        verify($options['position'])->equals(View::POS_END);
                    }),
                    'assetManager' => Stub::makeEmpty('yii\web\AssetManager', [
                        'publish' => Stub::once(function ($path) {
                            verify($path)->equals('@bower/requirejs/require.js');
                            return [null, '/require.js'];
                        })
                    ], $this)
                ], $this)
            ]);

            $publishRequireJs = $this->tester->getMethod($loader, 'publishRequireJs');

            $publishRequireJs->invokeArgs($loader, ['code']);
        });

        $this->specify('it does not publish the requirejs library if the libraryUrl property is set', function () {
            $loader = $this->mockLoader([
                'main' => false,
                'libraryUrl' => '/requirejs.js',
                'view' => $this->tester->mockView([
                    'registerJsFile' => Stub::once(function ($path, $options) {
                        verify($path)->equals('/requirejs.js');
                        verify($options)->hasntKey('async');
                        verify($options)->hasntKey('defer');
                    }),
                    'assetManager' => Stub::makeEmpty('yii\web\AssetManager', [
                        'publish' => Stub::never()
                    ], $this)
                ], $this)
            ]);

            $publishRequireJs = $this->tester->getMethod($loader, 'publishRequireJs');

            $publishRequireJs->invokeArgs($loader, ['code']);
        });

        $this->specify('it registers previously rendered JS code in the view', function () {
            $data = [
                [View::POS_END, "code"],
                [View::POS_HEAD, "var require = {};"]
            ];

            $loader = $this->mockLoader([
                'main' => false,
                'view' => $this->tester->mockView([
                    'registerJs' => Stub::atLeastOnce(function ($code, $position) use (&$data) {
                        $expected = array_shift($data);

                        verify($position)->equals($expected[0]);
                        verify($code)->equals($expected[1]);
                    }),
                    'assetManager' => Stub::makeEmpty('yii\web\AssetManager', [
                        'publish' => Stub::once(function () {
                            return [null, '/require.js'];
                        })
                    ], $this)
                ], $this)
            ]);

            $publishRequireJs = $this->tester->getMethod($loader, 'publishRequireJs');

            $publishRequireJs->invokeArgs($loader, ['code']);
        });

        $this->specify('it writes generated code into a file and then sets it as data-main entry', function ($main, $expectedMain, $expectedPublish) {
            $loader = $this->mockLoader([
                'main' => $main,
                'libraryUrl' => '/require.js',
                'view' => $this->tester->mockView([
                    'registerJs' => Stub::once(),
                    'registerJsFile' => Stub::once(function ($file, $options) use ($expectedMain) {
                        verify($file)->equals('/require.js');
                        verify($options)->hasKey('position');
                        verify($options)->hasKey('async');
                        verify($options)->hasKey('defer');
                        verify($options['position'])->equals(View::POS_END);
                        verify($options)->hasKey('data-main');
                        verify($options['data-main'])->equals($expectedMain);
                    }),
                    'assetManager' => Stub::makeEmpty('yii\web\AssetManager', [
                        'publish' => Stub::exactly($expectedPublish, function ($path) {
                            verify($path)->equals(\Yii::getAlias('@runtime/jsloader/' . md5('code') . '.js'));
                            verify("code")->equalsFile(\Yii::getAlias($path));
                            return [null, '/' . basename($path)];
                        })
                    ], $this)
                ], $this)
            ]);

            $publishRequireJs = $this->tester->getMethod($loader, 'publishRequireJs');

            $publishRequireJs->invokeArgs($loader, ['code']);
        }, ['examples' => [
            [null, '/' . md5('code') . '.js', 1],
            ['', '/' . md5('code') . '.js', 1],
            [' ', '/' . md5('code') . '.js', 1],
            ['/main.js', '/main.js', 0],
        ]]);

        $file = \Yii::getAlias('@runtime/jsloader') . '/' . md5('code') . '.js';

        $this->afterSpecify(function () use ($file) {
            rmdir($file);
            $this->cleanSpecify();
        });

        $this->specify('it throws an exception if it fails to write data-main file', function () use ($file) {
            $loader = $this->mockLoader();
            $loader->runtimePath = '/var/run';
            $publishRequireJs = $this->tester->getMethod($loader, 'publishRequireJs');
            @unlink($file);
            @mkdir($file);
            $publishRequireJs->invokeArgs($loader, ['code']);
        }, ['throws' => 'RuntimeException']);
    }

    /**
     * @dataProvider providerRenderRequireConfigOptions
     */
    public function testRenderRequireConfig($config, $expected)
    {
        $loader = $this->mockLoader([
            'getConfig' => Stub::once(function () use ($config) {
                return $this->tester->mockConfigInterface([
                    'toArray' => Stub::once(function () use ($config) {
                        return $config;
                    })
                ], $this);
            })
        ], $this);

        verify($this->tester->getMethod($loader, 'renderRequireConfig')->invoke($loader))->equals($expected);
    }

    public function providerRenderRequireConfigOptions()
    {
        return [
            [[], 'var require = {};'],
            [['paths' => []], 'var require = {};'],
            [['paths' => ['test' => 'file']], 'var require = {"paths":{"test":"file"}};'],
            [['paths' => ['test' => 'file'], 'shim' => ['test' => ['deps' => ['file2']]]], 'var require = {"paths":{"test":"file"},"shim":{"test":{"deps":["file2"]}}};'],
        ];
    }
}
