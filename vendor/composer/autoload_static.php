<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitda7c01b6e2d1c37ef2f0ec7e997c532b
{
    public static $files = array (
        '3ef87127dc6892a0a78f223558a0b940' => __DIR__ . '/..' . '/diff/diff/Diff.php',
    );

    public static $prefixLengthsPsr4 = array (
        'D' => 
        array (
            'Diff\\' => 5,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Diff\\' => 
        array (
            0 => __DIR__ . '/..' . '/diff/diff/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitda7c01b6e2d1c37ef2f0ec7e997c532b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitda7c01b6e2d1c37ef2f0ec7e997c532b::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
