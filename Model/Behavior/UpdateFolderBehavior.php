<?php
/**
 * CabinetFolderBehavior
 *
 * @author   Ryuji AMANO <ryuji@ryus.co.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

/**
 * Class CabinetFolderBehavior
 */
class UpdateFolderBehavior extends ModelBehavior {

/**
 * 親フォルダデータを返す
 *
 * @param Model $model CabinetFile
 * @param array $cabinetFile cabinetFile data
 * @return array cabinetFile data
 */
	public function getParent(Model $model, $cabinetFile) {
		$conditions = [
			'UpdateFileTree.id' => $updateFile['UpdateFileTree']['parent_id'],
		];

		$parentUpdateFolder = $model->find('first', ['conditions' => $conditions]);
		return $parentUpdateFolder;
	}

/**
 * 子ノードがあるか
 *
 * @param Model $model CabinetFile
 * @param array $cabinetFile cabinetFile(folder)data
 * @return bool true:あり
 */
	public function hasChildren(Model $model, $updateFile) {
		// 自分自身が親IDとして登録されてるデータがあれば子ノードあり
		$conditions = [
			'UpdateFileTree.parent_id' => $updateFile['UpdateFileTree']['id'],
		];
		$conditions = $model->getWorkflowConditions($conditions);
		$count = $model->find('count', ['conditions' => $conditions]);
		return ($count > 0);
	}

/**
 * ルートフォルダを得る
 *
 * @param Model $model CabinetFile
 * @param array $cabinet Cabinetデータ
 * @return array|null
 */
	public function getRootFolder(Model $model, $update) {
		$this->log( 'UpdateFolderBehacior/getRootFolder', 'debug'); //debug
		$this->log( $update, 'debug'); //debug
		return $model->find('first', [
			'conditions' => $this->_getRootFolderConditions(
				$update,
				array(
					'OR' => array(
						'UpdateFile.language_id' => Current::read('Language.id'),
						'UpdateFile.is_translation' => false,
					)
				)
			)
		]);
	}

/**
 * キャビネットのルートフォルダとキャビネットの同期
 * ルートフォルダがなければ作成する
 *
 * @param Model $model CabinetFile
 * @param array $cabinet Cabinet model data
 * @return bool
 */
	public function syncRootFolder(Model $model, $update) {
		if ($this->rootFolderExist($model, $update)) {
			// ファイル名同期
			$options = [
				'conditions' => $this->_getRootFolderConditions(
					$update, ['UpdateFile.language_id' => Current::read('Language.id')]
				)
			];
			$rootFolder = $model->find('first', $options);
			if ($rootFolder['UpdateFile']['filename'] == $update['Update']['name']) {
				// ファイル名が同じならupdate不要
				return true;
			}
			$rootFolder['UpdateFile']['filename'] = $update['Update']['name'];
			$model->Behaviors->disable('Topics');
			$model->useNameValidation = false;
			$result = ($model->save($rootFolder)) ? true : false;
			$model->useNameValidation = true;
			$model->Behaviors->enable('Topics');
			return $result;
		} else {
			return $model->makeRootFolder($update);
		}
	}

/**
 * Cabinetのルートフォルダを作成する
 *
 * @param Model $model CabinetFile
 * @param array $cabinet Cabinetモデルデータ
 * @return bool
 */
	public function makeRootFolder(Model $model, $update) {
		if ($this->rootFolderExist($model, $update)) {
			return true;
		}
		$model->loadModels([
			'UpdateFileTree' => 'Updates.UpdateFileTree',
		]);

		// $modelのTopicビヘイビアを停止
		$model->Behaviors->disable('Topics');
		$model->create();
		$model->useNameValidation = false;

		$rootFolderTree = $model->UpdateFileTree->find('first', array(
			'recursive' => -1,
			'conditions' => $this->_getRootFolderConditions($update),
		));

		$rootFolder = Hash::merge([
			'UpdateFileTree' => [
				'update_key' => $update['Update']['key'],
			],
			'UpdateFile' => [
				'update_key' => $update['Update']['key'],
				'status' => WorkflowComponent::STATUS_PUBLISHED,
				'filename' => $update['Update']['name'],
				'is_folder' => 1,
				'key' => Hash::get($rootFolderTree, 'UpdateFileTree.update_file_key'),
			]
		], $rootFolderTree);

		$result = $model->save($rootFolder);
		if ($rootFolder) {
			$result = (bool)$result;
			$model->useNameValidation = true;
		} else {
			$result = false;
		}
		// $modelのTopicビヘイビアを復帰
		$model->Behaviors->enable('Topics');
		return $result;
	}

/**
 * Cabinetのルートフォルダが存在するか
 *
 * @param Model $model CabinetFile
 * @param array $cabinet Cabinetデータ
 * @return bool true:存在する false:存在しない
 */
	public function rootFolderExist(Model $model, $cabinet) {
		// ルートフォルダが既に存在するかを探す
		$conditions = $this->_getRootFolderConditions(
			$update, ['UpdateFile.language_id' => Current::read('Language.id')]
		);
		$count = $model->find('count', ['conditions' => $conditions]);
		return ($count > 0);
	}

/**
 * フォルダの合計サイズを得る
 *
 * @param Model $model CabinetFile
 * @param array $folder CabinetFileデータ
 * @return int 合計サイズ
 */
	public function getUpdateSizeByFolder(Model $model, $folder) {
		// ベタパターン
		// 配下全てのファイルを取得する
		//$this->CabinetFileTree->setup(]);
		$cabinetKey = $folder['Cabinet']['key'];
		$conditions = [
			'UpdateFileTree.cabinet_key' => $cabinetKey,
			'UpdateFileTree.lft >' => $folder['UpdateFileTree']['lft'],
			'UpdateFileTree.rght <' => $folder['UpdateFileTree']['rght'],
			'UpdateFile.is_folder' => false,
		];
		$files = $model->find('all', ['conditions' => $conditions]);
		$update = 0;
		foreach ($files as $file) {
			$update += Hash::get($file, 'UploadFile.file.size', 0);
		}
		return $update;
		// sumパターンはUploadFileの構造をしらないと厳しい… がんばってsumするより合計サイズをキャッシュした方がいいかも
	}

/**
 * ルートフォルダ（＝キャビネット）をFindするためのconditionsを返す
 *
 * @param array $cabinet Cabinetデータ
 * @param array $addConditions 条件
 * @return array conditions
 */
	protected function _getRootFolderConditions($update, $addConditions = array()) {
		$conditions = Hash::merge([
			'UpdateFileTree.update_key' => $update['Update']['key'],
			'UpdateFileTree.parent_id' => null,
		], $addConditions);

		return $conditions;
	}

/**
 * Cabinet.update_sizeに容量をキャッシュする
 *
 * @param Model $model モデル
 * @param int $cabinetKey キャビネットKEY
 * @return void
 * @throws InternalErrorException
 */
	public function updateUpdateUpdateSize(Model $model, $updateKey) {
		$model->loadModels([
			'Update' => 'Updates.Update',
		]);
		$update = $model->Update->find('first', array(
			'recursive' => -1,
			'conditions' => array('key' => $updateKey),
		));

		// トータルサイズ取得
		$rootFolder = $model->getRootFolder($update);
		$updateSize = $model->getUpdateSizeByFolder(
			$rootFolder
		);
		// キャビネット更新
		$update = array(
			'Update.update_size' => $updateSize
		);
		$conditions = array(
			'Update.block_id' => $update['Update']['block_id']
		);
		if (! $model->Update->updateAll($update, $conditions)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}
		//$cabinet['Cabinet']['update_size'] = $updateSize;
		//$model->Cabinet->save($cabinet, ['callbacks' => false]);
		//$model->Cabinet->id = $cabinetId;
		//$model->Cabinet->saveField('update_size', $updateSize, ['callbacks' => false]);
	}
}