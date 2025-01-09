<?

namespace IS_PRO\img2picture;

use IS_PRO\img2picture\CSimpleImage;

if (class_exists('\IS_PRO\img2picture\CImageManupulator')) {
	return;
}


class CImageManupulator extends CSimpleImage
{
	const
		maxWorkTime = 20,
		DIR = '/upload/img2picture/',
		max_width = 99999,
		cachePath  = 'img2picture',
		smallWidth = 30,
		onePXpng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
		onePXwebp = 'data:image/webp;base64,UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA',
		onePXavif = 'data:image/avif;base64,AAAAFGZ0eXBhdmlmAAAAAG1pZjEAAACgbWV0YQAAAAAAAAAOcGl0bQAAAAAAAQAAAB5pbG9jAAAAAEQAAAEAAQAAAAEAAAC8AAAAGwAAACNpaW5mAAAAAAABAAAAFWluZmUCAAAAAAEAAGF2MDEAAAAARWlwcnAAAAAoaXBjbwAAABRpc3BlAAAAAAAAAAQAAAAEAAAADGF2MUOBAAAAAAAAFWlwbWEAAAAAAAAAAQABAgECAAAAI21kYXQSAAoIP8R8hAQ0BUAyDWeeUy0JG+QAACANEkA=';

	private $arParams = array();
	private $startTime = 0;
	private $cache;

	public function __construct($arParams)
	{

		$this->startTime = microtime(true);;

		/* DOCUMENT_ROOT */
		if ((!isset($arParams['DOCUMENT_ROOT'])) || (empty($arParams['DOCUMENT_ROOT']))) {
			$arParams['DOCUMENT_ROOT'] = self::DOC_ROOT();
		}

		/* ATTR_SRC_VALUES */
		$arParams['ATTR_SRC_VALUES'] = [];
		if ((isset($arParams['ATTR_SRC'])) && (!empty($arParams['ATTR_SRC']))) {
			$arAttrs = [];
			$arAttrs = explode("\n", $arParams['ATTR_SRC']);
			if (is_array($arAttrs)) {
				foreach ($arAttrs as $k => $v) {
					$v = trim($v);
					if ($v == '') {
						continue;
					}
					$arParams['ATTR_SRC_VALUES'][] = $v;
				}
			}
		}
		if (!isset($arParams['ATTR_SRC_VALUES']) || (count($arParams['ATTR_SRC_VALUES']) == 0)) {
			$arParams['ATTR_SRC_VALUES'][] = 'src';
		}

		/* EXCEPTIONS_SRC_REG */
		if ((isset($arParams['EXCEPTIONS_SRC'])) && (!empty($arParams['EXCEPTIONS_SRC']))) {
			$arParams['EXCEPTIONS_SRC_REG'] = [];
			$arExceptions = [];
			$arExceptions = explode("\n", $arParams['EXCEPTIONS_SRC']);
			if (is_array($arExceptions)) {
				foreach ($arExceptions as $k => $v) {
					$v = trim($v);
					if ($v == '') {
						continue;
					}
					$arParams['EXCEPTIONS_SRC_REG'][] = '|' . $v . '|';
				}
			}
		} else {
			$arParams['EXCEPTIONS_SRC'] = '';
			$arParams['EXCEPTIONS_SRC_REG'] = [];
		}

		/* EXCEPTIONS_TAG_REG */
		if (isset($arParams['EXCEPTIONS_TAG']) && !empty($arParams['EXCEPTIONS_TAG'])) {
			$arParams['EXCEPTIONS_TAG_REG'] = [];
			$arExceptions = [];
			$arExceptions = explode("\n", $arParams['EXCEPTIONS_TAG']);
			if (is_array($arExceptions)) {
				foreach ($arExceptions as $k => $v) {
					$v = trim($v);
					if ($v == '') {
						continue;
					}
					$arParams['EXCEPTIONS_TAG_REG'][] = '|' . $v . '|';
				}
			}
		} else {
			$arParams['EXCEPTIONS_TAG'] = '';
			$arParams['EXCEPTIONS_TAG_REG'] = [];
		}

		/* TAGS_ATTR_VALUES */
		$arParams['TAGS_ATTR_VALUES'] = [];
		if (isset($arParams['TAGS_ATTR']) && !empty($arParams['TAGS_ATTR'])) {
			$arAttrs = [];
			$arAttrs = explode("\n", $arParams['TAGS_ATTR']);
			if (is_array($arAttrs)) {
				foreach ($arAttrs as $k => $v) {
					$v = trim($v);
					if ($v == '') {
						continue;
					}
					if (mb_strPos($v, ':') === false) {
						continue;
					}
					$arParams['TAGS_ATTR_VALUES'][] = $v;
				}
			}
		}

		/* IMG_COMPRESSION */
		if (!isset($arParams['IMG_COMPRESSION']) || ((int) $arParams['IMG_COMPRESSION'] == 0)) {
			$arParams['IMG_COMPRESSION'] = 75;
		}

		/* WIDTH */
		if (isset($arParams['RESPONSIVE_VALUE']) && (is_array($arParams['RESPONSIVE_VALUE']))) {
			foreach ($arParams['RESPONSIVE_VALUE'] as $key => $val) {
				$arParams['WIDTH'][] = $val['width'];
			}
			$arParams['WIDTH'][] = self::smallWidth;
			rsort($arParams['WIDTH'], SORT_NUMERIC);
		} else {
			$arParams['RESPONSIVE_VALUE'] = [];
		}

		/* LAZYLOAD */
		$arParams['LAZYLOAD'] = (!empty($arParams['LAZYLOAD'])) ? $arParams['LAZYLOAD'] : 'N';

		/* CACHE_TTL */
		if ((int) $arParams['CACHE_TTL'] == 0) {
			$arParams['CACHE_TTL'] = 2592000; /* 30 дней */
		}

		if (isset($arParams['USE_IMAGICK']) && ($arParams['USE_IMAGICK'] == 'Y')) {
			$this->use_imagick = true;
		} else {
			$arParams['USE_IMAGICK'] = 'N';
		}

		if (!isset($arParams['CLEAR_CACHE'])) {
			$arParams['CLEAR_CACHE'] = '';
		}

		if (!isset($arParams['DEBUG'])) {
			$arParams['DEBUG'] = '';
		}

		$this->arParams = $arParams;
	}

	public static function __debug($arr)
	{
		/* функция дебага, даже если класс используется не в Битрикс */
		if (defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true) {
			\Bitrix\Main\Diag\Debug::writeToFile($arr);
		} else {
			file_put_contents($this->arParams['DOCUMENT_ROOT'] . '/img2picture.log', print_r($arr, true), FILE_APPEND);
		}
	}

