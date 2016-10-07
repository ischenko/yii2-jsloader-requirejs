<?php

namespace ischenko\yii2\jsloader\tests\unit\requirejs;

use ischenko\yii2\jsloader\requirejs\Module;

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

    public function testAddFile()
    {
        verify($this->module->getFiles())->equals([]);
        verify($this->module->addFile('file'))->same($this->module);
        verify($this->module->getFiles())->equals(['file' => []]);

        $this->module->addFile('file.js');
        $this->module->addFile('file.sj');
        $this->module->addFile('file1.js');

        verify($this->module->getFiles())->equals(['file' => [], 'file.sj' => [], 'file1' => []]);
    }

    public function testFallbackFiles()
    {
        verify($this->module->getFallbackFiles())->equals([]);
        verify($this->module->addFallbackFiles(['file1.js', 'key' => 'file2', 'file3.sj']))->same($this->module);
        verify($this->module->getFallbackFiles())->equals(['file1', 'file2', 'file3.sj']);
    }

    public function testClearFallbackFiles()
    {
        verify($this->module->getFallbackFiles())->equals([]);
        verify($this->module->addFallbackFiles(['file1.js', 'key' => 'file2', 'file3.sj']));
        verify($this->module->getFallbackFiles())->equals(['file1', 'file2', 'file3.sj']);
        verify($this->module->clearFallbackFiles())->same($this->module);
        verify($this->module->getFallbackFiles())->equals([]);
    }

    public function testExports()
    {
        $this->specify('it filters exports value', function($value, $expected) {
            verify($this->module->getExports())->null();
            verify($this->module->setExports($value))->same($this->module);
            verify($this->module->getExports())->equals($expected);
        }, ['examples' => [
            ['', null],
            [' ', null],
            ['test', 'test'],
            [' test', 'test'],
        ]]);

        $this->specify('it throws an exception if value is not a string', function() {
            $this->module->setExports([]);
        }, ['throws' => 'yii\base\InvalidParamException']);
    }
}
