<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc7570cda262eb52814ca9f69435bc001
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
        'A' => 
        array (
            'Avlas99\\Tanis\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
        'Avlas99\\Tanis\\' => 
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
            $loader->prefixLengthsPsr4 = ComposerStaticInitc7570cda262eb52814ca9f69435bc001::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc7570cda262eb52814ca9f69435bc001::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitc7570cda262eb52814ca9f69435bc001::$classMap;

        }, null, ClassLoader::class);
    }
}