	public static function DOC_ROOT(): string
	{
		/* функция получения DOCUMENT_ROOT, даже если класс используется не в Битрикс */
		if (defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true) {
			return \Bitrix\Main\Application::getDocumentRoot();
		} else {
			return $_SERVER['DOCUMENT_ROOT'];
		}
	}

	private function CacheInitCheck($cacheKey)
	{
		/* функция инициализации и проверки кеша, даже если класс используется не в Битрикс */
		$arParams = $this->arParams;
		if (defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true) {
			$this->cache = \Bitrix\Main\Data\Cache::createInstance();
			$cachePath = self::cachePath;
			$cacheTtl = (int) $arParams['CACHE_TTL'];
			if ($this->cache->initCache($cacheTtl, $cacheKey, $cachePath)) {
				return $this->cache->getVars();
			}
		} else {
			$curDate = time();
			$fileCache = $this->arParams['DOCUMENT_ROOT'] . self::DIR . 'cache/' . $cacheKey;
			$this->cache = $fileCache;
			if (file_exists($fileCache)) {
				$lastUpdate = filemtime($fileCache);
				if (!empty($arParams['CACHE_TTL']) && ($curDate - $lastUpdate) < (int) $arParams['CACHE_TTL']) {
					$var = file_get_contents($fileCache);
					$result = unserialize($var);
					return $result;
				}
			}
		}
		return false;
	}

	private function CacheAbort()
	{
		if (defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true) {
			if (!empty($this->cache)) {
				$this->cache->abortDataCache();
			}
		}
	}

	private function CacheSave($cached)
	{
		$arParams = $this->arParams;
		if (defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true) {
			if (!empty($this->cache) && is_object($this->cache)) {
				if ($this->cache->startDataCache()) {
					$this->cache->endDataCache($cached);
					return true;
				}

			}
		} else {
			if (!empty($this->cache)) {
				$data = serialize($cached);
				$this->CreateDir($this->cache, true);
				file_put_contents($this->cache, $data);
				if ($arParams['DEBUG'] == 'Y') {
					self::__debug(['CacheSave OK'  => $this->cache]);
				}
				return true;
			}
		}
		if ($arParams['DEBUG'] == 'Y') {
			self::__debug(['CacheSave FALSE'  => __LINE__]);
		}
		return false;
	}


	private function canContinue()
	{
		$time_end = microtime(true);
		$worktime = $time_end - $this->startTime;

		if (($this->arParams['DEBUG'] == 'Y') && ($worktime >= self::maxWorkTime)) {
			self::__debug(['Stop by timeout' => $worktime]);
		}
		return $worktime < self::maxWorkTime;
	}

	public function doIt(&$content)
	{
		$arParams = $this->arParams;
		if ($arParams['DEBUG'] == 'Y') {
			self::__debug(['ReplaceImg_' . date('Y.M.d H:i:s') => 'start']);
		}

		$this->ReplaceImg($content);

		if ($arParams['BACKGROUNDS'] == 'Y' && $this->canContinue()) {

			if ($arParams['DEBUG'] == 'Y') {
				self::__debug(['ReplaceBackground_' . date('Y.M.d H:i:s') => 'start']);
			}

			$this->ReplaceBackground($content);
		}

		$this->ReplaceTagsAttr($content);
	}

	public function ReplaceBackground(&$content)
	{
		$arParams = $this->arParams;
		$preg = '/<[^>]+style[^>]*=[^>]*(background(-image)*\s*:\s*url\((.*)\))[^>]*\>/ismuU';
		$tagkey = 0;
		$srckey = 3;

		if (!preg_match_all($preg, $content, $matches)) {
			return;
		}

		if ($arParams['DEBUG'] == 'Y') {
			self::__debug(['FOUND background array' => $matches]);
		}

		$arAllreadyReplaced = [];

		foreach ($matches[$tagkey] as $key => $tag) {
			if ($arParams['DEBUG'] == 'Y') {
				self::__debug(['FOUND background el' => $tag]);
			}

			$need = true;
			$img['tag'] = $matches[$tagkey][$key];
			$img['src'] = trim($matches[$srckey][$key], '"' . "'");

			if ($arParams['DEBUG'] == 'Y') {
				self::__debug(['FOUND background img' => $img]);
			}
			if (in_array($img['tag'], $arAllreadyReplaced)) {
				$need = false;
				if ($arParams['DEBUG'] == 'Y') {
					self::__debug(['TAG ALLREADY REPLACED']);
				}
			}

			$cacheKey    = $this->GenerateCacheKey($img);
			$cachedPlace = $this->CacheInitCheck($cacheKey);
			if (($cachedPlace !== false) && empty($arParams['CLEAR_CACHE'])) {
				if ($arParams['DEBUG'] == 'Y') {
					self::__debug(['GET_FROM_CACHE' => $cachedPlace]);
				}
				if (is_array($cachedPlace)) {
					$cachedPlace = $cachedPlace['place'];
				}
			} else {
				$arResult = [];
				$arResult['place'] = '';
				if (mb_strpos($img['tag'], 'data-i2p')) {
					$need = false;
					if ($arParams['DEBUG'] == 'Y') {
						self::__debug(['TAG IS HAVE data-i2p']);
					}
				}

				if ($need) {
					$need = $this->ExceptionBySrc($img['src']);
				}

				if ($need) {
					$arResult = $this->PrepareResultBackground($img, $arParams);
				}

				if ($arResult === false || empty($arResult)) {
					$this->CacheAbort();
					continue;
				}

				if ($arParams['MODULE_CONFIG']['MODULE_ID'] != '') {
					if (defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true) {
						if ($arParams['COMPATIBLE_MODE'] == 'Y') {
							foreach (GetModuleEvents($arParams['MODULE_CONFIG']['MODULE_ID'], 'OnPrepareResultBackground', true) as $arEvent) {
								ExecuteModuleEventEx($arEvent, array(&$arResult));
							}
						} else {
							$event = new \Bitrix\Main\Event($arParams['MODULE_CONFIG']['MODULE_ID'], "OnPrepareResultBackground", [&$arResult]);
							$event->send();
						}
					}
				}
				$cachedPlace = $arResult['place'];
				$this->CacheSave($cachedPlace);
			}
			$arResult['place'] = $cachedPlace;
			if ((trim($arResult['place']) != '') && (mb_strpos($arResult['place'], '</style>'))) {
				[$tohead, $newtag] = explode('</style>', $arResult['place']);
				$tohead .= '</style></head>';
				$arAllreadyReplaced[] = $img['tag'];
				$content = str_replace(
					['</head>', $img['tag']],
					[$tohead, $newtag],
					$content
				);
				if ($arParams['DEBUG'] == 'Y') {
					self::__debug([
						'REPLACED_FROM' => $img['tag'],
						'REPLACED_TO' => $arResult['place']
					]);
				}
			}
			if (!$this->canContinue()) {
				break;
			}
		};
	}

