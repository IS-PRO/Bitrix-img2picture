<?

namespace IS_PRO\img2picture;

use IS_PRO\img2picture\CSimpleImage;

if (class_exists('\IS_PRO\img2picture\CImageManupulator')) {
	return;
}


class CImageManupulator extends CSimpleImage
{
	const DIR = '/upload/img2picture/';
	const max_width = 99999;
	const cachePath  = 'img2picture';
	const smallWidth = 100;
	var $arParams = array();

	public function __construct($arParams)
	{
		if (empty($arParams['DOCUMENT_ROOT'])) {
			$arParams['DOCUMENT_ROOT'] = \Bitrix\Main\Application::getDocumentRoot();
		};
		if (!empty($arParams['EXCEPTIONS_SRC'])) {
			$arExceptions = [];
			$arExceptions = explode("\n", $arParams['EXCEPTIONS_SRC']);
			if (is_array($arExceptions)) {
				foreach ($arExceptions as $k => $v) {
					$arExceptions[$k] = '|' . trim($v) . '|';
				};
				$arParams['EXCEPTIONS_SRC_REG'] = $arExceptions;
			};
		};
		if (!empty($arParams['EXCEPTIONS_TAG'])) {
			$arExceptions = [];
			$arExceptions = explode("\n", $arParams['EXCEPTIONS_TAG']);
			if (is_array($arExceptions)) {
				foreach ($arExceptions as $k => $v) {
					$arExceptions[$k] = '|' . trim($v) . '|';
				};
				$arParams['EXCEPTIONS_TAG_REG'] = $arExceptions;
			};
		};

		if ((int) $arParams['IMG_COMPRESSION'] == 0) {
			$arParams['IMG_COMPRESSION'] = 75;
		};
		if (is_array($arParams['RESPONSIVE_VALUE'])) {
			foreach ($arParams['RESPONSIVE_VALUE'] as $key => $val) {
				$arParams['WIDTH'][] = $val['width'];
			};
			$arParams['WIDTH'][] = self::smallWidth;
			rsort($arParams['WIDTH']);
		} else {
			unset($arParams['RESPONSIVE_VALUE']);
		};
		if ((int) $arParams['CACHE_TTL'] == 0) {
			$arParams['CACHE_TTL'] = 2592000; /* 30 дней */
		};
		if (empty($arParams['LAZYLOAD'])) {
			$arParams['LAZYLOAD'] = 'Y';
		}
		$arParams['1PX']['src'] = str_replace([$arParams['DOCUMENT_ROOT'], '/lib/'], '', __DIR__).'/images/1px.png';
		$arParams['1PX']['webp'] = str_replace([$arParams['DOCUMENT_ROOT'], '/lib/'], '', __DIR__).'/images/1px.png';
		$this->arParams = $arParams;
	}

	public function doIt(&$content)
	{
		$arParams = $this->arParams;
		if ($arParams['DEBUG'] == 'Y') {
			\Bitrix\Main\Diag\Debug::writeToFile(['ReplaceImg_'.date('Y.M.d H:i:s') => 'start' ]);
		};

		$this->ReplaceImg($content);

		if ($arParams['DEBUG'] == 'Y') {
			\Bitrix\Main\Diag\Debug::writeToFile(['ReplaceBackground_'.date('Y.M.d H:i:s') => 'start']);
		};

		$this->ReplaceBackground($content);
	}

