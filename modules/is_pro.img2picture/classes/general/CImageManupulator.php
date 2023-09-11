<?

namespace IS_PRO\img2picture;

use IS_PRO\img2picture\CSimpleImage;

if (class_exists('\IS_PRO\img2picture\CImageManupulator')) {
	return;
}


class CImageManupulator extends CSimpleImage
{
	const
		DIR = '/upload/img2picture/',
		max_width = 99999,
		cachePath  = 'img2picture',
		smallWidth = 100,
		onePXpng = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
		onePXwebp = 'data:image/webp;base64,UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA',
		onePXavif = 'data:image/avif;base64,AAAAFGZ0eXBhdmlmAAAAAG1pZjEAAACgbWV0YQAAAAAAAAAOcGl0bQAAAAAAAQAAAB5pbG9jAAAAAEQAAAEAAQAAAAEAAAC8AAAAGwAAACNpaW5mAAAAAAABAAAAFWluZmUCAAAAAAEAAGF2MDEAAAAARWlwcnAAAAAoaXBjbwAAABRpc3BlAAAAAAAAAAQAAAAEAAAADGF2MUOBAAAAAAAAFWlwbWEAAAAAAAAAAQABAgECAAAAI21kYXQSAAoIP8R8hAQ0BUAyDWeeUy0JG+QAACANEkA=';

	var $arParams = array();

	public function __construct($arParams)
	{
		/* DOCUMENT_ROOT */
		if (empty($arParams['DOCUMENT_ROOT'])) {
			$arParams['DOCUMENT_ROOT'] = \Bitrix\Main\Application::getDocumentRoot();
		};

		/* ATTR_SRC_VALUES */
		$arParams['ATTR_SRC_VALUES'] = [];
		if (!empty($arParams['ATTR_SRC'])) {
			$arAttrs = [];
			$arAttrs = explode("\n", $arParams['ATTR_SRC']);
			if (is_array($arAttrs)) {
				foreach ($arAttrs as $k => $v) {
					if (trim($v) == '') {
						continue;
					};
					$arParams['ATTR_SRC_VALUES'][] = trim($v);
				};
			};
		};
		if (!isset($arParams['ATTR_SRC_VALUES']) || (count($arParams['ATTR_SRC_VALUES']) == 0)) {
			$arParams['ATTR_SRC_VALUES'][] = 'src';
		};

		/* EXCEPTIONS_SRC_REG */
		if (!empty($arParams['EXCEPTIONS_SRC'])) {
			$arParams['EXCEPTIONS_SRC_REG'] = [];
			$arExceptions = [];
			$arExceptions = explode("\n", $arParams['EXCEPTIONS_SRC']);
			if (is_array($arExceptions)) {
				foreach ($arExceptions as $k => $v) {
					if (trim($v) == '') {
						continue;
					};
					$arParams['EXCEPTIONS_SRC_REG'][] = '|' . trim($v) . '|';
				};
			};
		};

		/* EXCEPTIONS_TAG_REG */
		if (!empty($arParams['EXCEPTIONS_TAG'])) {
			$arParams['EXCEPTIONS_TAG_REG'] = [];
			$arExceptions = [];
			$arExceptions = explode("\n", $arParams['EXCEPTIONS_TAG']);
			if (is_array($arExceptions)) {
				foreach ($arExceptions as $k => $v) {
					if (trim($v) == '') {
						continue;
					};
					$arParams['EXCEPTIONS_TAG_REG'][] = '|' . trim($v) . '|';
				};
			};
		};

		/* IMG_COMPRESSION */
		if ((int) $arParams['IMG_COMPRESSION'] == 0) {
			$arParams['IMG_COMPRESSION'] = 75;
		};

		/* WIDTH */
		if (is_array($arParams['RESPONSIVE_VALUE'])) {
			foreach ($arParams['RESPONSIVE_VALUE'] as $key => $val) {
				$arParams['WIDTH'][] = $val['width'];
			};
			$arParams['WIDTH'][] = self::smallWidth;
			rsort($arParams['WIDTH']);
		} else {
			unset($arParams['RESPONSIVE_VALUE']);
		};

		/* LAZYLOAD */
		if (empty($arParams['LAZYLOAD'])) {
			$arParams['LAZYLOAD'] = 'Y';
		}

		/* CACHE_TTL */
		if ((int) $arParams['CACHE_TTL'] == 0) {
			$arParams['CACHE_TTL'] = 2592000; /* 30 дней */
		};

		$this->arParams = $arParams;
	}