	public function ReplaceImg(&$content)
	{
		$arParams = $this->arParams;
		$arPicture = $this->get_tags('picture', $content, true);
		$arImg = $this->get_tags('img', $content, false);

		$arAllreadyReplaced = [];

		foreach ($arImg as $img) {

			$need = true;
			if ($arParams['DEBUG'] == 'Y') {
				self::__debug(['FOUND_IMG' => $img]);
			}

			$found_src = false;
			$attr_src = '';
			foreach ($arParams['ATTR_SRC_VALUES'] as $attr_src) {
				if (trim($img[$attr_src]) !== '') {
					$found_src = true;
					break;
				}
			}
			if (!$found_src) {
				if ($arParams['DEBUG'] == 'Y') {
					self::__debug(['IMG SRC IS EMPTY']);
				}
				continue;
			}


			$cacheKey    = $this->GenerateCacheKey($img, $attr_src);
			$cachedPlace = $this->CacheInitCheck($cacheKey);
			if (($cachedPlace !== false) && (empty($arParams['CLEAR_CACHE']))) {
				if ($arParams['DEBUG'] == 'Y') {
					self::__debug(['GET_FROM_CACHE' => $cachedPlace]);
				}
			} else {
				/* проверим на исключения */
				$need = $this->ExceptionBySrc($img[$attr_src]);
				if ($need) {
					$need = $this->ExceptionByTag($img['tag']);
				}

				if ($need) {
					/* Проверим есть ли наше изображение уже в picture */
					if (is_array($arPicture)) {
						foreach ($arPicture as $picture) {
							if (mb_strpos($picture['tag'], $img['tag'])) {
								$need = false;
								if ($arParams['DEBUG'] == 'Y') {
									self::__debug(['EXCEPTIONS BY ALLREADY IN PICTURE' => $picture['tag']]);
								}
								break;
							}
						}
					}
				}

				$arResult = false;

				if ($need) {
					$arResult = $this->PrepareResultImg($img, $attr_src, $arParams);
				}

				if ($arResult === false || empty($arResult)) {
					$this->CacheAbort();
					continue;
				}

				if (isset($arParams['MODULE_CONFIG']['MODULE_ID']) && $arParams['MODULE_CONFIG']['MODULE_ID'] != '') {
					if (defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true) {
						if ($arParams['COMPATIBLE_MODE'] == 'Y') {
							foreach (GetModuleEvents($arParams['MODULE_CONFIG']['MODULE_ID'], 'OnPrepareResultImg', true) as $arEvent) {
								ExecuteModuleEventEx($arEvent, array(&$arResult));
							}
						} else {
							$event = new \Bitrix\Main\Event($arParams['MODULE_CONFIG']['MODULE_ID'], "OnPrepareResultImg", [&$arResult]);
							$event->send();
						}
					}
				}
				$cachedPlace = $arResult['place'];
				$this->CacheSave($cachedPlace);
			}

			$arResult['place'] = $cachedPlace;
			if (!empty($arResult['place'])) {
				$arAllreadyReplaced[] = $img['tag'];
				$content = str_replace($img['tag'], $arResult['place'], $content);
				if ($arParams['DEBUG'] == 'Y') {
					self::__debug([
						'REPLACED_FROM' => $img['tag'],
						'REPLACED_TO'   => $arResult['place']
					]);
				}
			}

			if (!$this->canContinue()) {
				break;
			}
		}
	}

	public function ReplaceTagsAttr(&$content)
	{
		$arParams = $this->arParams;
		if ($arParams['DEBUG'] == 'Y') {
			self::__debug(['ReplaceTagsAttrParams' => $arParams['TAGS_ATTR_VALUES']]);
		}
		if (!empty($arParams['TAGS_ATTR_VALUES']) && count($arParams['TAGS_ATTR_VALUES']) > 0) {
			foreach ($arParams['TAGS_ATTR_VALUES'] as $tagsAttr) {
				[$tag, $attr] = explode(':', $tagsAttr);
				if ($arParams['DEBUG'] == 'Y') {
					self::__debug(['tag' => $tag, 'attr' => $attr]);
				}
				if (!empty($tag) && !empty($attr)) {
					$this->ReplaceTag($content, $tag, $attr);
				}
			}
		}
	}
	public function ReplaceTag(&$content, $tagSearch, $attrImg)
	{
		$arParams = $this->arParams;
		$arImg = $this->get_tags($tagSearch, $content, false);

		$arAllreadyReplaced = [];

		foreach ($arImg as $img) {

			$need = true;
			if ($arParams['DEBUG'] == 'Y') {
				self::__debug(['FOUND_TAG' => $img]);
			}

			$attr_src = $attrImg;
			$found_src = self::isImg($img[$attr_src]);

			if (!$found_src) {
				if ($arParams['DEBUG'] == 'Y') {
					self::__debug(['TAG ATTR IS NOT IMAGE' => $img[$attr_src]]);
				}
				continue;
			}

			if (in_array($img['tag'], $arAllreadyReplaced)) {
				if ($arParams['DEBUG'] == 'Y') {
					self::__debug(['TAG ALLREADY REPLACED']);
				}
				continue;
			}

			$cacheKey    = $this->GenerateCacheKey($img, $attr_src);
			$cachedPlace = $this->CacheInitCheck($cacheKey);
			if (($cachedPlace !== false) && (empty($arParams['CLEAR_CACHE']))) {
				if ($arParams['DEBUG'] == 'Y') {
					self::__debug(['GET_FROM_CACHE' => $cachedPlace]);
				}
			} else {
				/* проверим на исключения */
				$need = $this->ExceptionBySrc($img[$attr_src]);
				if ($need) {
					$need = $this->ExceptionByTag($img['tag']);
				}

				$arResult = false;

				if ($need) {
					$arImgPrepared =  $this->PrepareOriginal($img[$attr_src]);
					if ($arParams['DEBUG'] == 'Y') {
						self::__debug(['Try generate WEBP/AVIF' => $arImgPrepared]);
					}
				}

				if ($arImgPrepared === false) {
					if ($arParams['DEBUG'] == 'Y') {
						self::__debug(['CANt prepare img to  WEBP/AVIF']);
					}
					$this->CacheAbort();
					continue;
				}


				$newSrc = false;
				if (!empty($arImgPrepared['avif'])) {
					$newSrc = $arImgPrepared['avif'];
				} else if (!empty($arImgPrepared['webp'])) {
					$newSrc = $arImgPrepared['webp'];
				}

				if ($newSrc === false) {
					if ($arParams['DEBUG'] == 'Y') {
						self::__debug(['CANt found WEBP/AVIF']);
					}
					$this->CacheAbort();
					continue;
				}

				$arResult = [
					'tag' => $img['tag'],
					'img' => $img,
					'FILES' => $arImgPrepared,
					'place' => str_replace($img[$attr_src], $newSrc, $img['tag'])
				];


				if ($arParams['MODULE_CONFIG']['MODULE_ID'] != '') {
					if (defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true) {
						if ($arParams['COMPATIBLE_MODE'] == 'Y') {
							foreach (GetModuleEvents($arParams['MODULE_CONFIG']['MODULE_ID'], 'OnPrepareResultTagsAttr', true) as $arEvent) {
								ExecuteModuleEventEx($arEvent, array(&$arResult));
							}
						} else {
							$event = new \Bitrix\Main\Event($arParams['MODULE_CONFIG']['MODULE_ID'], "OnPrepareResultTagsAttr", [&$arResult]);
							$event->send();
						}
					}
				}
				$cachedPlace = $arResult['place'];
				$this->CacheSave($cachedPlace);
			}
			$arResult['place'] = $cachedPlace;
			if (!empty($arResult['place'])) {
				$arAllreadyReplaced[] = $img['tag'];
				$content = str_replace($img['tag'], $arResult['place'], $content);

				if ($arParams['DEBUG'] == 'Y') {
					self::__debug([
						'REPLACED_FROM' => $img['tag'],
						'REPLACED_TO'   => $arResult['place']
					]);
				}
			}

			if (!$this->canContinue()) {
				break;
			}
		}
	}

