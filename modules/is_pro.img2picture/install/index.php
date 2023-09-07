<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
Loc::loadMessages(__FILE__);
if (class_exists('is_pro_img2picture')) {
	return;
}
Class is_pro_img2picture extends CModule
{
	public function __construct()
	{
		if(file_exists(__DIR__."/module.cfg.php")){
			include(__DIR__."/module.cfg.php");
			$this->arModuleCfg =  $arModuleCfg;
		}
		if(file_exists(__DIR__."/version.php")){
			$arModuleVersion = array();
			include(__DIR__."/version.php");
			$this->MODULE_ID 		   = $arModuleCfg['MODULE_ID'];
			$this->MODULE_VERSION  	   = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
			$this->MODULE_NAME 		   = Loc::getMessage("ISPRO_IMG2PICTURE_NAME");
			$this->MODULE_DESCRIPTION  = Loc::getMessage("ISPRO_IMG2PICTURE_DESC");
			$this->PARTNER_NAME 	   = Loc::getMessage("ISPRO_IMG2PICTURE_PARTNER_NAME");
			$this->PARTNER_URI  	   = Loc::getMessage("ISPRO_IMG2PICTURE_PARTNER_URI");
		}
	}


	public function DoInstall()
	{
		global $DB, $APPLICATION, $step;
		$this->InstallEvents();
		ModuleManager::registerModule($this->MODULE_ID);
		$this->SetDefaultOptions();
		return true;
	}

	public function DoUninstall()
	{
		global $DB, $APPLICATION, $step;
		$this->UnInstallEvents();
		$this->RemoveOptions();
		ModuleManager::unRegisterModule($this->MODULE_ID);
		return true;
	}


	public function InstallEvents()
	{
		RegisterModuleDependences("main", "OnEpilog",	$this->MODULE_ID,"IS_PRO\img2picture\Cimg2picture", "SetParamsJS");
		RegisterModuleDependences("main", "OnEndBufferContent", $this->MODULE_ID, "IS_PRO\img2picture\Cimg2picture", "img2picture");
		return false;
	}

	public function SetDefaultOptions()
	{
		$options_list = $this->arModuleCfg['options_list'];
		foreach ($options_list as $option_name => $arOption) {
			$option[$option_name] = $arOption['default'];
			\Bitrix\Main\Config\Option::set($this->MODULE_ID, $option_name, $option[$option_name]);
		}
	}

	public function RemoveOptions()
	{
		COption::RemoveOption($this->MODULE_ID);
	}

	public function UnInstallEvents()
	{
		UnRegisterModuleDependences("main", "OnEpilog", $this->MODULE_ID, "IS_PRO\img2picture\Cimg2picture", "SetParamsJS");
		UnRegisterModuleDependences("main", "OnEndBufferContent", $this->MODULE_ID, "IS_PRO\img2picture\Cimg2picture", "img2picture");
		return false;
	}

}
