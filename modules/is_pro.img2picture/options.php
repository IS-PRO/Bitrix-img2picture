<?

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\HtmlFilter;
use IS_PRO\img2picture\Cimg2picture;

global $USER;

if (!$USER->IsAdmin()) {
	return;
}

if (file_exists(__DIR__ . "/install/module.cfg.php")) {
	include(__DIR__ . "/install/module.cfg.php");
};

if (!Loader::includeModule($arModuleCfg['MODULE_ID'])) {
	return;
}

Loc::loadMessages(__FILE__);


$currentUrl = $APPLICATION->GetCurPage() . '?mid=' . urlencode($mid) . '&amp;lang=' . LANGUAGE_ID;
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$doc_root = \Bitrix\Main\Application::getDocumentRoot();
$url_module = str_replace($doc_root, '', __DIR__);

$options_list = $arModuleCfg['options_list'];

$ok_message = '';
$eeror_message = '';
$options_list_error = [];

if (check_bitrix_sessid()) {
	if (!empty($request->getpost('save'))) {
		$saveOption = true;
	}

	if (!empty($request->getpost('reset'))) {
		$setDefault = true;
	}
}

$isConfigurated = \Bitrix\Main\Config\Option::get($arModuleCfg['MODULE_ID'], 'IS_CONFIGURATED');
if ($isConfigurated != 'Y') {
	\Bitrix\Main\Config\Option::set($arModuleCfg['MODULE_ID'], 'IS_CONFIGURATED', 'Y');
	$setDefault = true;
}

function checkOption(string $option_name, $option)
{
	/* Тут проверяем значение настроек, если есть ошибка, то возвращаем ее текст, иначе вернем true */
	if (($option_name == 'ATTR_SRC') && (trim($option) == '')) {
		return loc::getMessage('ISPRO_IMG2PICTURE_'.$option_name.'_ERROR');
	}
	return true;
}


foreach ($options_list as $option_name => $arOption) {
	if ($saveOption) {
		$option[$option_name] = $request->getpost('option_' . $option_name);
		$optionIsValid = checkOption($option_name, $option[$option_name]);
		if ($optionIsValid !== true) {
			$options_list_error[$option_name] = $optionIsValid;
			$eeror_message .= 'ERROR: ' . mb_substr(Loc::getMessage('ISPRO_IMG2PICTURE_' . $option_name), 0, 40) . PHP_EOL;
		};
		if ($option_name == 'RESPONSIVE') {
			foreach ($option[$option_name] as $key => $val) {
				if ((trim($val['width']) == '')) {
					unset($option[$option_name][$key]);
				};
			};
		};
		if (is_array($option[$option_name])) {
			$option[$option_name] = json_encode($option[$option_name]);
		};
	}
	if ($setDefault) {
		$option[$option_name] = $arOption['default'];
		$optionIsValid = true;
	};
	if (($saveOption || $setDefault) && ($optionIsValid === true)) {
		\Bitrix\Main\Config\Option::set($arModuleCfg['MODULE_ID'], $option_name, $option[$option_name]);
		if (!setDefault) {
			$ok_message .= 'SAVED: ' . mb_substr(Loc::getMessage('ISPRO_IMG2PICTURE_' . $option_name), 0, 40) . PHP_EOL;
		}
	};

	$option[$option_name] = \Bitrix\Main\Config\Option::get($arModuleCfg['MODULE_ID'], $option_name);
	if ($arOption['type'] == 'json') {
		$option[$option_name . '_VALUE'] = @json_decode($option[$option_name], true);
	};
};

if ($request->getpost('removefiles') != '') {
	Cimg2picture::ClearDirCache();
};

if ($ok_message != '') {
	$message = new \CAdminMessage(array(
		'MESSAGE' => $ok_message,
		'TYPE' => 'OK'
	));
	echo $message->Show();
}
if ($eeror_message != '') {
	$message = new \CAdminMessage(array(
		'MESSAGE' => $eeror_message,
		'TYPE' => 'ERROR'
	));
	echo $message->Show();
}

$i = 0;
while ($i < 5) {
	$option['RESPONSIVE_VALUE'][] = [
		'min' => '',
		'max' => '',
		'width' => ''
	];
	$i++;
}

$tabList = [
	[
		'DIV' => 'description',
		'TAB' => Loc::getMessage('ISPRO_IMG2PICTURE_TAB_SET_DESC'),
		'ICON' => 'ib_settings',
		'TITLE' => Loc::getMessage('ISPRO_IMG2PICTURE_TAB_TITLE_DESC')
	],
	[
		'DIV' => 'setting',
		'TAB' => Loc::getMessage('ISPRO_IMG2PICTURE_TAB_SET_OPTION'),
		'ICON' => 'ib_settings',
		'TITLE' => Loc::getMessage('ISPRO_IMG2PICTURE_TAB_TITLE_OPTION')
	],
];


