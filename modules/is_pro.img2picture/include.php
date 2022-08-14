<?php
if (file_exists(__DIR__ . "/install/module.cfg.php")) {
	include(__DIR__ . "/install/module.cfg.php");
};

CModule::IncludeModule($arModuleCfg['MODULE_ID']);
global $DBType;

$arClasses=array(
	/* Библиотеки и классы для авто загрузки */
	'IS_PRO\img2picture\CSimpleImage'=>'classes/general/CSimpleImage.php',
	'IS_PRO\img2picture\CImageManupulator'=>'classes/general/CImageManupulator.php',
	'IS_PRO\img2picture\Cimg2picture'=>'classes/general/Cimg2picture.php'
);

CModule::AddAutoloadClasses($arModuleCfg['MODULE_ID'], $arClasses);