	public function ReplaceBackground(&$content)
	{
		$arParams = $this->arParams;
		$preg = '/<[^>]+style[^>]*=[^>]*(background(-image)*\s*:\s*url\((.*)\))[^>]*>/ismuU';
		$tagkey = 0;
		$backgroundKey = 1;
		$srckey = 3;

		if (preg_match_all($preg, $content, $matches)) {
			if ($arParams['DEBUG'] == 'Y') {
				\Bitrix\Main\Diag\Debug::writeToFile(['FOUND background array' => $matches]);
			};


			$cache = \Bitrix\Main\Data\Cache::createInstance();
			$cachePath = self::cachePath;
			$cacheTtl = (int) $arParams['CACHE_TTL'];

			$arAllreadyReplaced = [];


			foreach ($matches[$tagkey] as $key=>$tag) {
				if ($arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['FOUND background el' => $tag]);
				};

				$need = true;
				$img['tag'] = $matches[$tagkey][$key];
				$img['bg']  = $matches[$backgroundKey][$key];
				$img['src'] = $matches[$srckey][$key];
				$img['src'] = trim($img['src'], '"');
				$img['src'] = trim($img['src'], "'");

				if ($arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['FOUND background img' => $img]);
				};
				if (in_array($img['tag'], $arAllreadyReplaced)) {
					$need = false;
					if ($arParams['DEBUG'] == 'Y') {
						\Bitrix\Main\Diag\Debug::writeToFile(['TAG ALLREADY REPLACED']);
					};
				};

				$cacheKey =  md5($img['tag']);;

				if ($cache->initCache($cacheTtl, $cacheKey, $cachePath)) {
					$arResult = $cache->getVars();
					if ($arParams['DEBUG'] == 'Y') {
						\Bitrix\Main\Diag\Debug::writeToFile(['GET_FROM_CACHE' => $arResult]);
					};
				} elseif ($cache->startDataCache()) {
					$arResult = [];
					$arResult['place'] = '';
					if (mb_strpos($img['tag'], 'data-i2p')) {
						$need = false;
						if ($arParams['DEBUG'] == 'Y') {
							\Bitrix\Main\Diag\Debug::writeToFile(['TAG IS HAVE data-i2p']);
						};
					};

					if ($need) {
						$need = $this->ExceptionBySrc($img['src']);
					};

					if ($need) {
						$arResult['img'] = $img;
						$arResult['md5key'] = $cacheKey;
						$files = $this->PrepareResponsive($img['src'], $arParams['WIDTH']);
						if ($files) {
							$arResult['FILES'] =  $files;
							if ($arResult['FILES'][self::smallWidth]['src'] == '') {
								$arResult['FILES'][self::smallWidth]['src'] = $arResult['img']['src'];
							}
							$arResult['FILES']['original'] = $this->PrepareOriginal($arResult['img']['src']);
							if ($arParams['DEBUG'] == 'Y') {
								\Bitrix\Main\Diag\Debug::writeToFile(['TAG FILES' => $arResult['FILES']]);
							};
							$arResult['cssSelector'] = '[data-i2p="'.$arResult['md5key'].'"]';
							$arResult['style'] = '<style>';
							$arResult['style'] .= '*'.$arResult['cssSelector'].'{background-image:url('.$arResult['FILES'][self::smallWidth]['src'].')}';
							foreach ($arParams['RESPONSIVE_VALUE'] as $key => $val) {
								if (!is_array($arResult['FILES'][$val['width']])) {
									continue;
								};
								if (count($arResult['FILES'][$val['width']])==0) {
									continue;
								};
								$haveFiles = false;
								$addsourse  = ['', ''];
								$addsourseLazy  = ['', ''];
								foreach ($arResult['FILES'][$val['width']] as $file_type => $file_src) {
									if ($file_type == 'webp') {
										$haveFiles = true;
										$addsourse[1] = '.webp'.$arResult['cssSelector'].'{background-image:url('. $file_src.')}';
										$addsourseLazy[1] = '.webp.loaded'.$arResult['cssSelector'].'{background-image:url('. $file_src.')}';
									} else {
										$haveFiles = true;
										$addsourse[0] = '*'.$arResult['cssSelector'].'{background-image:url('. $file_src.')}';
										$addsourseLazy[0] = '.loaded'.$arResult['cssSelector'].'{background-image:url('. $file_src.')}';
									};
								}
								if ($haveFiles) {
									$arResult['style'] .= '@media ';
									$styleand = '';
									if ((int) $val['min'] > 0) {
										$arResult['style'] .= '(min-width: ' . $val['min'] . 'px)';
										$styleand = ' and ';
									};
									/*
									if ((int) $val['max'] > (int) $val['min']) {
										$arResult['style'] .= $styleand . '(max-width: ' . $val['max'] . 'px)';
									};
									*/
									$arResult['style'] .= '{';
									if ($arParams['LAZYLOAD'] != "Y") {
										$arResult['style'] .= $addsourse[0].$addsourse[1];
									} else {
										$arResult['style'] .= $addsourseLazy[0].$addsourseLazy[1];
									}
									$arResult['style'] .= '}';
								}
							}
							$arResult['style'] .= '</style>';

							$arResult['place'] = $arResult['style'].
								str_replace(
									[
										$img['bg'],
										' style'
									],
									[
										'',
										' data-i2p="'.$arResult['md5key'].'"  style'
									],
									$img['tag']
								);
						};
					};

					if ($arParams['MODULE_CONFIG']['MODULE_ID'] != '') {
						foreach (GetModuleEvents($arParams['MODULE_CONFIG']['MODULE_ID'], 'OnPrepareResultBackground', true) as $arEvent) {
							ExecuteModuleEventEx($arEvent, array(&$arResult));
						};
					}

					$cache->endDataCache($arResult);
				};
				if (trim($arResult['place']) != '') {
					$arAllreadyReplaced[] = $img['tag'];
					$content = str_replace($img['tag'], $arResult['place'], $content);
					if ($arParams['DEBUG'] == 'Y') {
						\Bitrix\Main\Diag\Debug::writeToFile([
								'REPLACED_FROM' => $img['tag'],
								'REPLACED_TO' => $arResult['place']
							]);
					};
				};
			};
		};
	}

	public function ReplaceImg(&$content)
	{
		$arParams = $this->arParams;
		$arPicture = $this->get_tags('picture', $content, true);
		$arImg = $this->get_tags('img', $content, false);

		$cache = \Bitrix\Main\Data\Cache::createInstance();
		$cachePath = self::cachePath;
		$cacheTtl = (int) $arParams['CACHE_TTL'];
		$arAllreadyReplaced = [];

		foreach ($arImg as $img) {

			$need = true;
			$arResult = [];
			if ($arParams['DEBUG'] == 'Y') {
				\Bitrix\Main\Diag\Debug::writeToFile(['FOUND_IMG' => $img]);
			};
			if (trim($img['src']) == '') {
				$need = false;
				if ($arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['IMG SRC IS EMPTY']);
				};
				continue;
			};

			if (in_array($img['tag'], $arAllreadyReplaced)) {
				$need = false;
				if ($arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['IMG ALLREADY REPLACED']);
				};
				continue;
			};

			$cacheKey =  md5($img['tag']);;

			if ($cache->initCache($cacheTtl, $cacheKey, $cachePath)) {
				$arResult = $cache->getVars();
				if ($arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['GET_FROM_CACHE' => $place]);
				};
			} elseif ($cache->startDataCache()) {
				$arResult = [];
				$arResult['place'] = '';

				/* проверим на исключения */
				$need = $this->ExceptionBySrc($img['src']);
				if ($need) {
					$need = $this->ExceptionByTag($img['tag']);
				};

				if ($need) {
					/* Проверим есть ли наше изображение уже в picture */
					if (is_array($arPicture)) {
						foreach ($arPicture as $picture) {
							if (mb_strpos($picture['tag'], $img['tag'])) {
								$need = false;
								if ($arParams['DEBUG'] == 'Y') {
									\Bitrix\Main\Diag\Debug::writeToFile(['EXCEPTIONS BY ALLREADY IN PICTURE' => $picture['tag']]);
								};
								break;
							};
						};
					};
				};
				if ($need) {
					$arResult['img'] = $img;
					$arResult['sources'] = [];

					$PreparedOriginal = $this->PrepareOriginal($img['src']);

					$files = $this->PrepareResponsive($img['src'], $arParams['WIDTH']);
					if ($files) {
						$arResult['FILES'] =  $files;

						if ($arParams['DEBUG'] == 'Y') {
							\Bitrix\Main\Diag\Debug::writeToFile(['CREATE_FILES' => $arResult['FILES']]);
						};

						foreach ($arParams['RESPONSIVE_VALUE'] as $key => $val) {
							if (!is_array($arResult['FILES'][$val['width']])) {
								continue;
							};
							if (count($arResult['FILES'][$val['width']])==0) {
								continue;
							};
							$addsourse = ['', ''];
							$addsourseLazy= ['', ''];
							foreach ($arResult['FILES'][$val['width']] as $file_type => $file_src) {
								if ($file_type == 'webp') {
									$type = 'type="image/webp"';
									if ($arResult['FILES'][self::smallWidth]['webp'] !== '') {
										$lazy = 'srcset="'.$arResult['FILES'][self::smallWidth]['webp'].'"';
									} else {
										$lazy = 'srcset="'.$arParams['1PX']['webp'].'"';
									}
									$index = 0;
								} else {
									$ext = substr(strrchr($file_src, '.'), 1);
									if ($ext == 'jpg') {
										$ext = 'jpeg';
									}
									$type = 'type="image/' . $ext . '"';
									if ($arResult['FILES'][self::smallWidth]['src'] != '') {
										$lazy = 'srcset="'.$arResult['FILES'][self::smallWidth]['src'].'"';
									} else {
										$lazy = 'srcset="'.$arParams['1PX']['webp'].'"';
									}
									$index = 1;
								};
								$media = 'media="';
								$mediaand = '';
								if ((int) $val['min'] > 0) {
									$media .= $mediaand . '(min-width: ' . $val['min'] . 'px)';
									$mediaand = ' and ';
								};
								/*
								if ((int) $val['max'] > (int) $val['min']) {
									$media .= $mediaand . '(max-width: ' . $val['max'] . 'px)';
								};
								*/
								$media .= '"';
								$addsourse[$index] = '<source srcset="' . $file_src . '" ' . $media . ' ' . $type . '>';
								$addsourseLazy[$index] = '<source '.$lazy.' data-i2p="Y" data-srcset="' . $file_src . '" ' . $media . ' ' . $type . '>';
							};
							if ($addsourse[0] != '') {
 								$arResult['sources'][] = $addsourse[0];
							}
							if ($addsourse[1] != '') {
								$arResult['sources'][] = $addsourse[1];
						    }
							if ($addsourseLazy[0] != '') {
								$arResult['sources_lazy'][] = $addsourseLazy[0];
							}
							if ($addsourseLazy[1] != '') {
								$arResult['sources_lazy'][] = $addsourseLazy[1];
							}
						};
					};

					$arResult['FILES']['original'] = $PreparedOriginal;
					if ($arResult['FILES']['original']['webp']) {
						if ($arResult['FILES'][self::smallWidth]['webp'] !== '') {
							$lazy = 'srcset="'.$arResult['FILES'][self::smallWidth]['webp'].'"';
						} else {
							$lazy = 'srcset="'.$arParams['1PX']['webp'].'"';
						}
						$arResult['sources'][] = '<source srcset="' . $arResult['FILES']['original']['webp'] . '"  type="image/webp">';
						$arResult['sources_lazy'][] = '<source '.$lazy.'  data-i2p="Y" data-srcset="' . $arResult['FILES']['original']['webp'] . '"  type="image/webp">';
					};

					if ($arParams['DEBUG'] == 'Y') {
						\Bitrix\Main\Diag\Debug::writeToFile(['CREATED arResult' => $arResult]);
					};

					$arResult["img_lazy"]["tag"] = '<img ';
					foreach ($arResult["img"] as $attr_name=>$attr_val) {
						if ($attr_name != 'tag') {
							if ($attr_name == 'src') {
								$arResult["img_lazy"]["tag"] .= ' data-i2p="Y" data-srcset="'.$attr_val.'"';
								if ($arResult['FILES'][self::smallWidth]['src'] != '') {
									$arResult["img_lazy"]["tag"] .= ' srcset="'.$arResult['FILES'][self::smallWidth]['src'].'"';
								} else {
									$arResult["img_lazy"]["tag"] .= ' srcset="'.$arParams['1PX']['src'].'"';
								}
							}
							$arResult["img_lazy"]["tag"] .= ' '.$attr_name.'="'.$attr_val.'"';
						}
					}
					$arResult["img_lazy"]["tag"] .= '>';

					if ($arParams['LAZYLOAD'] != "Y") {
						if (count($arResult["sources"]) > 0) {
							$arResult['place'] = '<picture>';
							foreach ($arResult["sources"] as $source){
								$arResult['place'] .= $source;
							};
							$arResult['place'] .= $arResult["img"]["tag"];
							$arResult['place'] .= '</picture>';
						} else {
							$arResult['place'] = $arResult["img"]["tag"];
						}
					} else {
						if (count($arResult["sources_lazy"]) > 0) {
							$arResult['place'] = '<picture  data-i2p="Y">';
							foreach ($arResult["sources_lazy"] as $source){
								$arResult['place'] .= $source;
							};
							$arResult['place'] .= $arResult["img_lazy"]["tag"];
							$arResult['place'] .= '</picture>';
						} else {
							$arResult['place'] = $arResult["img_lazy"]["tag"];
						}
					};
				};

				if ($arParams['MODULE_CONFIG']['MODULE_ID'] != '') {
					foreach (GetModuleEvents($arParams['MODULE_CONFIG']['MODULE_ID'], 'OnPrepareResultImg', true) as $arEvent) {
						ExecuteModuleEventEx($arEvent, array(&$arResult));
					};
				}

				$cache->endDataCache($arResult);
			};

			if (trim($arResult['place']) != '') {
				$arAllreadyReplaced[] = $img['tag'];
				$content = str_replace($img['tag'], $arResult['place'], $content);
				if ($arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile([
							'REPLACED_FROM' => $img['tag'],
							'REPLACED_TO' => $arResult['place']
						]);
				};
			};
		};
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
						\Bitrix\Main\Diag\Debug::writeToFile(['EXCEPTIONS_SRC_REG' => $exception]);
					};
					break;
				};
			};
		};
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
						\Bitrix\Main\Diag\Debug::writeToFile(['EXCEPTIONS_TAG_REG' => $exception]);
					};
					break;
				};
			};
		};
		return $result;
	}

	function PrepareOriginal($src)
	{
		$arParams = $this->arParams;
		$arResult['src'] = $src;
		$ext = substr(strrchr($src, '.'), 1);
		if ($ext == 'jpg') {
			$ext = 'jpeg';
		};
		$arResult['type'] = 'image/' . $ext;

		if ($arParams['USE_WEBP'] == 'Y') {
			$webpSrc = $this->ConvertImg2webp($src);
			if ($webpSrc) {
				$arResult['webp'] = $webpSrc;
			};
		};
		return $arResult;
	}

	function PrepareResponsive(string $src, array $arWidth)
	{
		$doc_root = $this->arParams['DOCUMENT_ROOT'];

		$loaded = false;
		$height = self::max_width;

		/* проверим существует ли файл вообще */
		if (!file_exists($doc_root . $src)) {
			return false;
		};

		$arResult = [];

		/* подготовим файлы для каждой ширины */
		foreach ($arWidth as $width) {
			$resized = false;
			$newsrc = self::DIR . '/' . $width . '/' .  $src;
			$newsrc = str_replace('//', '/', $newsrc);
			$filename = $doc_root . $newsrc;

			$arResult[$width]['src'] = $newsrc;

			if (!file_exists($filename)) {
				if (!$loaded) {
					if (!$this->load($doc_root . $src)) {
						return false;
					};
					$loaded = true;
				};
				if ($loaded) {
					$resized = $this->smallTo($width, $height);
					if ($resized) {
						$this->CreateDir($filename, true);
						if (!$this->save($filename, $this->image_type, $this->arParams['IMG_COMPRESSION'])) {
							unset($arResult[$width]['src']);
						};
					} else {
						unset($arResult[$width]['src']);
					}
				} else {
					unset($arResult[$width]['src']);
				}
			}
			if ($this->arParams['USE_WEBP'] !== 'Y') {
				continue;
			};

			/* подготовим webp */
			$filename = $doc_root . $newsrc . '.webp';
			$arResult[$width]['webp'] = $newsrc . '.webp';
			if (!file_exists($filename)) {
				if (!$loaded) {
					if (!$this->load($doc_root . $src)) {
						return false;
					};
					$loaded = true;
				};
				if ($loaded) {
					if (!$resized) {
						$resized = $this->smallTo($width, $height);
					}
					if ($resized) {
						$this->CreateDir($filename, true);
						if (!$this->save($filename, IMAGETYPE_WEBP, $this->arParams['IMG_COMPRESSION'])) {
							unset($arResult[$width]['webp']);
						};
						if (filesize($filename) == 0) {
							unset($arResult[$width]['webp']);
						}
					} else {
						unset($arResult[$width]['webp']);
					}
				} else {
					unset($arResult[$width]['webp']);
				}
			} else {
				if (filesize($filename) == 0) {
					unset($arResult[$width]['webp']);
				}
			}
		}
		return $arResult;
	}

	public function ConvertImg2webp(string $src)
	{
		$need = false;
		$doc_root = $this->arParams['DOCUMENT_ROOT'];
		$webp = self::DIR . $src . '.webp';
		$webp = str_replace('//', '/', $webp);
		$filename = $doc_root . $webp;

		if (!file_exists($doc_root . $webp)) {
			$need = true;
		} else {
			if (filesize($filename) == 0) {
				return false;
			};
		};

		if ($need) {

			$this->CreateDir($filename, true);
			if (!$this->load($doc_root . $src)) {
				return false;
			};
			if (!$this->save($filename, IMAGETYPE_WEBP, $this->arParams['IMG_COMPRESSION'])) {
				return false;
			};
			if (filesize($filename) == 0) {
				return false;
			};
		};

		return $webp;
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
		};
		$resultdir = '';
		foreach ($dirs as $dir) {
			$resultdir .= $dir;
			if ($dir != '') {
				@mkdir($resultdir);
			};
			$resultdir .= '/';
		};
		return $resultdir;
	}

	public function RemoveDir($path)
	{

		$files = glob($path . '/*');
		if ($files) {
			foreach ($files as $file) {
				is_dir($file) ? self::RemoveDir($file) : @unlink($file);
			};
		};
		@rmdir($path);

		return;
	}

	function get_tags($tag, $content, $haveClosedTag = true)
	{
		if ($haveClosedTag) {
			$arTag['tag'] = '/(<' . $tag . '[^>]*>)(.*)<\/' . $tag . '>/ismuU';;
		} else {
			$arTag['tag'] = '/(<' . $tag . '[^>]*>)/ismuU';
		};
		$arTag['attr'][0] = '/\s+([a-zA-Z-]+)\s*=\s*"([^"]*)"/ismuU';
		$arTag['attr'][] = str_replace('"', "'", $arTag['attr'][0]);
		$result = array();
		if (preg_match_all($arTag['tag'], $content, $matches)) {
			foreach ($matches[0] as $k => $match) {
				$res_tag = array();
				$res_tag['tag'] = $match;
				if (isset($matches[1][$k])) {
					foreach ($arTag['attr'] as $arTagAttr) {
						unset($attr_matches);
						preg_match_all($arTagAttr, $matches[1][$k], $attr_matches);
						if (is_array($attr_matches[1])) {
							foreach ($attr_matches[1] as $key => $val) {
								$res_tag[$val] = $attr_matches[2][$key];
							};
						};
					};
				};
				if (isset($matches[2][$k])) {
					$res_tag['text'] = $matches[2][$k];
				};
				$result[] = $res_tag;
			};
		};
		return $result;
	}
}