	function ExceptionBySrc($src)
	{
		$result = true;
		$arParams = $this->arParams;
		if (is_array($arParams['EXCEPTIONS_SRC_REG'])) {
			foreach ($arParams['EXCEPTIONS_SRC_REG'] as $exception) {
				if (preg_match($exception, $src)) {
					$result = false;
					if ($arParams['DEBUG'] == 'Y') {
						self::__debug(['EXCEPTIONS_SRC_REG' => $exception]);
					}
					break;
				}
			}
		}
		return $result;
	}

	function ExceptionByTag($tag)
	{
		$result = true;
		$arParams = $this->arParams;
		if (is_array($arParams['EXCEPTIONS_TAG_REG'])) {
			foreach ($arParams['EXCEPTIONS_TAG_REG'] as $exception) {
				if (preg_match($exception, $tag)) {
					$result = false;
					if ($arParams['DEBUG'] == 'Y') {
						self::__debug(['EXCEPTIONS_TAG_REG' => $exception]);
					}
					break;
				}
			}
		}
		return $result;
	}

	function GenerateCacheKey($img, $attr_src = 'src')
	{
		$doc_root = $this->arParams['DOCUMENT_ROOT'];
		if (file_exists($doc_root . $img[$attr_src])) {
			$img['LAST_MODIFIED'] = filemtime($doc_root . $img[$attr_src]);
			$img['SIZE']          = filesize($doc_root . $img[$attr_src]);
		}
		if ($this->arParams['DEBUG'] == 'Y') {
			self::__debug(['GenerateCacheKey by' => $img]);
		}
		return md5(print_r($img, true));
	}

	public static function isImg($src): bool
	{
		if (mb_strpos($src, '.') === false) {
			return false;
		}
		$src = explode('?', $src)[0];
		$ext = mb_strtolower(substr(strrchr($src, '.'), 1));
		if (in_array($ext, ['jpg', 'jpeg', 'png', 'bmp', 'gif'])) {
			return true;
		}
		return false;
	}

	function PrepareOriginal($src)
	{
		$doc_root = $this->arParams['DOCUMENT_ROOT'];
		$arParams = $this->arParams;
		$arResult['src'] = $src;
		$ext = mb_strtolower(substr(strrchr($src, '.'), 1));
		if ($ext == 'jpg') {
			$ext = 'jpeg';
		}
		$arResult['type'] = 'image/' . $ext;

		/* проверим существует ли файл вообще */
		if (!file_exists($doc_root . $src)) {
			$src = urldecode($src);
		}

		if (!file_exists($doc_root . $src)) {
			return false;
		}

		if (!$this->load($doc_root . $src)) {
			return false;
		}

		$arResult['width'] = $this->getWidth();
		$arResult['height'] = $this->getHeight();

		if ($arParams['USE_WEBP'] == 'Y') {
			$webpSrc = $this->ConvertImg2webp($src);
			if ($webpSrc) {
				$arResult['webp'] = $webpSrc;
			}
		}
		if ($arParams['USE_AVIF'] == 'Y') {
			$avifSrc = $this->ConvertImg2avif($src);
			if ($avifSrc) {
				$arResult['avif'] = $avifSrc;
			}
		}
		if ($arParams['USE_ONLY_WEBP_AVIF'] == 'Y') {
			if (!empty($arResult['webp'])) {
				$arResult['src'] = $arResult['webp'];
				$arResult['type'] = 'image/webp';
			} else if (!empty($arResult['avif'])) {
				$arResult['src'] = $arResult['avif'];
				$arResult['type'] = 'image/avif';
			}
		}
		return $arResult;
	}

