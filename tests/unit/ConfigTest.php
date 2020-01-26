<?php

namespace ischenko\yii2\jsloader\tests\unit\requirejs;

use Codeception\AssertThrows;
use Codeception\Specify;
use Codeception\Test\Unit;
use ischenko\yii2\jsloader\requirejs\Config;
use ischenko\yii2\jsloader\tests\UnitTester;
use yii\web\JsExpression;

class ConfigTest extends Unit
{
    use AssertThrows;
    use Specify;

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Config
     * @specify
     */
    public $config;

    protected function _before()
    {
        parent::_before();

        $this->config = new Config();
    }

    /** Tests go below */

    public function testInstance()
    {
        verify($this->config)->isInstanceOf('ischenko\yii2\jsloader\ConfigInterface');
        verify($this->config)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Config');
    }

    public function testAddModule()
    {
        verify($this->config->addModule('test'))->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');
    }

    public function testPathsSetter()
    {
        verify($this->config->getModules())->equals([]);

        $this->specify('it creates modules and add files to them', function () {
            verify($this->config->setPaths(['test' => ['test.js']]))->same($this->config);

            $testModule = $this->config->getModule('test');

            verify($testModule)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');
            verify($testModule->getFiles(false))->equals(['test.js' => []]);

            $this->config->setPaths(['test' => ['test2.js']]);
            verify($testModule->getFiles(false))->equals(['test2.js' => []]);
            verify($testModule)->same($this->config->getModule('test'));

            $this->config->setPaths(['test' => ['test2.js'], 'test2' => 'test.js']);

            $testModule = $this->config->getModule('test');
            $testModule2 = $this->config->getModule('test2');

            verify($testModule->getFiles(false))->equals(['test2.js' => []]);
            verify($testModule2)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');
            verify($testModule2->getFiles(false))->equals(['test.js' => []]);
        });

        $this->specify('it skips modules without files', function () {
            verify($this->config->setPaths(['test' => []]))->same($this->config);
            verify($this->config->getModule('test'))->null();
        });

        $this->specify('it adds fallback files to a module if they exist', function ($paths, $expected) {
            $this->config->setPaths($paths);
            verify($this->config->getModule('test')->getFallbackFiles())->equals($expected);
        }, [
            'examples' => [
                [['test' => ['file1']], []],
                [['test' => ['file1', 'file2']], ['file2']],
                [['test' => ['file1', 'file2', 'file3.js']], ['file2', 'file3.js']],
            ]
        ]);
    }

    public function testShimSetter()
    {
        verify($this->config->getModules())->equals([]);
        verify($this->config->setShim(['test' => ['deps' => ['test.js']]]))->same($this->config);

        $module = $this->config->getModule('test');
        $dep1 = $this->config->getModule(md5('test.js'));

        verify($dep1)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');
        verify($module)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');
        verify($module->getDependencies())->equals([$dep1->getName() => $dep1]);

        $this->config->setShim(['test' => ['deps' => ['test2.js']]]);

        $dep2 = $this->config->getModule(md5('test2.js'));

        verify($dep1)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');
        verify($module->getDependencies())->equals([$dep2->getName() => $dep2]);

        $this->config->setShim(['test' => ['exports' => ' ']]);
        verify($module->getExports())->null();

        $this->config->setShim(['test' => ['exports' => 'test']]);
        verify($module->getExports())->equals('test');

        $this->config->setShim(['test' => ['exports' => null]]);
        verify($module->getExports())->null();
    }

