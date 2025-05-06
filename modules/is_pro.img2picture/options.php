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
}

if (!Loader::includeModule($arModuleCfg['MODULE_ID'])) {
	return;
}

Loc::loadMessages(__FILE__);

// получить массив сайтов [lid => name, ...]
$res     = \Bitrix\Main\SiteTable::getList();
$siteIds = [];
while ($site = $res->fetch()) {
	$siteIds[$site["LID"]] = $site["NAME"];
}

$currentUrl = $APPLICATION->GetCurPage() . '?mid=' . urlencode($mid) . '&amp;lang=' . LANGUAGE_ID;
$request    = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$doc_root   = \Bitrix\Main\Application::getDocumentRoot();
$url_module = str_replace($doc_root, '', __DIR__);

$options_list = $arModuleCfg['options_list'];
foreach ($options_list as $option_name => $arOption) {
	if (!isset($options_list[$option_name]['default'])) {
		$options_list[$option_name]['default'] = '';
	}
}

$ok_message    = '';
$error_message = '';

function checkOption(string $option_name, $option)
{
	/* Тут проверяем значение настроек, если есть ошибка, то возвращаем ее текст, иначе вернем true */
	if (($option_name == 'ATTR_SRC') && (trim($option) == '')) {
		return loc::getMessage('ISPRO_IMG2PICTURE_' . $option_name . '_ERROR');
	}
	return true;
}

$options_list_error = [];

if (check_bitrix_sessid()) {
	$save = $request->getpost('save');
	if ($save == 'save') {
		$saveOption = true;
	}
}

foreach ($siteIds as $sId => $sName) {

	$setDefault = false;

	$isConfigurated =
		\Bitrix\Main\Config\Option::get($arModuleCfg['MODULE_ID'], 'IS_CONFIGURATED', 'N', $sId);
	if ($isConfigurated != 'Y') {
		\Bitrix\Main\Config\Option::set($arModuleCfg['MODULE_ID'], 'IS_CONFIGURATED', 'Y', $sId);
		$setDefault = true;
	}

	if (check_bitrix_sessid()) {
		if ($save == 'reset_' . $sId) {
			$setDefault = true;
		}
	}

	foreach ($options_list as $option_name => $arOption) {
		$option_name_def = $option_name;
		$option_name     = $option_name . '_' . $sId;
		$optionIsValid   = false;
		if ($saveOption) {
			$option[$option_name] = $request->getpost('option_' . $option_name);
			$optionIsValid        = checkOption($option_name_def, $option[$option_name]);
			if ($optionIsValid !== true) {
				$options_list_error[$option_name] = $optionIsValid;
				$error_message .= 'ERROR: ' . mb_substr(Loc::getMessage('ISPRO_IMG2PICTURE_' . $option_name_def), 0, 40) . PHP_EOL;
			}
			if ($option_name_def == 'RESPONSIVE') {
				foreach ($option[$option_name] as $key => $val) {
					if ((trim($val['width']) == '')) {
						unset($option[$option_name][$key]);
					}
				}
			}
			if (is_array($option[$option_name])) {
				$option[$option_name] = json_encode($option[$option_name]);
			}
		}
		if ($setDefault) {
			$option[$option_name] = $arOption['default'];
			$optionIsValid        = true;
		}
		if (($saveOption || $setDefault) && ($optionIsValid === true)) {
			\Bitrix\Main\Config\Option::set($arModuleCfg['MODULE_ID'], $option_name_def, $option[$option_name], $sId);
			$ok_message .= 'SAVED: ' . Loc::getMessage('ISPRO_IMG2PICTURE_' . $option_name_def) . ' ' . $option_name . '= ' . $option[$option_name] . PHP_EOL;
		}

		$option[$option_name] = \Bitrix\Main\Config\Option::get($arModuleCfg['MODULE_ID'], $option_name_def, $arOption['default'], $sId);
		if ($arOption['type'] == 'json') {
			$option[$option_name . '_VALUE'] = @json_decode($option[$option_name], true);
		}
	}
}

if ($save == 'removefiles') {
	Cimg2picture::ClearDirCache();
}

if (($error_message == '') && ($ok_message != '')) {
	$ok_message = 'Saved';
}

if ($ok_message != '') {
	$message = new \CAdminMessage(array(
		'MESSAGE' => $ok_message,
		'TYPE'    => 'OK'
	));
	echo $message->Show();
}

if ($error_message != '') {
	$message = new \CAdminMessage(array(
		'MESSAGE' => $error_message,
		'TYPE'    => 'ERROR'
	));
	echo $message->Show();
}