	function PrepareResponsive(string $src, array $arWidth)
	{
		$doc_root = $this->arParams['DOCUMENT_ROOT'];

		$loaded = false;
		$height = self::max_width;

		/* проверим существует ли файл вообще */
		$fullPathFile = str_replace('//', '/', $doc_root . $src);
		if (!file_exists($fullPathFile)) {
			$src = urldecode($src);
		}
		$fullPathFile = str_replace('//', '/', $doc_root . $src);
		if (!file_exists($fullPathFile)) {
			if ($this->arParams['DEBUG'] == 'Y') {
				self::__debug(['FILE not found' => $fullPathFile]);
			}
			return false;
		}

		$arResult = [];

		/* подготовим файлы для каждой ширины */
		foreach ($arWidth as $width) {
			$resized = false;
			$newsrc = self::DIR . '/' . $width . '/' .  str_replace(['%2F', '+', '%'], ['/', '_', '_'], urlencode($src));
			$newsrc = str_replace('//', '/', $newsrc);
			$filename = $doc_root . $newsrc;

			$arResult[$width]['src'] = $newsrc;

			if (
				(!file_exists($filename)) ||
				(in_array(
					$this->arParams['CLEAR_CACHE'],
					[
						'Y',
						$fullPathFile,
						$src,
						$filename,
						$newsrc
					]
				))
			) {
				if (!$loaded) {
					if (!$this->load($doc_root . $src)) {
						return false;
					}
					$loaded = true;
				}
				if ($loaded) {
					$resized = $this->smallTo($width, $height);
					if ($resized) {
						$this->CreateDir($filename, true);
						if (!$this->save($filename, $this->image_type, $this->arParams['IMG_COMPRESSION'])) {
							unset($arResult[$width]['src']);
						}
					} else {
						unset($arResult[$width]['src']);
					}
				} else {
					unset($arResult[$width]['src']);
				}
			} else {
				if (in_array(filesize($filename), [0, 4096])) {
					unset($arResult[$width]['src']);
				}
			}

			if ($this->arParams['USE_WEBP'] == 'Y') {

				/* подготовим webp */
				$filename = $doc_root . $newsrc . '.webp';
				$arResult[$width]['webp'] = $newsrc . '.webp';
				if ((!file_exists($filename)) ||
					(in_array(
						$this->arParams['CLEAR_CACHE'],
						[
							'Y',
							$doc_root . $src,
							$src,
							$filename,
							$newsrc
						]
					))
				) {
					if (!$loaded) {
						if (!$this->load($doc_root . $src)) {
							return false;
						}
						$loaded = true;
					}
					if ($loaded) {
						if (!$resized) {
							$resized = $this->smallTo($width, $height);
						}
						if ($resized) {
							$this->CreateDir($filename, true);
							if (!$this->save($filename, IMAGETYPE_WEBP, $this->arParams['IMG_COMPRESSION'])) {
								unset($arResult[$width]['webp']);
							};
							if (in_array(filesize($filename), [0, 4096])) {
								unset($arResult[$width]['webp']);
							}
						} else {
							unset($arResult[$width]['webp']);
						}
					} else {
						unset($arResult[$width]['webp']);
					}
				} else {
					if (in_array(filesize($filename), [0, 4096])) {
						unset($arResult[$width]['webp']);
					}
				}
			}

			if ($this->arParams['USE_AVIF'] == 'Y') {
				/* подготовим avif */
				$filename = $doc_root . $newsrc . '.avif';
				$arResult[$width]['avif'] = $newsrc . '.avif';
				if (
					(!file_exists($filename)) ||
					(in_array(
						$this->arParams['CLEAR_CACHE'],
						[
							'Y',
							$doc_root . $src,
							$src,
							$filename,
							$newsrc
						]
					)
					)
				) {
					if (!$loaded) {
						if (!$this->load($doc_root . $src)) {
							return false;
						}
						$loaded = true;
					}
					if ($loaded) {
						if (!$resized) {
							$resized = $this->smallTo($width, $height);
						}
						if ($resized) {
							$this->CreateDir($filename, true);
							if (!$this->save($filename, IMAGETYPE_AVIF, $this->arParams['IMG_COMPRESSION'])) {
								unset($arResult[$width]['avif']);
							};
							if (in_array(filesize($filename), [0, 4096])) {
								unset($arResult[$width]['avif']);
							}
						} else {
							unset($arResult[$width]['avif']);
						}
					} else {
						unset($arResult[$width]['avif']);
					}
				} else {
					if (in_array(filesize($filename), [0, 4096])) {
						unset($arResult[$width]['avif']);
					}
				}
			}
			if ($this->arParams['USE_ONLY_WEBP_AVIF'] == 'Y') {
				if (!empty($arResult[$width]['webp'])) {
					$arResult[$width]['src'] = $arResult[$width]['webp'];
				} else if (!empty($arResult[$width]['avif'])) {
					$arResult[$width]['src'] = $arResult[$width]['avif'];
				}
			}
		}
		if (isset($arResult[self::smallWidth]) && is_array($arResult[self::smallWidth])) {
			foreach ($arResult[self::smallWidth] as $type_origin => $file) {
				$filename = str_replace('//', '/', $this->arParams['DOCUMENT_ROOT'] . '/' . $file);
				$type = pathinfo($filename, PATHINFO_EXTENSION);
				$data = file_get_contents($filename);
				$arResult[self::smallWidth][$type_origin] = 'data:image/' . $type . ';base64,' . base64_encode($data);
				$arResult[self::smallWidth][$type_origin . '_file'] = $file;
			}
		}
		return $arResult;
	}