	public function doIt(&$content)
	{
		$arParams = $this->arParams;
		if ($arParams['DEBUG'] == 'Y') {
			\Bitrix\Main\Diag\Debug::writeToFile(['ReplaceImg_' . date('Y.M.d H:i:s') => 'start']);
		};

		$this->ReplaceImg($content);

		if ($arParams['BACKGROUNDS'] == 'Y') {

			if ($arParams['DEBUG'] == 'Y') {
				\Bitrix\Main\Diag\Debug::writeToFile(['ReplaceBackground_' . date('Y.M.d H:i:s') => 'start']);
			};

			$this->ReplaceBackground($content);
		}
	}

	public function ReplaceBackground(&$content)
	{
		$arParams = $this->arParams;
		$preg = '/<[^>]+style[^>]*=[^>]*(background(-image)*\s*:\s*url\((.*)\))[^>]*\>/ismuU';
		$tagkey = 0;
		$srckey = 3;

		if (preg_match_all($preg, $content, $matches)) {
			if ($arParams['DEBUG'] == 'Y') {
				\Bitrix\Main\Diag\Debug::writeToFile(['FOUND background array' => $matches]);
			};


			$cache = \Bitrix\Main\Data\Cache::createInstance();
			$cachePath = self::cachePath;
			$cacheTtl = (int) $arParams['CACHE_TTL'];

			$arAllreadyReplaced = [];

			foreach ($matches[$tagkey] as $key => $tag) {
				if ($arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['FOUND background el' => $tag]);
				};

				$need = true;
				$img['tag'] = $matches[$tagkey][$key];
				$img['src'] = trim($matches[$srckey][$key], '"'."'");

				if ($arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['FOUND background img' => $img]);
				};
				if (in_array($img['tag'], $arAllreadyReplaced)) {
					$need = false;
					if ($arParams['DEBUG'] == 'Y') {
						\Bitrix\Main\Diag\Debug::writeToFile(['TAG ALLREADY REPLACED']);
					};
				};

				$cacheKey =  md5($img['tag']);

				if (($cache->initCache($cacheTtl, $cacheKey, $cachePath)) && (empty($arParams['CLEAR_CACHE']))) {
					$cachedPlace = $cache->getVars();
					if ($arParams['DEBUG'] == 'Y') {
						\Bitrix\Main\Diag\Debug::writeToFile(['GET_FROM_CACHE' => $cachedPlace]);
					};
					if (is_array($cachedPlace)) {
						$cachedPlace = $cachedPlace['place'];
					}
				} else {
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
						$arResult = $this->PrepareResultBackground($img, $arParams);
					};

					if ($arParams['MODULE_CONFIG']['MODULE_ID'] != '') {
						foreach (GetModuleEvents($arParams['MODULE_CONFIG']['MODULE_ID'], 'OnPrepareResultBackground', true) as $arEvent) {
							ExecuteModuleEventEx($arEvent, array(&$arResult));
						};
					};
					$cachedPlace = $arResult['place'];
					if ($cache->startDataCache()) {
						$cache->endDataCache($cachedPlace);
					};
				};
				$arResult['place'] = $cachedPlace;
				if ((trim($arResult['place']) != '') && (mb_strpos($arResult['place'], '</style>'))) {
					list($tohead, $newtag) = explode('</style>', $arResult['place']);
					$tohead .= '</style></head>';
					$arAllreadyReplaced[] = $img['tag'];
					$content = str_replace(
						['</head>', $img['tag']],
						[$tohead, $newtag],
						$content
					);
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

			$found_src = false;
			$attr_src = '';
			foreach ($arParams['ATTR_SRC_VALUES'] as $attr_src) {
				if (trim($img[$attr_src]) !== '') {
					$found_src = true;
					break;
				}
			}
			if (!$found_src) {
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

			if (($cache->initCache($cacheTtl, $cacheKey, $cachePath)) && (empty($arParams['CLEAR_CACHE']))) {
				$cachedPlace = $cache->getVars();
				if ($arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['GET_FROM_CACHE' => $cachedPlace]);
				};
				if (is_array($cachedPlace)) {
					$cachedPlace = $arResult['place'];
				}
			} else {
				$arResult = [];
				$arResult['place'] = '';

				/* проверим на исключения */
				$need = $this->ExceptionBySrc($img[$attr_src]);
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
					$arResult = $this->PrepareResultImg($img, $attr_src, $arParams);
				};

				if ($arParams['MODULE_CONFIG']['MODULE_ID'] != '') {
					foreach (GetModuleEvents($arParams['MODULE_CONFIG']['MODULE_ID'], 'OnPrepareResultImg', true) as $arEvent) {
						ExecuteModuleEventEx($arEvent, array(&$arResult));
					};
				}
				$cachedPlace = $arResult['place'];
				if ($cache->startDataCache()) {
					$cache->endDataCache($cachedPlace);
				}
			};
			$arResult['place'] = $cachedPlace;;
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
		if ($arParams['USE_AVIF'] == 'Y') {
			$avifSrc = $this->ConvertImg2avif($src);
			if ($avifSrc) {
				$arResult['avif'] = $avifSrc;
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
				))
			) {
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
			$loaded = false;
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
						};
						$loaded = true;
					};
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
		}
		return $arResult;
	}

	public function PrepareResultImg($img, $attr_src, $arParams)
	{
		$arResult['img'] = $img;
		$arResult['sources'] = [];

		$PreparedOriginal = $this->PrepareOriginal($img[$attr_src]);

		$files = $this->PrepareResponsive($img[$attr_src], $arParams['WIDTH']);
		if ($files) {
			$arResult['FILES'] =  $files;

			if ($arParams['DEBUG'] == 'Y') {
				\Bitrix\Main\Diag\Debug::writeToFile(['CREATE_FILES' => $arResult['FILES']]);
			};

			foreach ($arParams['RESPONSIVE_VALUE'] as $key => $val) {
				if (!is_array($arResult['FILES'][$val['width']])) {
					continue;
				};
				if (count($arResult['FILES'][$val['width']]) == 0) {
					continue;
				};
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
					} else {
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
					};
					$media = 'media="';
					$mediaand = '';
					if ((int) $val['min'] >= 0) {
						$media .= $mediaand . '(min-width: ' . $val['min'] . 'px)';
						$mediaand = ' and ';
					};

					if ((int) $val['max'] > (int) $val['min']) {
						$media .= $mediaand . '(max-width: ' . $val['max'] . 'px)';
					};

					$media .= '"';
					$addsourse[$index] = '<source srcset="' . $file_src . '" ' . $media . ' ' . $type . '>';
					$addsourseLazy[$index] = '<source ' . $lazy . ' data-i2p="Y" data-srcset="' . $file_src . '" ' . $media . ' ' . $type . '>';
				};
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
			};
		};

		$arResult['FILES']['original'] = $PreparedOriginal;

		if ($arResult['FILES']['original']['avif']) {
			if (!empty($arResult['FILES'][self::smallWidth]['avif'])) {
				$lazy = 'srcset="' . $arResult['FILES'][self::smallWidth]['avif'] . '"';
			} else {
				$lazy = 'srcset="' . self::onePXavif . '"';
			}
			$arResult['sources'][] = '<source srcset="' . $arResult['FILES']['original']['avif'] . '"  type="image/avif">';
			$arResult['sources_lazy'][] = '<source ' . $lazy . '  data-i2p="Y" data-srcset="' . $arResult['FILES']['original']['avif'] . '"  type="image/avif">';
		};

		if ($arResult['FILES']['original']['webp']) {
			if (!empty($arResult['FILES'][self::smallWidth]['webp'])) {
				$lazy = 'srcset="' . $arResult['FILES'][self::smallWidth]['webp'] . '"';
			} else {
				$lazy = 'srcset="' . self::onePXwebp . '"';
			}
			$arResult['sources'][] = '<source srcset="' . $arResult['FILES']['original']['webp'] . '"  type="image/webp">';
			$arResult['sources_lazy'][] = '<source ' . $lazy . '  data-i2p="Y" data-srcset="' . $arResult['FILES']['original']['webp'] . '"  type="image/webp">';
		};

		if ($arParams['DEBUG'] == 'Y') {
			\Bitrix\Main\Diag\Debug::writeToFile(['CREATED arResult' => $arResult]);
		};

		$arResult["img_lazy"]["tag"] = '<img ';
		foreach ($arResult["img"] as $attr_name => $attr_val) {
			if ($attr_name != 'tag') {
				if ($attr_name == 'src') {
					$arResult["img_lazy"]["tag"] .= ' data-i2p="Y" data-srcset="' . $attr_val . '"';
					if (!empty($arResult['FILES'][self::smallWidth]['src'])) {
						$arResult["img_lazy"]["tag"] .= ' srcset="' . $arResult['FILES'][self::smallWidth]['src'] . '"';
					} else {
						$arResult["img_lazy"]["tag"] .= ' srcset="' . self::onePXpng . '"';
					}
				}
				$arResult["img_lazy"]["tag"] .= ' ' . $attr_name . '="' . $attr_val . '"';
			}
		}
		$arResult["img_lazy"]["tag"] .= '>';

		if ($arParams['LAZYLOAD'] != "Y") {
			if ((isset($arResult["sources"])) && (count($arResult["sources"]) > 0)) {
				$arResult['place'] = '<picture>';
				foreach ($arResult["sources"] as $source) {
					$arResult['place'] .= $source;
				};
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
				};
				$arResult['place'] .= $arResult["img_lazy"]["tag"];
				$arResult['place'] .= '</picture>';
			} else {
				$arResult['place'] = $arResult["img_lazy"]["tag"];
			}
		};
		return $arResult;
	}


	public function PrepareResultBackground($img, $arParams)
	{
		$img['parse_tag'] = $this->get_tags('', $img['tag'], false);
		$img['parse_tag'] = $img['parse_tag'][0];

		$arStyleParams = explode(';', $img['parse_tag']['style']);
		if ((is_array($arStyleParams)) && (count($arStyleParams) > 0)) {
			foreach ($arStyleParams as $strStyleParam) {
				$strStyleParam = trim($strStyleParam);
				if ((mb_strpos($strStyleParam, 'background') !== false) && (mb_strpos($strStyleParam, 'url') !== false))  {
					$img['parse_tag']['style'] = $strStyleParam;
					break;
				}
			}
		}
		$arResult['img'] = $img;
		$arResult['md5key'] = md5($img['tag']);
		$files = $this->PrepareResponsive($img['src'], $arParams['WIDTH']);
		$PreparedOriginal = $this->PrepareOriginal($img['src']);
		if ($files) {
			$arResult['FILES'] =  $files;
			if ($arResult['FILES'][self::smallWidth]['src'] == '') {
				$arResult['FILES'][self::smallWidth]['src'] = $arResult['img']['src'];
			}
			$arResult['FILES']['original'] = $this->PrepareOriginal($arResult['img']['src']);
			if ($arParams['DEBUG'] == 'Y') {
				\Bitrix\Main\Diag\Debug::writeToFile(['TAG FILES' => $arResult['FILES']]);
			};
			$arResult['cssSelector'] = '[data-i2p="' . $arResult['md5key'] . '"]';
			$arResult['style'] = '<style>';
			$arResult['style'] .= '*' . $arResult['cssSelector'] . '{'.str_replace($arResult['img']['src'], $arResult['FILES'][self::smallWidth]['src'], $arResult['img']['parse_tag']['style']).'}';
			foreach ($arParams['RESPONSIVE_VALUE'] as $key => $val) {
				if (!is_array($arResult['FILES'][$val['width']])) {
					continue;
				};
				if (count($arResult['FILES'][$val['width']]) == 0) {
					continue;
				};
				$haveFiles = false;
				$addsourse  = ['', ''];
				$addsourseLazy  = ['', ''];
				$minmax = 0;

				foreach ($arResult['FILES'][$val['width']] as $file_type => $file_src) {
					if ($file_type == 'avif') {
						$haveFiles = true;
						$addsourse[2] = '.avif' . $arResult['cssSelector'] . '{'.str_replace($arResult['img']['src'], $file_src, $arResult['img']['parse_tag']['style']).'}';
						$addsourseLazy[2] = '.loaded' . $addsourse[2];
					} else if ($file_type == 'webp') {
						$haveFiles = true;
						$addsourse[1] = '.webp' . $arResult['cssSelector'] . '{'.str_replace($arResult['img']['src'], $file_src, $arResult['img']['parse_tag']['style']).'}';
						$addsourseLazy[1] = '.loaded' . $addsourse[1];
					} else {
						$haveFiles = true;
						$addsourse[0] = '' . $arResult['cssSelector'] . '{'.str_replace($arResult['img']['src'], $file_src, $arResult['img']['parse_tag']['style']).'}';
						$addsourseLazy[0] = '.loaded' . $addsourse[0];
					};
				}
				if ($haveFiles) {
					$arResult['style'] .= '@media ';
					$styleand = '';
					if ((int) $val['min'] >= 0) {
						$arResult['style'] .= '(min-width: ' . $val['min'] . 'px)';
						$styleand = ' and ';
						if ($minmax < $val['min']) {
							$minmax = $val['min'];
						};
					};
					if ((int) $val['max'] > (int) $val['min']) {
						$arResult['style'] .= $styleand . '(max-width: ' . $val['max'] . 'px)';
						if ($minmax < $val['max']) {
							$minmax = $val['max'];
						};
					};
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
			$arResult['FILES']['original'] = $PreparedOriginal;
			$arResult['style'] .= '@media (min-width: ' . (int) $minmax . 'px) {';
			if ($arParams['LAZYLOAD'] != "Y") {
				$arResult['style'] .= '' . $arResult['cssSelector'] . '{'.str_replace($arResult['img']['src'], $arResult['FILES']['original']['src'], $arResult['img']['parse_tag']['style']).'}';
				if ($arResult['FILES']['original']['avif'] != '') {
					$arResult['style'] .= '.avif' . $arResult['cssSelector'] . '{'.str_replace($arResult['img']['src'], $arResult['FILES']['original']['avif'], $arResult['img']['parse_tag']['style']).'}';

				}
				if ($arResult['FILES']['original']['webp'] != '') {
					$arResult['style'] .= '.webp' . $arResult['cssSelector'] . '{'.str_replace($arResult['img']['src'], $arResult['FILES']['original']['webp'], $arResult['img']['parse_tag']['style']).'}';
				}
			} else {
				$arResult['style'] .= '.loaded' . $arResult['cssSelector'] . '{'.str_replace($arResult['img']['src'], $arResult['FILES']['original']['src'], $arResult['img']['parse_tag']['style']).'}';
				if ($arResult['FILES']['original']['avif'] != '') {
					$arResult['style'] .= '.avif.loaded' . $arResult['cssSelector'] . '{'.str_replace($arResult['img']['src'], $arResult['FILES']['original']['avif'], $arResult['img']['parse_tag']['style']).'}';
				}
				if ($arResult['FILES']['original']['webp'] != '') {
					$arResult['style'] .= '.webp.loaded' . $arResult['cssSelector'] . '{'.str_replace($arResult['img']['src'], $arResult['FILES']['original']['webp'], $arResult['img']['parse_tag']['style']).'}';
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
				\Bitrix\Main\Diag\Debug::writeToFile(['CREATED arResult' => $arResult]);
			};
		};
		return $arResult;
	}

	public function ConvertImg2webp(string $src)
	{
		if ($this->arParams['DEBUG'] == 'Y') {
			\Bitrix\Main\Diag\Debug::writeToFile(['TRY CONVERT TO WEBP' => $src]);
		};
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
			if (in_array(filesize($filename), [0, 4096])) {
				return false;
			};
		};

		if ($this->arParams['DEBUG'] == 'Y') {
			\Bitrix\Main\Diag\Debug::writeToFile(['NEED CONVERT TO WEBP' => $need]);
		};

		if ($need) {

			$this->CreateDir($filename, true);
			if (!$this->load($doc_root . $src)) {

				if ($this->arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['ERROR CONVERT TO WEBP' => 'NOT LOAD: ' . $doc_root . $src]);
				};

				return false;
			};
			if (!$this->save($filename, IMAGETYPE_WEBP, $this->arParams['IMG_COMPRESSION'])) {

				if ($this->arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['ERROR CONVERT TO WEBP' => 'NOT SAVE: ' . $filename]);
				};

				return false;
			};
			if (filesize($filename) == 0) {

				if ($this->arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['ERROR CONVERT TO WEBP' => 'SAVED FILE IS EMPTY: ' . $filename]);
				};

				return false;
			};
		};

		if ($this->arParams['DEBUG'] == 'Y') {
			\Bitrix\Main\Diag\Debug::writeToFile(['RESULT CONVERT TO WEBP' => $webp]);
		};

		return $webp;
	}

	public function ConvertImg2avif(string $src)
	{
		if ($this->arParams['DEBUG'] == 'Y') {
			\Bitrix\Main\Diag\Debug::writeToFile(['TRY CONVERT TO AVIF' => $src]);
		};
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
			};
		};

		if ($this->arParams['DEBUG'] == 'Y') {
			\Bitrix\Main\Diag\Debug::writeToFile(['NEED CONVERT TO AVIF' => $need]);
		};

		if ($need) {

			$this->CreateDir($filename, true);
			if (!$this->load($doc_root . $src)) {

				if ($this->arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['ERROR CONVERT TO AVIF' => 'NOT LOAD: ' . $doc_root . $src]);
				};

				return false;
			};
			if (!$this->save($filename, IMAGETYPE_AVIF, $this->arParams['IMG_COMPRESSION'])) {

				if ($this->arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['ERROR CONVERT TO AVIF' => 'NOT SAVE: ' . $filename]);
				};

				return false;
			};
			if (filesize($filename) == 0) {

				if ($this->arParams['DEBUG'] == 'Y') {
					\Bitrix\Main\Diag\Debug::writeToFile(['ERROR CONVERT TO AVIF' => 'SAVED FILE IS EMPTY: ' . $filename]);
				};

				return false;
			};
		};

		if ($this->arParams['DEBUG'] == 'Y') {
			\Bitrix\Main\Diag\Debug::writeToFile(['RESULT CONVERT TO AVIF' => $avif]);
		};

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
