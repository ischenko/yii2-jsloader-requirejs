<?php

namespace ischenko\yii2\jsloader\tests\unit\requirejs;

use Codeception\AssertThrows;
use Codeception\Specify;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Codeception\Util\Stub;
use ischenko\yii2\jsloader\helpers\JsExpression;
use ischenko\yii2\jsloader\tests\UnitTester;
use Yii;
use yii\web\View;

class RequireJsTest extends Unit
{
    use Specify;
    use AssertThrows;

    /**
     * @var UnitTester
     */
    protected $tester;

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
            'publishRequireJs' => Expected::once(function ($code) use ($expected) {
                verify($code)->equals($expected);
            })
        ]);

        $doRender = $this->tester->getMethod($loader, 'doRender');

        $doRender->invokeArgs($loader, [$expressions]);
    }

    public function doRenderDataProvider()
    {
        return [
            [
                [
                    View::POS_END => new JsExpression('end code block'),
                    View::POS_LOAD => new JsExpression('load code block',
                        [$this->construct('ischenko\yii2\jsloader\requirejs\Module', ['/file1'])]),
                    View::POS_BEGIN => new JsExpression('begin code block'),
                    View::POS_READY => new JsExpression(null,
                        [$this->construct('ischenko\yii2\jsloader\requirejs\Module', ['/file1'])]),
                ],
                "begin code block\nend code block\n\nload code block"
            ],
            [
                [
                    View::POS_END => new JsExpression('end code block'),
                    View::POS_LOAD => new JsExpression('load code block', [
                        $this->construct('ischenko\yii2\jsloader\requirejs\Module', ['file1'],
                            ['getFiles' => ['/file' => []]])
                    ]),
                    View::POS_BEGIN => new JsExpression('begin code block'),
                    View::POS_READY => new JsExpression(null, [
                        $this->construct('ischenko\yii2\jsloader\requirejs\Module', ['file1'],
                            ['getFiles' => ['/file' => []]])
                    ]),
                ],
                "begin code block\nend code block\nrequire([\"file1\"], function() {\n\nrequire([\"file1\"], function() {\nload code block\n});\n});"
            ],
            [
                [
                    View::POS_END => new JsExpression('end code block'),
                    View::POS_LOAD => new JsExpression('load code block', [
                        $this->construct('ischenko\yii2\jsloader\requirejs\Module', ['/file1'],
                            ['getFiles' => ['file' => []]])
                    ]),
                    View::POS_BEGIN => new JsExpression('begin code block', [
                        $this->construct('ischenko\yii2\jsloader\requirejs\Module', ['mod'],
                            ['getFiles' => ['file' => []]])
                    ]),
                    View::POS_READY => new JsExpression(null, [
                        $this->construct('ischenko\yii2\jsloader\requirejs\Module', ['/file1'],
                            ['getFiles' => ['file' => []]])
                    ]),
                ],
                "require([\"mod\"], function() {\nbegin code block\nend code block\nrequire([\"/file1\"], function() {\n\nrequire([\"/file1\"], function() {\nload code block\n});\n});\n});"
            ],
            [
                [
                    View::POS_END => new JsExpression('end code block'),
                    View::POS_LOAD => new JsExpression('load code block', [
                        $this->construct('ischenko\yii2\jsloader\requirejs\Module', ['/file1'],
                            ['getFiles' => ['file' => []]])
                    ]),
                    View::POS_BEGIN => new JsExpression('begin code block', [
                        $this->construct('ischenko\yii2\jsloader\requirejs\Module', ['mod'],
                            ['getFiles' => ['file' => []]])
                    ]),
                    View::POS_READY => new JsExpression(new JsExpression('test'), [
                        $this->construct('ischenko\yii2\jsloader\requirejs\Module', ['ready'],
                            ['getFiles' => ['file' => []]])
                    ]),
                ],
                "require([\"mod\"], function() {\nbegin code block\nend code block\nrequire([\"ready\"], function() {\njQuery(function() {\ntest\n});\nrequire([\"/file1\"], function() {\nload code block\n});\n});\n});"
            ]
        ];
    }

    public function testPublishRequireJs()
    {
        $this->specify('it publishes and registers the requirejs library from bower package', function () {
            $loader = $this->mockLoader([
                'main' => false,
                'view' => $this->tester->mockView([
                    'registerJsFile' => Expected::once(function ($path, $options) {
                        verify($path)->equals('/require.js');
                        verify($options)->hasKey('position');
                        verify($options)->hasntKey('defer');
                        verify($options)->hasntKey('async');
                        verify($options['position'])->equals(View::POS_END);
                    }),
                    'assetManager' => $this->makeEmpty('yii\web\AssetManager', [
                        'publish' => Expected::once(function ($path) {
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
                    'registerJsFile' => Expected::once(function ($path, $options) {
                        verify($path)->equals('/requirejs.js');
                        verify($options)->hasntKey('async');
                        verify($options)->hasntKey('defer');
                    }),
                    'assetManager' => $this->makeEmpty('yii\web\AssetManager', [
                        'publish' => Expected::never()
                    ])
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
                    'registerJs' => Expected::atLeastOnce(function ($code, $position) use (&$data) {
                        $expected = array_shift($data);

                        verify($position)->equals($expected[0]);
                        verify($code)->equals($expected[1]);
                    }),
                    'assetManager' => Stub::makeEmpty('yii\web\AssetManager', [
                        'publish' => Expected::once(function () {
                            return [null, '/require.js'];
                        })
                    ], $this)
                ], $this)
            ]);

            $publishRequireJs = $this->tester->getMethod($loader, 'publishRequireJs');

            $publishRequireJs->invokeArgs($loader, ['code']);
        });

        $this->specify('it writes generated code into a file and then sets it as data-main entry',
            function ($main, $expectedMain, $expectedPublish) {
                $loader = $this->mockLoader([
                    'main' => $main,
                    'libraryUrl' => '/require.js',
                    'view' => $this->tester->mockView([
                        'registerJs' => Expected::once(),
                        'registerJsFile' => Expected::once(function ($file, $options) use ($expectedMain) {
                            verify($file)->equals('/require.js');
                            verify($options)->hasKey('position');
                            verify($options)->hasKey('async');
                            verify($options)->hasKey('defer');
                            verify($options['position'])->equals(View::POS_END);
                            verify($options)->hasKey('data-main');
                            verify($options['data-main'])->equals($expectedMain);
                        }),
                        'assetManager' => $this->makeEmpty('yii\web\AssetManager', [
                            'publish' => Expected::exactly($expectedPublish, function ($path) use ($expectedMain) {
                                verify($path)->equals(Yii::getAlias('@runtime/jsloader/' . ltrim($expectedMain,
                                        DIRECTORY_SEPARATOR)));
                                verify("code")->equalsFile(Yii::getAlias($path));
                                return [null, '/' . basename($path)];
                            })
                        ])
                    ], $this)
                ]);

                $publishRequireJs = $this->tester->getMethod($loader, 'publishRequireJs');

                $publishRequireJs->invokeArgs($loader, ['code']);
            }, [
                'examples' => [
                    [null, '/' . md5('code') . '.js', 1],
                    ['', '/' . md5('code') . '.js', 1],
                    [' ', '/' . md5('code') . '.js', 1],
                    [
                        function ($code) {
                            verify($code)->equals('code');
                            return '/main_from_callable.js';
                        },
                        '/main_from_callable.js',
                        1
                    ],
                    ['/main.js', '/main.js', 1],
                ]
            ]);

        $file = Yii::getAlias('@runtime/jsloader') . '/' . md5('code') . '.js';

        $this->afterSpecify(function () use ($file) {
            rmdir($file);
            $this->cleanSpecify();
        });

        $this->specify('it throws an exception if it fails to write data-main file', function () use ($file) {
            $this->assertThrows('RuntimeException', function () use ($file) {
                $loader = $this->mockLoader();
                $loader->runtimePath = '/var/run';
                $publishRequireJs = $this->tester->getMethod($loader, 'publishRequireJs');
                @unlink($file);
                @mkdir($file);
                $publishRequireJs->invokeArgs($loader, ['code']);
            });
        });
    }

    /**
     * @dataProvider providerRenderRequireConfigOptions
     */
    public function testRenderRequireConfig($config, $expected)
    {
        $loader = $this->mockLoader([
            'getConfig' => Expected::once(function () use ($config) {
                return $this->tester->mockConfigInterface([
                    'toArray' => Expected::once(function () use ($config) {
                        return $config;
                    })
                ], $this);
            })
        ]);

        verify($this->tester->getMethod($loader, 'renderRequireConfig')->invoke($loader))->equals($expected);
    }

    public function providerRenderRequireConfigOptions()
    {
        return [
            [[], 'var require = {};'],
            [['paths' => []], 'var require = {};'],
            [['paths' => ['test' => 'file']], 'var require = {"paths":{"test":"file"}};'],
            [
                ['paths' => ['test' => 'file'], 'shim' => ['test' => ['deps' => ['file2']]]],
                'var require = {"paths":{"test":"file"},"shim":{"test":{"deps":["file2"]}}};'
            ],
        ];
    }

    protected function _before()
    {
        parent::_before();
    }

    protected function mockLoader($params = [])
    {
        if (isset($params['view'])) {
            $view = $params['view'];
            unset($params['view']);
        } else {
            $view = $this->tester->mockView();
        }

        return $this->construct('ischenko\yii2\jsloader\RequireJs', [$view], $params);
    }
}