	public function PrepareResultImg($img, $attr_src, $arParams)
	{
		$arResult['img'] = $img;
		$arResult['sources'] = [];

		$files = $this->PrepareResponsive($img[$attr_src], $arParams['WIDTH']);

		if ($files === false) {
			return false;
		}

		$PreparedOriginal = $this->PrepareOriginal($img[$attr_src]);
		$arResult['FILES'] =  $files;

		if ($arParams['DEBUG'] == 'Y') {
			self::__debug(['CREATE_FILES' => $arResult['FILES']]);
		}

		foreach ($arParams['RESPONSIVE_VALUE'] as $key => $val) {
			if (!is_array($arResult['FILES'][$val['width']])) {
				continue;
			}
			if (count($arResult['FILES'][$val['width']]) == 0) {
				continue;
			}
			$addsourse = ['', ''];
			$addsourseLazy = ['', ''];
			foreach ($arResult['FILES'][$val['width']] as $file_type => $file_src) {
				if ($file_type == 'avif') {
					$type = 'type="image/avif"';
					if (!empty($arResult['FILES'][self::smallWidth]['avif'])) {
						$lazy = 'srcset="' . $arResult['FILES'][self::smallWidth]['avif'] . '"';
					} else {
						$lazy = 'srcset="' . self::onePXavif . '"';
					}
					$index = 0;
				} else if ($file_type == 'webp') {
					$type = 'type="image/webp"';
					if (!empty($arResult['FILES'][self::smallWidth]['webp'])) {
						$lazy = 'srcset="' . $arResult['FILES'][self::smallWidth]['webp'] . '"';
					} else {
						$lazy = 'srcset="' . self::onePXwebp . '"';
					}
					$index = 1;
				} else if ($arParams['USE_ONLY_WEBP_AVIF'] != 'Y') {
					$ext = substr(strrchr($file_src, '.'), 1);
					if ($ext == 'jpg') {
						$ext = 'jpeg';
					}
					$type = 'type="image/' . $ext . '"';
					if (!empty($arResult['FILES'][self::smallWidth]['src'])) {
						$lazy = 'srcset="' . $arResult['FILES'][self::smallWidth]['src'] . '"';
					} else {
						$lazy = 'srcset="' . self::onePXpng . '"';
					}
					$index = 3;
				}
				$media = 'media="';
				$mediaand = '';
				if ((int) $val['min'] >= 0) {
					$media .= $mediaand . '(min-width: ' . $val['min'] . 'px)';
					$mediaand = ' and ';
				}

				if ((int) $val['max'] > (int) $val['min']) {
					$media .= $mediaand . '(max-width: ' . $val['max'] . 'px)';
				}

				$media .= '"';
				$addsourse[$index] = '<source srcset="' . $file_src . '" ' . $media . ' ' . $type . '>';
				$addsourseLazy[$index] = '<source ' . $lazy . ' data-i2p="Y" data-srcset="' . $file_src . '" ' . $media . ' ' . $type . '>';
			}
			ksort($addsourse);
			foreach ($addsourse as $oneaddsourse) {
				if ($oneaddsourse != '') {
					$arResult['sources'][] = $oneaddsourse;
				}
			}
			ksort($addsourseLazy);
			foreach ($addsourseLazy as $oneaddsourselazy) {
				if ($oneaddsourselazy != '') {
					$arResult['sources_lazy'][] = $oneaddsourselazy;
				}
			}
		}


		$arResult['FILES']['original'] = $PreparedOriginal;

		if (!empty($arResult['FILES']['original']['avif'])) {
			if (!empty($arResult['FILES'][self::smallWidth]['avif'])) {
				$lazy = 'srcset="' . $arResult['FILES'][self::smallWidth]['avif'] . '"';
			} else {
				$lazy = 'srcset="' . self::onePXavif . '"';
			}
			$arResult['sources'][] = '<source srcset="' . $arResult['FILES']['original']['avif'] . '"  type="image/avif">';
			$arResult['sources_lazy'][] = '<source ' . $lazy . '  data-i2p="Y" data-srcset="' . $arResult['FILES']['original']['avif'] . '"  type="image/avif">';
		}

		if (!empty($arResult['FILES']['original']['webp'])) {
			if (!empty($arResult['FILES'][self::smallWidth]['webp'])) {
				$lazy = 'srcset="' . $arResult['FILES'][self::smallWidth]['webp'] . '"';
			} else {
				$lazy = 'srcset="' . self::onePXwebp . '"';
			}
			$arResult['sources'][] = '<source srcset="' . $arResult['FILES']['original']['webp'] . '"  type="image/webp">';
			$arResult['sources_lazy'][] = '<source ' . $lazy . '  data-i2p="Y" data-srcset="' . $arResult['FILES']['original']['webp'] . '"  type="image/webp">';
		}

		if ($arParams['USE_ONLY_WEBP_AVIF'] == 'Y') {
			$arResult["img"]["tag"] = str_replace('"' . $arResult["img"]["src"] . '"', '"' . $arResult['FILES']['original']['src'] . '"', $arResult["img"]["tag"]);
			$arResult["img"]["src"] = $arResult['FILES']['original']['src'];
		}

		if ($arParams['DEBUG'] == 'Y') {
			self::__debug(['CREATED arResult' => $arResult]);
		}

		$arResult["img_lazy"]["tag"] = '<img ';
		foreach ($arResult["img"] as $attr_name => $attr_val) {
			if ($attr_name != 'tag') {
				if ($attr_name == 'src') {
					$arResult["img_lazy"]["tag"] .= ' data-i2p="Y" data-srcset="' . $attr_val . '"';
					if (!empty($arResult['FILES'][self::smallWidth]['src_file'])) {
						$arResult["img_lazy"]["tag"] .= ' srcset="' . $arResult['FILES'][self::smallWidth]['src_file'] . '"';
					} else {
						$arResult["img_lazy"]["tag"] .= ' srcset=""';
					}
				}
				if 	(
						in_array($attr_name, ['width', 'height'])
						|| (
							$attr_name == 'style'
							&& (
								mb_strpos($attr_val, 'width') !== false
								|| mb_strpos($attr_val, 'height') !== false
							)
						)
					) {
					unset($arResult['FILES']['original']['width']);
					unset($arResult['FILES']['original']['height']);
				}
				$arResult["img_lazy"]["tag"] .= ' ' . $attr_name . '="' . $attr_val . '"';
			}
		}
		if (($arParams['ADD_WIDTH'] == "Y") && !empty($arResult['FILES']['original']['width']) && !empty($arResult['FILES']['original']['height'])) {
			$arResult["img_lazy"]["tag"] .= ' width="' . $arResult['FILES']['original']['width'] . '" ';
			$arResult["img_lazy"]["tag"] .= ' height="' . $arResult['FILES']['original']['height'] . '" ';
		}

		$arResult["img_lazy"]["tag"] .= '>';

		if ($arParams['LAZYLOAD'] != "Y") {
			if ((isset($arResult["sources"])) && (count($arResult["sources"]) > 0)) {
				$arResult['place'] = '<picture>';
				foreach ($arResult["sources"] as $source) {
					$arResult['place'] .= $source;
				}
				$arResult['place'] .= $arResult["img"]["tag"];
				$arResult['place'] .= '</picture>';
			} else {
				$arResult['place'] = $arResult["img"]["tag"];
			}
		} else {
			if ((isset($arResult["sources_lazy"])) && (count($arResult["sources_lazy"]) > 0)) {
				$arResult['place'] = '<picture  data-i2p="Y">';
				foreach ($arResult["sources_lazy"] as $source) {
					$arResult['place'] .= $source;
				}
				$arResult['place'] .= $arResult["img_lazy"]["tag"];
				$arResult['place'] .= '</picture>';
			} else {
				$arResult['place'] = $arResult["img_lazy"]["tag"];
			}
		}
		return $arResult;
	}


