<?php
if (!defined('IMAGETYPE_WEBP')) {
	define('IMAGETYPE_WEBP', 18);
}

if (!defined('IMAGETYPE_AVIF')) {
	define('IMAGETYPE_AVIF', 19);
}

if (!function_exists('imagewebp')) {
	function imagewebp($p1 = null, $p2 = null, $p3 = null)
	{
		return false;
	}
}
if (!function_exists('imagecreatefromavif')) {
	function imagecreatefromavif($p1 = null, $p2 = null, $p3 = null)
	{
		return false;
	}
}


if (!function_exists('imageavif')) {
	function imageavif($p1 = null, $p2 = null, $p3 = null)
	{
		return false;
	}
}

if (file_exists(__DIR__ . "/install/module.cfg.php")) {
	include(__DIR__ . "/install/module.cfg.php");
};

use Bitrix\Main\Loader;

Loader::includeModule($arModuleCfg['MODULE_ID']);


$arClasses = array(
	/* Библиотеки и классы для авто загрузки */
	'IS_PRO\img2picture\CSimpleImage' => 'classes/general/CSimpleImage.php',
	'IS_PRO\img2picture\CImageManupulator' => 'classes/general/CImageManupulator.php',
	'IS_PRO\img2picture\Cimg2picture' => 'classes/general/Cimg2picture.php'
);

Loader::registerAutoLoadClasses($arModuleCfg['MODULE_ID'], $arClasses);
