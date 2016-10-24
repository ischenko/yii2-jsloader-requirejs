<?php

namespace ischenko\yii2\jsloader\tests\unit\requirejs;

use Codeception\Util\Stub;
use ischenko\yii2\jsloader\requirejs\Config;
use yii\web\JsExpression;

class ConfigTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    /**
     * @var \ischenko\yii2\jsloader\tests\UnitTester
     */
    protected $tester;

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
            verify($testModule->getFiles(false))->equals(['test' => []]);

            $this->config->setPaths(['test' => ['test2.js']]);
            verify($testModule->getFiles(false))->equals(['test2' => []]);
            verify($testModule)->same($this->config->getModule('test'));

            $this->config->setPaths(['test' => ['test2.js'], 'test2' => 'test.js']);

            $testModule = $this->config->getModule('test');
            $testModule2 = $this->config->getModule('test2');

            verify($testModule->getFiles(false))->equals(['test2' => []]);
            verify($testModule2)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');
            verify($testModule2->getFiles(false))->equals(['test' => []]);
        });

        $this->specify('it skips modules without files', function () {
            verify($this->config->setPaths(['test' => []]))->same($this->config);
            verify($this->config->getModule('test'))->null();
        });

        $this->specify('it adds fallback files to a module if they exist', function ($paths, $expected) {
            $this->config->setPaths($paths);
            verify($this->config->getModule('test')->getFallbackFiles())->equals($expected);
        }, ['examples' => [
            [['test' => ['file1']], []],
            [['test' => ['file1', 'file2']], ['file2']],
            [['test' => ['file1', 'file2', 'file3.js']], ['file2', 'file3']],
        ]]);
    }

    public function testShimSetter()
    {
        verify($this->config->getModules())->equals([]);
        verify($this->config->setShim(['test' => ['deps' => ['test.js']]]))->same($this->config);

        $module = $this->config->getModule('test');
        $dep1 = $this->config->getModule(md5('test.js'));

        verify($dep1)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');
        verify($module)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');
        verify($module->getDependencies())->equals([$dep1]);

        $this->config->setShim(['test' => ['deps' => ['test2.js']]]);

        $dep2 = $this->config->getModule(md5('test2.js'));

        verify($dep1)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');
        verify($module->getDependencies())->equals([$dep2]);

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

        $this->config->setPaths([
            'test' => ['test2.js'],
            'test2' => 'test.js',
            'test3' => ['t1', 't2', 't3']
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
                'exports' => 'library'
            ],
            'test3' => []
        ]);

        verify($this->config->toArray())->equals([
            'shim' => [
                'test' => [
                    'deps' => ['test2']
                ],
                'test2' => [
                    'exports' => 'library'
                ]
            ],
            'paths' => [
                'test' => '/base/url',
                'test2' => ['test'],
                'test3' => ['t1', 't2', 't3']
            ]
        ]);
    }

    public function testAttributesSetter()
    {
        $this->specify('it allows to set valid options', function() {
            $this->config->config = ['test' => 1];
            verify($this->config->config)->equals(['test' => 1]);
        });

        $this->specify('it allows to get valid options', function() {
            verify($this->config->config)->null();
        });

        $this->specify('it throws an exception if property cannot be set', function() {
            $this->config->unknown = ['test' => 1];
        }, ['throws' => 'yii\base\UnknownPropertyException']);

        $this->specify('it throws an exception if property cannot be get', function() {
            $unknown = $this->config->unknown;
        }, ['throws' => 'yii\base\UnknownPropertyException']);

        $this->specify('it wraps callback option with JsExpression', function() {
            $this->config->callback = 'alert(1);';
            verify($this->config->callback)->isInstanceOf(JsExpression::className());
            verify($this->config->callback->expression)->equals('alert(1);');
        });
    }
}