	public function PrepareResultBackground($img, $arParams)
	{
		$img['parse_tag'] = $this->get_tags('', $img['tag'], false);
		$img['parse_tag'] = $img['parse_tag'][0];

		$arResult['img'] = $img;
		$arResult['md5key'] = md5($img['tag']);
		$files = $this->PrepareResponsive($img['src'], $arParams['WIDTH']);

		if (empty($files)) {
			if ($arParams['DEBUG'] == 'Y') {
				self::__debug(['FILES not ready' => $files]);
			}
			return false;
		}
		$PreparedOriginal = $this->PrepareOriginal($img['src']);
		$arResult['FILES'] =  $files;
		if ($arResult['FILES'][self::smallWidth]['src'] == '') {
			$arResult['FILES'][self::smallWidth]['src'] = $arResult['img']['src'];
		}
		$arResult['FILES']['original'] = $PreparedOriginal;
		if ($arParams['DEBUG'] == 'Y') {
			self::__debug(['TAG FILES' => $arResult['FILES']]);
		}
		$arResult['cssSelector'] = '[data-i2p="' . $arResult['md5key'] . '"]';
		$arResult['style'] = '<style>';

		$arResult['style'] .= '*' . $arResult['cssSelector'] . '{' . str_replace($arResult['img']['src'], $arResult['FILES'][self::smallWidth]['src'], $arResult['img']['parse_tag']['style']) . '}';

		foreach ($arParams['RESPONSIVE_VALUE'] as $key => $val) {
			if (!is_array($arResult['FILES'][$val['width']])) {
				continue;
			}
			if (count($arResult['FILES'][$val['width']]) == 0) {
				continue;
			}
			$haveFiles = false;
			$addsourse  = ['', ''];
			$addsourseLazy  = ['', ''];
			$minmax = 0;

			foreach ($arResult['FILES'][$val['width']] as $file_type => $file_src) {
				if ($file_type == 'avif') {
					$haveFiles = true;
					$addsourse[2] = '.avif' . $arResult['cssSelector'] . '{' . str_replace($arResult['img']['src'], $file_src, $arResult['img']['parse_tag']['style']) . '}';
					$addsourseLazy[2] = '.loaded' . $addsourse[2];
				} else if ($file_type == 'webp') {
					$haveFiles = true;
					$addsourse[1] = '.webp' . $arResult['cssSelector'] . '{' . str_replace($arResult['img']['src'], $file_src, $arResult['img']['parse_tag']['style']) . '}';
					$addsourseLazy[1] = '.loaded' . $addsourse[1];
				} else if ($arParams['USE_ONLY_WEBP_AVIF'] != 'Y') {
					$haveFiles = true;
					$addsourse[0] = '' . $arResult['cssSelector'] . '{' . str_replace($arResult['img']['src'], $file_src, $arResult['img']['parse_tag']['style']) . '}';
					$addsourseLazy[0] = '.loaded' . $addsourse[0];
				}
			}
			if ($haveFiles) {
				$arResult['style'] .= '@media ';
				$styleand = '';
				if ((int) $val['min'] >= 0) {
					$arResult['style'] .= '(min-width: ' . $val['min'] . 'px)';
					$styleand = ' and ';
					if ($minmax < $val['min']) {
						$minmax = $val['min'];
					}
				}
				if ((int) $val['max'] > (int) $val['min']) {
					$arResult['style'] .= $styleand . '(max-width: ' . $val['max'] . 'px)';
					if ($minmax < $val['max']) {
						$minmax = $val['max'];
					}
				}
				$arResult['style'] .= '{';
				if ($arParams['LAZYLOAD'] != "Y") {
					ksort($addsourse);
					foreach ($addsourse as $oneaddsourse) {
						$arResult['style'] .= $oneaddsourse;
					}
				} else {
					ksort($addsourseLazy);
					foreach ($addsourseLazy as $oneaddsourseLazy) {
						$arResult['style'] .= $oneaddsourseLazy;
					}
				}
				$arResult['style'] .= '}';
			}
		}
		$arResult['style'] .= '@media (min-width: ' . (int) $minmax . 'px) {';
		if ($arParams['LAZYLOAD'] != "Y") {
			$arResult['style'] .= '' . $arResult['cssSelector'] . '{' . str_replace($arResult['img']['src'], $arResult['FILES']['original']['src'], $arResult['img']['parse_tag']['style']) . '}';
			if (!empty($arResult['FILES']['original']['avif'])) {
				$arResult['style'] .= '.avif' . $arResult['cssSelector'] . '{' . str_replace($arResult['img']['src'], $arResult['FILES']['original']['avif'], $arResult['img']['parse_tag']['style']) . '}';
			}
			if (!empty($arResult['FILES']['original']['webp'])) {
				$arResult['style'] .= '.webp' . $arResult['cssSelector'] . '{' . str_replace($arResult['img']['src'], $arResult['FILES']['original']['webp'], $arResult['img']['parse_tag']['style']) . '}';
			}
		} else {
			$arResult['style'] .= '.loaded' . $arResult['cssSelector'] . '{' . str_replace($arResult['img']['src'], $arResult['FILES']['original']['src'], $arResult['img']['parse_tag']['style']) . '}';
			if (!empty($arResult['FILES']['original']['avif'])) {
				$arResult['style'] .= '.avif.loaded' . $arResult['cssSelector'] . '{' . str_replace($arResult['img']['src'], $arResult['FILES']['original']['avif'], $arResult['img']['parse_tag']['style']) . '}';
			}
			if (!empty($arResult['FILES']['original']['webp'])) {
				$arResult['style'] .= '.webp.loaded' . $arResult['cssSelector'] . '{' . str_replace($arResult['img']['src'], $arResult['FILES']['original']['webp'], $arResult['img']['parse_tag']['style']) . '}';
			}
		}
		$arResult['style'] .= '}';
		$arResult['style'] .= '</style>';
		$arResult['place'] = $arResult['style'] .
			str_replace(
				[
					$arResult['img']['parse_tag']['style'],
					' style',
					"\t" . 'style',
					"\n" . 'style',
				],
				[
					'',
					' data-i2p="' . $arResult['md5key'] . '"  style',
					"\t" . 'data-i2p="' . $arResult['md5key'] . '"  style',
					"\n" . 'data-i2p="' . $arResult['md5key'] . '"  style',
				],
				$img['tag']
			);
		if ($arParams['DEBUG'] == 'Y') {
			self::__debug(['CREATED arResult' => $arResult]);
		}
		return $arResult;
	}

	public function ConvertImg2webp(string $src)
	{
		if ($this->arParams['DEBUG'] == 'Y') {
			self::__debug(['TRY CONVERT TO WEBP' => $src]);
		}
		$need = false;
		$doc_root = $this->arParams['DOCUMENT_ROOT'];
		$webp = self::DIR . $src . '.webp';
		$webp = str_replace('//', '/', $webp);
		$filename = $doc_root . $webp;

		if ((!file_exists($filename)) ||
			(in_array(
				$this->arParams['CLEAR_CACHE'],
				[
					'Y',
					$doc_root . $src,
					$src,
					$filename,
					$webp
				]
			))
		) {
			$need = true;
		} else {
			$fileSize = filesize($filename);

			if (in_array($fileSize, [0, 4096])) {
				return false;
			}

			$srcModified = filemtime($doc_root . $src);
			$fileModified = filemtime($filename);
			if ($this->arParams['DEBUG'] == 'Y') {
				self::__debug(['Check files modification' => [$filename => $fileModified, $doc_root . $src => $srcModified]]);
			}
			if ($srcModified > $fileModified) {
				$need = true;
			}
		}

		if ($this->arParams['DEBUG'] == 'Y') {
			self::__debug(['NEED CONVERT TO WEBP' => $need]);
		}

		if ($need) {

			$this->CreateDir($filename, true);
			if (!$this->load($doc_root . $src)) {

				if ($this->arParams['DEBUG'] == 'Y') {
					self::__debug(['ERROR CONVERT TO WEBP' => 'NOT LOAD: ' . $doc_root . $src]);
				}

				return false;
			}
			if (!$this->save($filename, IMAGETYPE_WEBP, $this->arParams['IMG_COMPRESSION'])) {

				if ($this->arParams['DEBUG'] == 'Y') {
					self::__debug(['ERROR CONVERT TO WEBP' => 'NOT SAVE: ' . $filename]);
				}

				return false;
			}
			if (filesize($filename) == 0) {

				if ($this->arParams['DEBUG'] == 'Y') {
					self::__debug(['ERROR CONVERT TO WEBP' => 'SAVED FILE IS EMPTY: ' . $filename]);
				}

				return false;
			}
		}

		if ($this->arParams['DEBUG'] == 'Y') {
			self::__debug(['RESULT CONVERT TO WEBP' => $webp]);
		}

		return $webp;
	}

