<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit181e3d83ed7601dfbaf713cf3f372eff
{
	public static $prefixLengthsPsr4 = [
		'R' =>
			[
				'RegularLabs\\Plugin\\System\\Snippets\\' => 35,
			],
	];

	public static $prefixDirsPsr4 = [
		'RegularLabs\\Plugin\\System\\Snippets\\' =>
			[
				0 => __DIR__ . '/../..' . '/src',
			],
	];

	public static function getInitializer(ClassLoader $loader)
	{
		return \Closure::bind(function () use ($loader) {
			$loader->prefixLengthsPsr4 = ComposerStaticInit181e3d83ed7601dfbaf713cf3f372eff::$prefixLengthsPsr4;
			$loader->prefixDirsPsr4    = ComposerStaticInit181e3d83ed7601dfbaf713cf3f372eff::$prefixDirsPsr4;
		}, null, ClassLoader::class);
	}
}