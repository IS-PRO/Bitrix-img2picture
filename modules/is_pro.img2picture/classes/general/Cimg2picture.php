<?
namespace IS_PRO\img2picture;

use IS_PRO\img2picture\CImageManupulator;

if (class_exists('\IS_PRO\img2picture\Cimg2picture')) {
	return;
}

class Cimg2picture
{

	public static function SetParamsJS()
	{
		if (php_sapi_name() == 'cli') {
			return;
		}

		global $USER;
		if (isset($USER) && $USER->IsAdmin()) {
			return;
		}
		if (\CSite::InDir('/bitrix/')) {
			return;
		}
		$option = self::GetOptions();

		if ($option['MODULE_MODE'] !== 'on') {
			return;
		}
		if (trim($option['EXCEPTIONS_DIR'])) {
			$dirs = explode("\n", $option['EXCEPTIONS_DIR']);
			if (is_array($dirs)) {
				foreach ($dirs as $dir) {
					if (\CSite::InDir($dir)) {
						return;
					}
				}
			}
		}

		$jsPath   = str_replace(
			[$option['DOCUMENT_ROOT'], 'classes/general'],
			['', 'lib/js/'],
			__DIR__
		);

		\Bitrix\Main\Page\Asset::getInstance()->addJs($jsPath.'lozad.min.js');
		\Bitrix\Main\Page\Asset::getInstance()->addJs($jsPath.'img2picture.min.js');
	}

	public static function img2picture(&$content)
	{
		if (php_sapi_name() == 'cli') {
			return;
		}

		global $USER;
		if (isset($USER) && $USER->IsAdmin()) {
			return;
		}
		if (\CSite::InDir('/bitrix/')) {
			return;
		}
		if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
			return;
		}
		if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
			return;
		}

		$option = self::GetOptions();

		if ($option['MODULE_MODE'] !== 'on') {
			return;
		}
		if (trim($option['EXCEPTIONS_DIR'])) {
			$dirs = explode("\n", $option['EXCEPTIONS_DIR']);
			if (is_array($dirs)) {
				foreach ($dirs as $dir) {
					if (\CSite::InDir($dir)) {
						return;
					}
				}
			}
		}
		$content = self::doIt($content, $option);
	}

	public static function doIt(string $content, array $option = [])
	{
		if (count($option) == 0) {
			$option = self::GetOptions();
		}
		if (empty($option['DOCUMENT_ROOT'])) {
			$option['DOCUMENT_ROOT'] = \Bitrix\Main\Application::getDocumentRoot();
		}
		$img2picture = new CImageManupulator($option);
		$img2picture->doIt($content);
		return $content;
	}

	public static function MakeWebp(string $src, array $option = [])
	{
		if (count($option) == 0) {
			$option = self::GetOptions();
		}
		$img2picture = new CImageManupulator($option);
		return $img2picture->ConvertImg2webp($src);
	}

	public static function MakeAvif(string $src, array $option = [])
	{
		if (count($option) == 0) {
			$option = self::GetOptions();
		}
		$img2picture = new CImageManupulator($option);
		return $img2picture->ConvertImg2avif($src);
	}

	public static function GetOptions() {
		include(__DIR__ . "/../../install/module.cfg.php");
		$options_list = $arModuleCfg['options_list'];
		foreach ($options_list as $option_name => $arOption) {
			$option[$option_name] = \Bitrix\Main\Config\Option::get($arModuleCfg['MODULE_ID'], $option_name, SITE_ID);
			if ($arOption['type'] == 'json') {
				$option[$option_name . '_VALUE'] = @json_decode($option[$option_name], true);
			}
		}
		if ($option['MODULE_MODE'] == 'test') {
			$img2picture = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('img2picture');
			if ($img2picture) {
				$_SESSION['img2picture'] = $img2picture;
			}
			$option['MODULE_MODE'] = $_SESSION['img2picture'];
			$option['DEBUG'] = 'Y';
		}
		if ($option['MODULE_MODE'] == 'on') {
			$img2pictureDebug = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('img2picture');
			$img2pictureClearCache = \Bitrix\Main\Application::getInstance()->getContext()->getRequest()->get('img2picture');
			if ($img2pictureDebug == 'Y') {
				$option['DEBUG'] = 'Y';
			}
			if ($img2pictureClearCache != '') {
				$option['CLEAR_CACHE'] = $img2pictureClearCache;
			}
		}

		$option['MODULE_CONFIG'] = $arModuleCfg;
		$option['DOCUMENT_ROOT'] = \Bitrix\Main\Application::getDocumentRoot();
		return $option;
	}

	public static function ClearDirCache() {
		$option = self::GetOptions();
		$img2picture = new CImageManupulator($option);
		return $img2picture->ClearDirCache();
	}

}