	public function ConvertImg2avif(string $src)
	{
		if ($this->arParams['DEBUG'] == 'Y') {
			self::__debug(['TRY CONVERT TO AVIF' => $src]);
		}
		$need = false;
		$doc_root = $this->arParams['DOCUMENT_ROOT'];
		$avif = self::DIR . $src . '.avif';
		$avif = str_replace('//', '/', $avif);
		$filename = $doc_root . $avif;

		if ((!file_exists($filename)) ||
			(in_array(
				$this->arParams['CLEAR_CACHE'],
				[
					'Y',
					$doc_root . $src,
					$src,
					$filename,
					$avif
				]
			))
		) {
			$need = true;
		} else {
			if (in_array(filesize($filename), [0, 4096])) {
				return false;
			}

			$srcModified = filemtime($doc_root . $src);
			$fileModified = filemtime($filename);
			if ($this->arParams['DEBUG'] == 'Y') {
				self::__debug(['Check files modification' => [$filename => $fileModified, $doc_root . $src => $srcModified]]);
			}

			if ($srcModified > $fileModified) {
				$need = true;
			}

		}

		if ($this->arParams['DEBUG'] == 'Y') {
			self::__debug(['NEED CONVERT TO AVIF' => $need]);
		}

		if ($need) {

			$this->CreateDir($filename, true);
			if (!$this->load($doc_root . $src)) {

				if ($this->arParams['DEBUG'] == 'Y') {
					self::__debug(['ERROR CONVERT TO AVIF' => 'NOT LOAD: ' . $doc_root . $src]);
				}

				return false;
			}
			if (!$this->save($filename, IMAGETYPE_AVIF, $this->arParams['IMG_COMPRESSION'])) {

				if ($this->arParams['DEBUG'] == 'Y') {
					self::__debug(['ERROR CONVERT TO AVIF' => 'NOT SAVE: ' . $filename]);
				}

				return false;
			}
			if (filesize($filename) == 0) {

				if ($this->arParams['DEBUG'] == 'Y') {
					self::__debug(['ERROR CONVERT TO AVIF' => 'SAVED FILE IS EMPTY: ' . $filename]);
				}

				return false;
			}
		}

		if ($this->arParams['DEBUG'] == 'Y') {
			self::__debug(['RESULT CONVERT TO AVIF' => $avif]);
		}

		return $avif;
	}

	public function ClearDirCache()
	{
		$doc_root = $this->arParams['DOCUMENT_ROOT'];
		self::RemoveDir($doc_root . self::DIR);
		self::RemoveDir($doc_root . '/bitrix/cache/' . self::cachePath);
		return true;
	}


	public function CreateDir($path,  $lastIsFile = false)
	{
		$dirs = explode('/', $path);
		if ($lastIsFile) {
			unset($dirs[count($dirs) - 1]);
		}
		$resultdir = '';
		foreach ($dirs as $dir) {
			$resultdir .= $dir;
			if ($dir != '') {
				@mkdir($resultdir);
			}
			$resultdir .= '/';
		}
		return $resultdir;
	}

	public function RemoveDir($path)
	{

		$files = glob($path . '/*');
		if ($files) {
			foreach ($files as $file) {
				is_dir($file) ? self::RemoveDir($file) : @unlink($file);
			}
		}
		@rmdir($path);

		return;
	}

	function get_tags($tag, $content, $haveClosedTag = true)
	{
		preg_match_all('/^([a-zA-Z]+)/', $tag, $seletorTag);
		preg_match_all('/#([a-zA-Z0-9-_]+)*/', $tag, $seletorIds);
		preg_match_all('/\.([a-zA-Z0-9-_]+)*/', $tag, $seletorClass);
		preg_match_all('/\[(.*)\]/', $tag, $seletorParams);
		if (!empty($seletorParams[1][0])) {
			$strParams = ' ' . str_replace(',', ' ', $seletorParams[1][0]);
			preg_match_all('/\s+([a-zA-Z-]+)\s*=\s*"([^"]*)"/ismuU', $strParams, $seletorParams);
		} else {
			$seletorParams = [];
		}
		if (!empty($seletorTag[1][0])) {
			$tag = $seletorTag[1][0];
		} else {
			$tag = '';
		}
		$arFilter = [];
		if (!empty($seletorIds[1])) {
			$arFilter['id'] = $seletorIds[1];
		}
		if (!empty($seletorClass[1])) {
			$arFilter['class'] = $seletorClass[1];
		}
		if (is_array($seletorParams[1])) {
			foreach ($seletorParams[1] as $key => $val) {
				$arFilter[$val][] = $seletorParams[2][$key];
			};
		}
		$notClosedTags = [
			'araa',
			'base',
			'br',
			'col',
			'command',
			'embed',
			'hr',
			'img',
			'input',
			'keygen',
			'link',
			'meta',
			'param',
			'source',
			'track',
			'wbr',
		];

		if (!in_array($tag, $notClosedTags) && $haveClosedTag) {
			$arTag['tag'] = '/(<' . $tag . '[^>]*>)(.*)<\/' . $tag . '>/ismuU';;
		} else {
			$arTag['tag'] = '/(<' . $tag . '[^>]*>)/ismuU';
		};

		$arTag['attr'][0] = '/\s+([a-zA-Z-]+)\s*=\s*"([^"]*)"/ismuU';
		$arTag['attr'][] = str_replace('"', "'", $arTag['attr'][0]);
		$result = [];
		if (preg_match_all($arTag['tag'], $content, $matches)) {
			foreach ($matches[0] as $k => $match) {
				$res_tag = [];
				$res_tag['tag'] = $match;
				if (isset($matches[1][$k])) {
					foreach ($arTag['attr'] as $arTagAttr) {
						unset($attr_matches);
						preg_match_all($arTagAttr, $matches[1][$k], $attr_matches);
						if (is_array($attr_matches[1])) {
							foreach ($attr_matches[1] as $key => $val) {
								$res_tag[$val] = $attr_matches[2][$key];
							}
						}
					}
				}
				if (isset($matches[2][$k])) {
					$res_tag['text'] = $matches[2][$k];
				}
				$ok = true;
				if (!empty($arFilter)) {
					foreach ($arFilter as $attrkey => $arValues) {
						if (!isset($res_tag[$attrkey])) {
							$ok = false;
							break;
						}
						if (!is_array($arValues)) {
							continue;
						}
						$arCurValues = explode(' ', $res_tag[$attrkey]);
						foreach ($arValues as $searchValue) {
							if (!in_array($searchValue, $arCurValues)) {
								$ok = false;
								break 2;
							}
						}
					}
				}
				if ($ok) {
					$result[] = $res_tag;
				}
			}
		}
		return $result;
	}
}
