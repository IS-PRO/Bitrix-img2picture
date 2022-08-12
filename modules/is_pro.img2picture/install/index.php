<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
Loc::loadMessages(__FILE__);

Class is_pro_img2picture extends CModule
{
	public function __construct()
	{
		if(file_exists(__DIR__."/module.cfg.php")){
			include(__DIR__."/module.cfg.php");
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
		return false;
	}


	public function DoInstall()
	{
		global $DB, $APPLICATION, $step;
		$this->InstallEvents();
		$this->SetDefaultOptions();
		ModuleManager::registerModule($this->MODULE_ID);
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
		RegisterModuleDependences("main", "OnEndBufferContent", $this->MODULE_ID, "IS_PRO\img2picture\Main", "img2picture");
		return false;
	}

	public function SetDefaultOptions()
	{
		include(__DIR__."/module.cfg.php");
		include(__DIR__ . "/../default_option.php");
		foreach ($options_list as $option_name => $option_type) {
			$option[$option_name] = $is_pro_img2picture_default_options[$option_name];
			\Bitrix\Main\Config\Option::set($arModuleCfg['MODULE_ID'], $option_name, $option[$option_name]);
		}
	}

	public function RemoveOptions()
	{
		include(__DIR__."/module.cfg.php");
		COption::RemoveOption($arModuleCfg['MODULE_ID'], "");
	}

	public function UnInstallEvents()
	{
		UnRegisterModuleDependences("main", "OnEndBufferContent", $this->MODULE_ID, "IS_PRO\img2picture\Main", "img2picture");
		return false;
	}

}
