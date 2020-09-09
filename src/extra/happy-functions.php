<?php

use Lightroom\Adapter\ClassManager;
use Lightroom\Packager\Moorexa\Helpers\Assets;

/**
 * @method Happy Asset
 * @return Asssets
 */
function assets() : Assets 
{
    return ClassManager::singleton(Assets::class);
}

/**
 * @method HappyWebEngine assets_image
 * @param string $image
 * @return string
 */
function assets_image(string $image) : string
{
    return assets()->image($image);
}

/**
 * @method HappyWebEngine assets_js
 * @param string $js
 * @return string
 */
function assets_js(string $js) : string
{
    return assets()->js($js);
}

/**
 * @method HappyWebEngine assets_media
 * @param string $media
 * @return string
 */
function assets_media(string $media) : string
{
    return assets()->media($media);
}

/**
 * @method HappyWebEngine assets_css
 * @param string $css
 * @return string
 */
function assets_css(string $css) : string
{
    return assets()->css($css);
}

/**
 * @method HappyWebEngine web_url
 * @param string $path
 * @return string
 */
function web_url(string $path) : string
{
    return func()->url($path);
}

/**
 * @method HappyWebEngine web_secure_url
 * @param string $path
 * @return string
 */
function web_secure_url(string $path) : string
{
    return func()->url(rawurlencode($path));
}