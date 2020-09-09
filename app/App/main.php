<?php
namespace Moorexa\Framework;

use Lightroom\Packager\Moorexa\MVC\Controller;
use function Lightroom\Templates\Functions\{render, redirect, json, view};
/**
 * Documentation for App Page can be found in App/readme.txt
 *
 *@package      App Page
 *@author       Moorexa <www.moorexa.com>
 *@author       Amadi Ifeanyi <amadiify.com>
 **/

class App extends Controller
{
    /**
    * @method App home
    *
    * See documentation https://www.moorexa.com/doc/controller
    *
    * You can catch params sent through the $_GET request
    * @return void
    **/

    public function home() : void 
    {
        $this->view->render('home');
    }
}