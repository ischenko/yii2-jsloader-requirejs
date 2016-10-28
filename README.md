# yii2-jsloader-requirejs

[![Build Status](https://travis-ci.org/ischenko/yii2-jsloader-requirejs.svg?branch=master)](https://travis-ci.org/ischenko/yii2-jsloader-requirejs)
[![Code Climate](https://codeclimate.com/github/ischenko/yii2-jsloader-requirejs/badges/gpa.svg)](https://codeclimate.com/github/ischenko/yii2-jsloader-requirejs)
[![Test Coverage](https://codeclimate.com/github/ischenko/yii2-jsloader-requirejs/badges/coverage.svg)](https://codeclimate.com/github/ischenko/yii2-jsloader-requirejs/coverage)

An Yii2 extension that allows to register asset bundles as [RequireJS](http://requirejs.org) modules.

## Installation
*Requires PHP >= 5.4*

*Requires [ischenko/yii2-jsloader](https://github.com/ischenko/yii2-jsloader) >= 1.0*

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run
```
composer require ischenko/yii2-jsloader-requirejs
```

or add

```json
"ischenko/yii2-jsloader-requirejs": "*"
```

to the `require` section of your composer.json.

## Usage

Add the [behavior](https://github.com/ischenko/yii2-jsloader#usage) and requirejs loader to the view configuration

```php
    ...
    'components' => [
        ...
        'view' => [
            'as jsLoader' => [
                'class' => 'ischenko\yii2\jsloader\Behavior',
                'loader' => [
                    'class' => 'ischenko\yii2\jsloader\RequireJs',
                ]
            ]
        ]
        ...
    ]
    ...
```

Additionally modules configuration accepts options described in [RequireJS API docs](http://requirejs.org/docs/api.html#config): 

```php
    ...
    'components' => [
        ...
        'view' => [
            'as jsLoader' => [
                'class' => 'ischenko\yii2\jsloader\Behavior',
                'loader' => [
                    'config' => [
                        'shim' => [
                            'yii\web\JqueryAsset' => [
                                'exports' => 'jQuery'
                            ]
                        ],
                    ],
                    'class' => 'ischenko\yii2\jsloader\RequireJs',
                ]
            ]
        ]
        ...
    ]
    ...
```

##License
**MIT**