    public function testToArray()
    {
        verify($this->config->getModules())->isEmpty();

        $this->specify('it renders paths and shims', function () {
            $this->config->setPaths([
                'test' => ['test2.js'],
                'test2' => 'test.js',
                'test3' => ['t1', 't2', 't3'],
            ]);

            $testModule = $this->config->getModule('test');
            $testModule2 = $this->config->getModule('test2');

            verify($testModule)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');

            $testModule->addFile('another_file.js');
            $testModule->addFile('yet_another_file.js');
            $testModule->setOptions(['baseUrl' => '/base/url']);
            $testModule2->setOptions(['baseUrl' => '/base/url']);

            $this->config->setShim([
                'test' => [
                    'deps' => ['test2']
                ],
                'test2' => [
                    'exports' => 'library',
                    'init' => 'alert'
                ],
                'test3' => [],
                'test4' => [
                    'exports' => 'something'
                ],
            ]);

            $this->config->setAliases([
                'test4' => 'TESTING4'
            ]);

            verify($test2 = $this->config->getModule('test2'))->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');
            verify($expression = $test2->getInit())->isInstanceOf('yii\web\JsExpression');

            $testModule4 = $this->config->getModule('test4');
            $testModule4->setOptions(['async' => 1]);
            $testModule4->addFile('t1')->addFile('t2')->addFile('t3');

            verify($this->config->toArray())->equals([
                'shim' => [
                    'test' => [
                        'deps' => ['test2', 'test2.js', 'another_file.js']
                    ],
                    'test2' => [
                        'exports' => 'library',
                        'init' => $expression
                    ],
                    'test2.js' => [
                        'deps' => ['test2']
                    ],
                    'another_file.js' => [
                        'deps' => ['test2', 'test2.js']
                    ],
                    'TESTING4' => [
                        'deps' => ['t1', 't2'],
                        'exports' => 'something'
                    ],
                    't2' => [
                        'deps' => []
                    ],
                    't1' => [
                        'deps' => []
                    ]
                ],
                'paths' => [
                    'test' => ['yet_another_file'],
                    'test2' => ['test'],
                    'test3' => ['t1', 't2', 't3'],
                    'TESTING4' => ['t3']
                ]
            ]);
        });

        $this->specify('it renders baseUrl in paths section if module has baseUrl property and does not have any files',
            function () {
                $this->config->setShim([
                    'test' => [
                        'exports' => 'test'
                    ],
                ]);

                $testModule = $this->config->getModule('test');
                $testModule->setOptions(['baseUrl' => '/testing123']);

                verify($this->config->toArray())->equals([
                    'shim' => [
                        'test' => [
                            'exports' => 'test'
                        ],
                    ],
                    'paths' => [
                        'test' => '/testing123',
                    ]
                ]);
            });

        $this->specify('it renders baseUrl and other properties', function () {
            $this->config->baseUrl = 'test';
            $this->config->enforceDefine = true;
            $this->config->waitSeconds = 120;
            $this->config->context = 'context';
            $this->config->deps = ['deps'];
            $this->config->xhtml = true;
            $this->config->urlArgs = 'arg1=1';
            $this->config->scriptType = 'text/javascript';
            $this->config->skipDataMain = false;

            verify($this->config->toArray())->equals([
                'enforceDefine' => true,
                'waitSeconds' => 120,
                'context' => 'context',
                'deps' => ['deps'],
                'xhtml' => true,
                'urlArgs' => 'arg1=1',
                'scriptType' => 'text/javascript',
                'skipDataMain' => false,
                'baseUrl' => 'test'
            ]);
        });
    }

    public function testAttributesSetter()
    {
        $this->specify('it allows to set valid options', function () {
            $this->config->config = ['test' => 1];
            verify($this->config->config)->equals(['test' => 1]);
        });

        $this->specify('it allows to get valid options', function () {
            verify($this->config->config)->null();
        });

        // it throws an exception if property cannot be set
        $this->assertThrows('yii\base\UnknownPropertyException', function () {
            $this->config->unknown = ['test' => 1];
        });

        // it throws an exception if property cannot be get
        $this->assertThrows('yii\base\UnknownPropertyException', function () {
            $unknown = $this->config->unknown;
        });

        $this->specify('it wraps callback option with JsExpression', function () {
            $this->config->callback = 'alert(1);';
            verify($this->config->callback)->isInstanceOf(JsExpression::class);
            verify($this->config->callback->expression)->equals('alert(1);');
        });
    }
}
