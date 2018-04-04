<?php
/**
 * CabinetFolderBehavior
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

/**
 * Class CabinetFolderBehavior
 *
 * @package NetCommons\Cabinets\Model\Behavior
 */
class UpdateFileBehavior extends ModelBehavior {

/**
 * ファイル名検査
 *
 * @param Model $model モデル
 * @param array $check 検査対象
 * @return bool
 */
	public function validateFilename(Model $model, $check) {
		$filename = $check['filename'];
		if ($model->data[$model->alias]['is_folder']) {
			return !preg_match('/[' . preg_quote('\'./?|:\<>\*"', '/') . ']/', $filename);
		} else {
			return !preg_match('/[' . preg_quote('\'/?|:\<>\*"', '/') . ']/', $filename);
		}
	}

/**
 * ファイル編集時のファイル名チェック
 *
 * @param Model $model モデル
 * @param array $check 検査対象
 * @return bool
 */
	public function validateWithOutExtFileName(Model $model, $check) {
		if ($model->data[$model->alias]['is_folder']) {
			return true;
		}
		// ファイルの編集時だけ拡張子抜きのファイル名が空でないかチェックする
		if ($model->data[$model->alias]['key']) {
			$withOutExtFileName = $model->data[$model->alias]['withOutExtFileName'];
			return (strlen($withOutExtFileName) > 0);
		}
		return true;
	}
}