<?
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

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

foreach ($options_list as $option_name => $option_type) {
	if ($request->getpost('saveoptions') != '') {
		$option[$option_name] = $request->getpost('option_'.$option_name);
		if ($option_name == 'RESPONSIVE') {
			foreach ($option[$option_name] as $key=>$val) {
				if ((trim($val['width']) == '') || (trim($val['max']) == '') || (trim($val['min']) == '')) {
					unset( $option[$option_name][$key] );
				}
			}
		}
		if (is_array($option[$option_name])) {
			$option[$option_name] = json_encode($option[$option_name]);
		}
		\Bitrix\Main\Config\Option::set($arModuleCfg['MODULE_ID'], $option_name, $option[$option_name]);
		$message = new \CAdminMessage(array(
			'MESSAGE' => 'SAVED: '.$option_name. ' => '.$option[$option_name],
			'TYPE' => 'OK'
			));
		echo $message->Show();
	};
	$option[$option_name] = \Bitrix\Main\Config\Option::get($arModuleCfg['MODULE_ID'], $option_name);
	if ($option_type == 'json') {
		$option[$option_name.'_VALUE'] = @json_decode($option[$option_name], true);
	};
};

$option['RESPONSIVE_VALUE'][] = [
	'min' => '',
	'max' => '',
	'width' => ''
];

print_r_tree($option);
$tabList = [
	[
		'DIV' => 'edit1',
		'TAB' => Loc::getMessage('ISPRO_IMG2PICTURE_TAB_SET_OPTION'),
		'ICON' => 'ib_settings',
		'TITLE' => Loc::getMessage('ISPRO_IMG2PICTURE_TAB_TITLE_OPTION')
	],
];


$tabControl = new CAdminTabControl(str_replace('.', '_', $arModuleCfg['MODULE_ID']) . '_options', $tabList);
?>
<form method="POST" action="<?= $currentUrl; ?>"  enctype="multipart/form-data">
	<?= bitrix_sessid_post(); ?>
<?
$tabControl->Begin();
?>

	<?
	$tabControl->BeginNextTab();
	?>
		<tr>
			<td colspan="2">
				<?=BeginNote();?>
					<?= Loc::getMessage('ISPRO_IMG2PICTURE_INFO'); ?>
				<?=EndNote();?>
			</td>
		</tr

		<tr>
			<td style="text-align: left" width="20%"  valign="top">
				<?=Loc::getMessage('ISPRO_ING2PICTURE_RESPONSIVE')?>
			</td>
			<td width="80%">
				<table>
					<tr>
						<th>
							<?=Loc::getMessage('ISPRO_ING2PICTURE_MIN_SCREEN_WIDTH')?>
						</th>
						<th>
							<?=Loc::getMessage('ISPRO_ING2PICTURE_MAX_SCREEN_WIDTH')?>
						</th>						
						<th>
							<?=Loc::getMessage('ISPRO_ING2PICTURE_MAX_IMG_WIDTH')?>
						</th>
					</tr>
					<?foreach ($option['RESPONSIVE_VALUE'] as $key => $val):?>
						<tr>
							<td>
								<input type="numeric" name="option_RESPONSIVE[<?=key?>][min]" value="<?=$val['min']?>">
							</td>
							<td>
								<input type="numeric" name="option_RESPONSIVE[<?=key?>][max]" value="<?=$val['max']?>">
							</td>
							<td>
								<input type="numeric" name="option_RESPONSIVE[<?=key?>][width]" value="<?=$val['width']?>">
							</td>
						</tr>
					<?endforeach?>
				</table>
			</td>
		</tr>

		<tr>
			<td>
				<?=Loc::getMessage('ISPRO_ING2PICTURE_USE_WEBP')?>
			</td>
			<td>
				<input type="hidden" name="option_USE_WEBP" value="N" />
				<input type="checkbox" name="option_USE_WEBP" value="Y" <?=($option['USE_WEBP']=="Y")?'checked="checked"':''?> />
			</td>
		</tr>
		
		<tr>
			<td>
				<?=Loc::getMessage('ISPRO_ING2PICTURE_IMG_COMPRESSION')?>
			</td>
			<td>
				<input type="numeric" name="option_IMG_COMPRESSION" value="<?=$option['IMG_COMPRESSION']?>" min="0" max="100"/>
			</td>
		</tr>

		<tr>
			<td>
				<?=Loc::getMessage('ISPRO_ING2PICTURE_TEMPLATE')?>
			</td>
			<td>
				<textarea name="option_TEMPLATE"><?=$option['TEMPLATE']?></textarea>
			</td>
		</tr>

		<tr>
			<td>
				<?=Loc::getMessage('ISPRO_ING2PICTURE_EXCEPTIONS_DIR')?>
			</td>
			<td>
				<textarea name="option_EXCEPTIONS_DIR"><?=$option['EXCEPTIONS_DIR']?></textarea>
			</td>
		</tr>

		<tr>
			<td>
				<?=Loc::getMessage('ISPRO_ING2PICTURE_EXCEPTIONS_SRC')?>
			</td>
			<td>
				<textarea name="option_EXCEPTIONS_SRC"><?=$option['EXCEPTIONS_SRC']?></textarea>
			</td>
		</tr>

		<tr>
			<td>
				<?=Loc::getMessage('ISPRO_ING2PICTURE_MODULE_MODE')?>
			</td>
			<td>

			<select name="option_MODULE_MODE">
				<?$arModuleMode = ['off', 'test', 'on'];?>
				<?foreach ($arModuleMode as $mode):?>
					<option value="<?=$mode?>"><?=Loc::getMessage('ISPRO_ING2PICTURE_MODULE_MODE_'.$mode)?></option>
				<?endforeach?>
			</select>
			</td>
		</tr>

		<tr>
			<td colspan="2">
				<input type="submit" class="adm-btn-save" name="saveoptions" value="<? echo Loc::getMessage('ISPRO_FILESCLEANUP_OPTIONS_DELETE'); ?>">
			</td>
		</tr>

	<?$tabControl->Buttons();?>
<?$tabControl->End();?>
</form>