$tabList   = [];
$tabList[] = [
	'DIV'   => 'description',
	'TAB'   => Loc::getMessage('ISPRO_IMG2PICTURE_TAB_SET_DESC'),
	'ICON'  => 'ib_settings',
	'TITLE' => Loc::getMessage('ISPRO_IMG2PICTURE_TAB_TITLE_DESC')
];
/*
$tabList[] = [
	'DIV' => 'description',
	'TAB' => Loc::getMessage('ISPRO_IMG2PICTURE_TAB_SET_IMGCONVERT'),
	'ICON' => 'ib_settings',
	'TITLE' => Loc::getMessage('ISPRO_IMG2PICTURE_TAB_TITLE_IMGCONVERT')
];
*/
foreach ($siteIds as $sId => $sName) {
	$tabList[] = [
		'DIV'   => 'setting' . $sId,
		'TAB'   => Loc::getMessage('ISPRO_IMG2PICTURE_TAB_SET_OPTION') . ' (' . $sName . ')',
		'ICON'  => 'ib_settings',
		'TITLE' => Loc::getMessage('ISPRO_IMG2PICTURE_TAB_TITLE_OPTION') . ' (' . $sName . ')'
	];
}


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
	<? /*
	 <? $tabControl->BeginNextTab(); ?>
	 <tr>
		 <td colspan="2">
			 <?= BeginNote(); ?>
				 Тут будет сжатиеоригиналов
			 <?= EndNote(); ?>
		 </td>
	 </tr>
 */ ?>
	<? foreach ($siteIds as $sId => $sName): ?>

		<? $tabControl->BeginNextTab(); ?>

		<? foreach ($options_list as $option_name => $arOption): ?>
			<? $option_name_def = $option_name; ?>
			<? $option_name     = $option_name . '_' . $sId; ?>
			<tr>
				<td width="20%" valign="top">
					<?= Loc::getMessage('ISPRO_IMG2PICTURE_' . $option_name_def) ?>
				</td>
				<td width="80%">
					<? if ($options_list_error[$option_name] != '') {
						$message = new \CAdminMessage(array(
							'MESSAGE' => $options_list_error[$option_name],
							'TYPE'    => 'ERROR'
						));
						echo $message->Show();
					} ?>
					<? if ($arOption['type'] == 'textarea'): ?>
						<textarea name="option_<?= $option_name ?>"><?= HtmlFilter::encode($option[$option_name]) ?></textarea>
					<? elseif ($arOption['type'] == 'checkbox'): ?>
						<input type="hidden" name="option_<?= $option_name ?>" value="N" />
						<input type="checkbox" name="option_<?= $option_name ?>" value="Y" <?= ($option[$option_name] == "Y") ? 'checked="checked"' : '' ?> />
					<? elseif ($arOption['type'] == 'select'): ?>
						<select name="option_<?= $option_name ?>">
							<? foreach ($arOption['values'] as $value): ?>
								<option value="<?= $value ?>" <?= ($option[$option_name] == $value) ? 'selected' : '' ?>>
									<?= Loc::getMessage('ISPRO_IMG2PICTURE_' . $option_name_def . '_' . $value) ?>
								</option>
							<? endforeach ?>
						</select>
					<? elseif ($option_name_def == 'RESPONSIVE'): ?>
						<?
						$i = 0;
						while ($i < 5) {
							$option[$option_name . '_VALUE'][] = [
								'min'   => '',
								'max'   => '',
								'width' => ''
							];
							$i++;
						}
						?>
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
							<? foreach ($option[$option_name . '_VALUE'] as $key => $val): ?>
								<tr>
									<td>
										<input type="number" name="option_<?= $option_name ?>[<?= $key ?>][min]"
											value="<?= $val['min'] ?>">
									</td>
									<td>
										<input type="number" name="option_<?= $option_name ?>[<?= $key ?>][max]"
											value="<?= $val['max'] ?>">
									</td>
									<td>
										<input type="number" name="option_<?= $option_name ?>[<?= $key ?>][width]"
											value="<?= $val['width'] ?>">
									</td>
								</tr>
							<? endforeach ?>
						</table>
					<? else: ?>
						<input type="<?= $arOption['type'] ?>" name="option_<?= $option_name ?>"
							value="<?= HtmlFilter::encode($option[$option_name]) ?>" />
					<? endif ?>

				</td>
			</tr>
		<? endforeach ?>
		<tr>
			<td>
				<?= Loc::getMessage('ISPRO_IMG2PICTURE_DEFAULT'); ?>
			</td>
			<td>
				<button type="submit" class="adm-btn" name="save"
					value="reset_<?= $sId ?>"><?= Loc::getMessage('ISPRO_IMG2PICTURE_DEFAULT'); ?> (<?= $sName ?>)</button>
			</td>
		</tr>
	<? endforeach ?>

	<? $tabControl->Buttons(); ?>

	<button type="submit" class="adm-btn adm-btn-save" name="save"
		value="save"><? echo Loc::getMessage('ISPRO_IMG2PICTURE_SAVE'); ?></button>
	<button type="submit" class="adm-btn adm-btn-save" name="save"
		value="removefiles"><? echo Loc::getMessage('ISPRO_IMG2PICTURE_REMOVE_FILES'); ?></button>

	<? $tabControl->End(); ?>
</form>