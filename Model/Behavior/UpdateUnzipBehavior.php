<?php
/**
 * CabinetUnzipBehavior
 *
 * @author   Ryuji AMANO <ryuji@ryus.co.jp>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 */

/**
 * Class CabinetUnzipBehavior
 */
class UpdateUnzipBehavior extends ModelBehavior {

/**
 * キャビネットファイルのUnzip
 *
 * @param Model $model CabinetFile
 * @param array $cabinetFile CabinetFileデータ
 * @return bool
 * @throws InternalErrorException
 */
	public function unzip(Model $model, $updateFile) {
		$model->begin();
		try {
			// テンポラリフォルダにunzip
			$zipPath = WWW_ROOT . $updateFile['UploadFile']['file']['path'] .
				$updateFile['UploadFile']['file']['id'] . DS .
				$updateFile['UploadFile']['file']['real_file_name'];
			//debug($zipPath);
			App::uses('UnZip', 'Files.Utility');
			$unzip = new UnZip($zipPath);
			$tmpFolder = $unzip->extract();
			if ($tmpFolder === false) {
				throw new InternalErrorException('UnZip Failed.');
			}

			$parentUpdateFolder = $model->find(
				'first',
				['conditions' => ['UpdateFileTree.id' => $updateFile['UpdateFileTree']['parent_id']]]
			);

			// unzipされたファイル拡張子のバリデーション
			// unzipされたファイルのファイルサイズバリデーション
			$files = $tmpFolder->findRecursive();
			$unzipUpdateSize = 0;
			foreach ($files as $file) {
				//
				$unzipUpdateSize += filesize($file);

				// ここでは拡張子だけチェックする
				$extension = pathinfo($file, PATHINFO_EXTENSION);
				if (!$model->isAllowUploadFileExtension($extension)) {
					// NG
					$model->validationErrors = [
						__d('cabinets', 'Unzip failed. Contains does not allow file format.')
					];
					return false;
				}
			}
			// ルームファイルサイズ制限
			$maxRoomDiskSize = Current::read('Space.room_disk_size');
			if ($maxRoomDiskSize !== null) {
				// nullだったらディスクサイズ制限なし。null以外ならディスクサイズ制限あり
				// 解凍後の合計
				// 現在のルームファイルサイズ
				$roomId = Current::read('Room.id');
				$roomFileSize = $model->getUpdateSizeByRoomId($roomId);
				if (($roomFileSize + $unzipUpdateSize) > $maxRoomDiskSize) {

					$model->validationErrors[] = __d(
						'cabinets',
						'Failed to expand. The update size exceeds the limit.<br />' .
						'The update size limit is %s (%s left).',
						CakeNumber::toReadableSize($roomFileSize + $unzipUpdateSize),
						CakeNumber::toReadableSize($maxRoomDiskSize)
					);
					return false;
				}
			}

			// 再帰ループで登録処理
			list($folders, $files) = $tmpFolder->read(true, false, true);
			foreach ($files as $file) {
				$this->_addFileFromPath($model, $parentUpdateFolder, $file);
			}
			foreach ($folders as $folder) {
				$this->_addFolderFromPath($model, $parentUpdateFolder, $folder);
			}
		} catch (Exception $e) {
			return $model->rollback($e);
		}
		$model->commit();
		return true;
	}

/**
 * フォルダパスにある実フォルダをキャビネットに登録する
 *
 * @param Model $model Model
 * @param array $parentCabinetFolder 登録する親フォルダ
 * @param string $folderPath 実フォルダのパス
 * @throws InternalErrorException
 * @return void
 */
	protected function _addFolderFromPath(Model $model, $parentUpdateFolder, $folderPath) {
		$newFolder = [
			'UpdateFile' => [
				'update_key' => $parentUpdateFolder['UpdateFile']['update_key'],
				'is_folder' => true,
				'filename' => $model->basename($folderPath),
				'status' => WorkflowComponent::STATUS_PUBLISHED,
			],
			'UpdateFileTree' => [
				'parent_id' => $parentUpdateFolder['UpdateFileTree']['id'],
				'update_key' => $parentUpdateFolder['UpdateFileTree']['update_key'],
			],
		];
		$newFolder = $model->create($newFolder);

		if (!$savedFolder = $model->saveFile($newFolder)) {
			throw new InternalErrorException('Save Failed');
		}
		//// folder配下のread
		$thisFolder = new Folder($folderPath);
		list($folders, $files) = $thisFolder->read(true, false, true);
		// 配下のファイル登録
		foreach ($files as $childFilePath) {
			$this->_addFileFromPath($model, $savedFolder, $childFilePath);
		}
		// 配下のフォルダ登録
		foreach ($folders as $childFolderPath) {
			$this->_addFolderFromPath($model, $savedFolder, $childFolderPath);
		}
	}

/**
 * ファイルパスにある実ファイルをキャビネットに登録する
 *
 * @param Model $model Model
 * @param array $parentCabinetFolder 登録する親フォルダ
 * @param string $filePath 実ファイルのパス
 * @throws InternalErrorException
 * @return void
 */
	protected function _addFileFromPath(Model $model, $parentUpdateFolder, $filePath) {
		$newFile = $this->_makeUpdateFileDataFromPath($model, $parentUpdateFolder, $filePath);

		if (!$model->saveFile($newFile)) {
			throw new InternalErrorException('Save Failed');
		}
	}

/**
 * CabinetFileデータをファイルパスから作成する
 *
 * @param Model $model Model
 * @param array $parentCabinetFolder 親フォルダデータ
 * @param string $filePath ファイルパス
 * @return array フォームからポストされる形のCabinetFileデータ
 */
	protected function _makeUpdateFileDataFromPath(Model $model, $parentUpdateFolder,
		$filePath) {
		//MIMEタイプの取得
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mimeType = $finfo->file($filePath);

		$newFile = [
			'UpdateFile' => [
				'update_key' => $parentUpdateFolder['UpdateFile']['update_key'],
				'is_folder' => false,
				'filename' => $model->basename($filePath),
				'status' => WorkflowComponent::STATUS_PUBLISHED,
				'file' => [
					'name' => $model->basename($filePath),
					'type' => $mimeType,
					'tmp_name' => $filePath,
					'error' => 0,
					'size' => filesize($filePath),
				],
			],
			'UpdateFileTree' => [
				'parent_id' => $parentUpdateFolder['UpdateFileTree']['id'],
				'update_key' => $parentUpdateFolder['UpdateFileTree']['update_key'],
			],
		];
		$newFile = $model->create($newFile);
		return $newFile;
	}
}