$tabControl = new CAdminTabControl(str_replace('.', '_', $arModuleCfg['MODULE_ID']) . '_options', $tabList);
?>
<style>
	#img2picture_form textarea {
		width: 100%;
		min-height: 150px;
	}
</style>
<form method="POST" action="<?= $currentUrl; ?>" enctype="multipart/form-data" id="img2picture_form">
	<?= bitrix_sessid_post(); ?>
	<? $tabControl->Begin(); ?>

	<? $tabControl->BeginNextTab(); ?>
	<tr>
		<td colspan="2">
			<?= BeginNote(); ?>
			<?= Loc::getMessage('ISPRO_IMG2PICTURE_INFO'); ?>
			<?= EndNote(); ?>
		</td>
	</tr>

	<? $tabControl->BeginNextTab(); ?>

	<?foreach ($options_list as $option_name => $arOption) :?>
		<tr>
			<td width="20%" valign="top">
				<?= Loc::getMessage('ISPRO_IMG2PICTURE_'.$option_name) ?>
			</td>
			<td width="80%">
				<?if ($options_list_error[$option_name] != '') {
					$message = new \CAdminMessage(array(
						'MESSAGE' => $options_list_error[$option_name],
						'TYPE' => 'ERROR'
					));
					echo $message->Show();
				}?>
				<?if ($arOption['type'] == 'textarea') :?>
					<textarea name="option_<?=$option_name?>"><?= HtmlFilter::encode($option[$option_name]) ?></textarea>
				<?elseif ($arOption['type'] == 'checkbox') :?>
					<input type="hidden"  name="option_<?=$option_name?>" value="N" />
					<input type="checkbox" name="option_<?=$option_name?>" value="Y" <?= ($option[$option_name] == "Y") ? 'checked="checked"' : '' ?> />
				<?elseif ($arOption['type'] == 'select') :?>
					<select name="option_<?=$option_name?>">
					<? foreach ($arOption['values'] as $value) : ?>
						<option value="<?= $value ?>" <?= ($option[$option_name] == $value) ? 'selected' : '' ?>>
							<?= Loc::getMessage('ISPRO_IMG2PICTURE_'.$option_name.'_'.$value) ?>
						</option>
					<? endforeach ?>
					</select>
				<?elseif ($option_name == 'RESPONSIVE') :?>
					<table width="100%">
						<tr>
							<th>
								<?= Loc::getMessage('ISPRO_IMG2PICTURE_MIN_SCREEN_WIDTH') ?>
							</th>
							<th>
								<?= Loc::getMessage('ISPRO_IMG2PICTURE_MAX_SCREEN_WIDTH') ?>
							</th>
							<th>
								<?= Loc::getMessage('ISPRO_IMG2PICTURE_MAX_IMG_WIDTH') ?>
							</th>
						</tr>
						<? foreach ($option['RESPONSIVE_VALUE'] as $key => $val) : ?>
							<tr>
								<td>
									<input type="number" name="option_RESPONSIVE[<?= $key ?>][min]" value="<?= $val['min'] ?>">
								</td>
								<td>
									<input type="number" name="option_RESPONSIVE[<?= $key ?>][max]" value="<?= $val['max'] ?>">
								</td>
								<td>
									<input type="number" name="option_RESPONSIVE[<?= $key ?>][width]" value="<?= $val['width'] ?>">
								</td>
							</tr>
						<? endforeach ?>
					</table>
				<?else :?>
					<input type="<?=$arOption['type']?>" name="option_<?=$option_name?>" value="<?=HtmlFilter::encode($option[$option_name])?>" />
				<?endif?>

			</td>
		</tr>
	<?endforeach?>



	<tr>
		<td colspan="2">
			<input type="submit" class="adm-btn-save" name="save" value="<? echo Loc::getMessage('ISPRO_IMG2PICTURE_SAVE'); ?>">
			<input type="submit" class="adm-btn-save" name="reset" value="<? echo Loc::getMessage('ISPRO_IMG2PICTURE_DEFAULT'); ?>">
			<input type="submit" class="adm-btn-save" name="removefiles" value="<? echo Loc::getMessage('ISPRO_IMG2PICTURE_REMOVE_FILES'); ?>">

		</td>
	</tr>

	<? $tabControl->Buttons(); ?>
	<? $tabControl->End(); ?>
</form>