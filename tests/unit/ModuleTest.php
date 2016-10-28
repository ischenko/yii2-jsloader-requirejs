<?php

namespace ischenko\yii2\jsloader\tests\unit\requirejs;

use ischenko\yii2\jsloader\requirejs\Module;
use yii\web\JsExpression;

class ModuleTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    /**
     * @var \ischenko\yii2\jsloader\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        parent::_before();

        $this->module = new Module('test');
    }

    /** Tests go below */

    public function testInstance()
    {
        verify($this->module)->isInstanceOf('ischenko\yii2\jsloader\ModuleInterface');
        verify($this->module)->isInstanceOf('ischenko\yii2\jsloader\requirejs\Module');
    }

    public function testFallbackFiles()
    {
        verify($this->module->getFallbackFiles())->equals([]);
        verify($this->module->addFallbackFiles(['file1.js', 'key' => 'file2', 'file3.sj']))->same($this->module);
        verify($this->module->getFallbackFiles())->equals(['file1.js', 'file2', 'file3.sj']);
    }

    public function testClearFallbackFiles()
    {
        verify($this->module->getFallbackFiles())->equals([]);
        verify($this->module->addFallbackFiles(['file1.js', 'key' => 'file2', 'file3.sj']));
        verify($this->module->getFallbackFiles())->equals(['file1.js', 'file2', 'file3.sj']);
        verify($this->module->clearFallbackFiles())->same($this->module);
        verify($this->module->getFallbackFiles())->equals([]);
    }

    public function testExports()
    {
        $this->specify('it filters exports value', function ($value, $expected) {
            verify($this->module->getExports())->null();
            verify($this->module->setExports($value))->same($this->module);
            verify($this->module->getExports())->equals($expected);
        }, ['examples' => [
            ['', null],
            [' ', null],
            ['test', 'test'],
            [' test', 'test'],
        ]]);

        $this->specify('it throws an exception if value is not a string', function () {
            $this->module->setExports([]);
        }, ['throws' => 'yii\base\InvalidParamException']);
    }

    public function testInitProperty()
    {
        verify($this->module->setInit('alert(1);'))->same($this->module);
        verify($this->module->getInit())->isInstanceOf(JsExpression::className());
        verify($this->module->getInit()->expression)->equals('alert(1);');

        $this->module->setInit($this->module->getInit());
        verify($this->module->getInit())->isInstanceOf(JsExpression::className());
        verify($this->module->getInit()->expression)->equals('alert(1);');


    }

    public function testRequireJsOptions()
    {
        $options = [
            'requirejs' => [
                'alias' => 'testing',
                'init' => 'test;',
                'exports' => 'test'
            ],
            'test' => 't'
        ];

        verify($this->module->setOptions($options))->same($this->module);
        verify($this->module->getOptions())->equals(['test' => 't']);

        verify($this->module->getAlias())->equals('testing');
        verify($this->module->getExports())->equals('test');
        verify($this->module->getInit())->isInstanceOf('yii\web\JsExpression');

        $this->tester->expectException('yii\base\UnknownPropertyException', function() {
            $this->module->setOptions(['requirejs' => ['unknown' => 1]]);
        });
    }
}
