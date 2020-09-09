<?php

use Lightroom\Router\Guards;
use Lightroom\Packager\Moorexa\Router;

/**
 * @package App configuration file
 * @author Moorexa Software foundation 
 */

 // this file contains all the basic configuration for App controller
 return [
    // let's describe the controller directory structure
    // using init defaults.
    'directory' => [
        'model'     => CONTROLLER_MODEL, 
        'view'      => CONTROLLER_VIEW,
        'custom'    => CONTROLLER_CUSTOM,
        'package'   => CONTROLLER_PACKAGE,
        'partial'   => CONTROLLER_PARTIAL,
        'static'    => CONTROLLER_STATIC,
        'provider'  => CONTROLLER_PROVIDER
    ],

    // define the main entry file
    'main.entry' => 'main.php',

    // define the default provider
    'default.provider' => 'Provider.php',

    // override default view
    'default.view' => Router::readConfig('router.default.view'),

    // set a default model
    'default.model' => null,

    // override static json file for css
    'static.css' => [],
    
    // override static json file for js
    'static.js' => [],

    // controller guard
    'controller.guard' => function(array &$incomingUrl)
    {
        // Here is a basic example below.
        // if this guard implements the route guard interface, it can alter the controller, view and arguments.
        // $incomingUrl = Guards::loadGuard(<guard class>, <optional method in guard>, $incomingUrl);
    },

    // view guard
    'view.guard' => [
        /* Here is a basic example
        '<view>|<view>' => function()
        {
           Guards::loadGuard(<guard class>, <optional method in guard>);
        }
        */
    ]
 ];