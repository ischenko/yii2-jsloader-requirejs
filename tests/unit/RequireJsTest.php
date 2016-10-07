<?php

namespace ischenko\yii2\jsloader\tests\unit\requirejs;

use Codeception\Util\Stub;
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

    public function testRenderRequireBlock()
    {
        $loader = $this->mockLoader();
        $renderRequireBlock = $this->tester->getMethod($loader, 'renderRequireBlock');

        verify($renderRequireBlock->invokeArgs($loader, ['', []]))->equals('');
        verify($renderRequireBlock->invokeArgs($loader, ['test;', []]))
            ->equals("require([], function() {\ntest;\n});");

        $cfg = $loader->getConfig();

        $mod1 = $cfg->addModule('mod1');
        $mod2 = $cfg->addModule('mod2');

        $mod1->setExports('mod1');

        verify($renderRequireBlock->invokeArgs($loader, ['test;', [$mod2, $mod1]]))
            ->equals("require([\"mod2\",\"mod1\"], function(undefined,mod1) {\ntest;\n});");

        $mod1->setExports(null);

        verify($renderRequireBlock->invokeArgs($loader, ['test;', [$mod2, $mod1]]))
            ->equals("require([\"mod2\",\"mod1\"], function() {\ntest;\n});");

        $mod1->setExports('mod1');

        $mod3 = $cfg->addModule('mod3');

        verify($renderRequireBlock->invokeArgs($loader, ['test;', [$mod2, $mod1, $mod3]]))
            ->equals("require([\"mod2\",\"mod1\",\"mod3\"], function(undefined,mod1) {\ntest;\n});");
    }

    public function testDoRender()
    {
        $codeBlocks = $exCodeBlocks = [
            View::POS_END => [
                'code' => 'end code block',
                'depends' => []
            ],
            View::POS_LOAD => [
                'code' => 'load code block',
                'depends' => [Stub::construct('ischenko\yii2\jsloader\requirejs\Module', ['/file1'])]
            ],
            View::POS_BEGIN => [
                'code' => 'begin code block',
            ],
            View::POS_READY => [
                'depends' => [Stub::construct('ischenko\yii2\jsloader\requirejs\Module', ['/file1'])]
            ],
        ];

        krsort($exCodeBlocks);

        $loader = $this->mockLoader([
            'renderRequireBlock' => Stub::exactly(4, function ($code, $depends) use (&$exCodeBlocks) {
                $data = array_shift($exCodeBlocks);

                if (isset($data['code'])) {
                    verify($code)->equals($data['code']);
                } else {
                    verify($code)->equals('');
                }

                if (isset($data['depends'])) {
                    verify($depends)->equals($data['depends']);
                } else {
                    verify($depends)->equals([]);
                }
            }),
            'publishRequireJs' => Stub::once()
        ], $this);

        $doRender = $this->tester->getMethod($loader, 'doRender');

        $doRender->invokeArgs($loader, [$codeBlocks]);
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

            $this->verifyMockObjects();
        });

        $this->specify('it does not publish the requirejs library if the libraryUrl property is set', function () {
            $loader = $this->mockLoader([
                'main' => false,
                'libraryUrl' => '/requirejs.js',
                'view' => $this->tester->mockView([
                    'registerJsFile' => Stub::once(function ($path, $options) {
                        verify($path)->equals('/requirejs.js');
                    }),
                    'assetManager' => Stub::makeEmpty('yii\web\AssetManager', [
                        'publish' => Stub::never()
                    ], $this)
                ], $this)
            ]);

            $publishRequireJs = $this->tester->getMethod($loader, 'publishRequireJs');

            $publishRequireJs->invokeArgs($loader, ['code']);

            $this->verifyMockObjects();
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

            $this->verifyMockObjects();
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
                        verify($options['position'])->equals(View::POS_END);
                        verify($options)->hasKey('data-main');
                        verify($options['data-main'])->equals($expectedMain);
                    }),
                    'assetManager' => Stub::makeEmpty('yii\web\AssetManager', [
                        'publish' => Stub::exactly($expectedPublish, function ($path) {
                            verify($path)->equals(\Yii::getAlias('@runtime/jsloader/requirejs-main.js'));
                            verify("code")->equalsFile(\Yii::getAlias($path));
                            return [null, '/' . basename($path)];
                        })
                    ], $this)
                ], $this)
            ]);

            $publishRequireJs = $this->tester->getMethod($loader, 'publishRequireJs');

            $publishRequireJs->invokeArgs($loader, ['code']);

            $this->verifyMockObjects();
        }, ['examples' => [
            [null, '/requirejs-main.js', 1],
            ['', '/requirejs-main.js', 1],
            [' ', '/requirejs-main.js', 1],
            ['/main.js', '/main.js', 0],
        ]]);

        $file = \Yii::getAlias(RequireJs::RUNTIME_PATH) . '/requirejs-main.js';

        $this->afterSpecify(function () use ($file) {
            rmdir($file);
            $this->cleanSpecify();
        });

        $this->specify('it throws an exception if it fails to write data-main file', function () use ($file) {
            $loader = $this->mockLoader();
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
