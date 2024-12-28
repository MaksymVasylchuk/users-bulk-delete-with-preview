<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6f01e40db7d45813d5a10febe976a47c
{
    public static $prefixLengthsPsr4 = array (
        'U' => 
        array (
            'UsersBulkDeleteWithPreview\\' => 27,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'UsersBulkDeleteWithPreview\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6f01e40db7d45813d5a10febe976a47c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6f01e40db7d45813d5a10febe976a47c::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit6f01e40db7d45813d5a10febe976a47c::$classMap;

        }, null, ClassLoader::class);
    }
}