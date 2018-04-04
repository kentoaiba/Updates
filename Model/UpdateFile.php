<?php
/**
 * CabinetFile Model
 *
 * @author Ryuji AMANO <ryuji@ryus.co.jp>
 * @link     http://www.netcommons.org NetCommons Project
 * @license  http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('UpdatesAppModel', 'Updates.Model');
App::uses('NetCommonsTime', 'NetCommons.Utility');
App::uses('Current', 'NetCommons.Utility');

/**
 * Summary for CabinetFile Model
 */
class UpdateFile extends UpdatesAppModel {

/**
 * @var int recursiveはデフォルトアソシエーションなしに
 */
	public $recursive = 0;

/**
 * RootFolder作成時はfalseにセットしてfilenameを自由につけられるようにする
 *
 * @var bool
 */
	public $useNameValidation = true;

/**
 * use behaviors
 *
 * @var array
 */
	public $actsAs = array(
		'NetCommons.Trackable',
		'NetCommons.OriginalKey',
		'Workflow.Workflow',
		'Workflow.WorkflowComment',
		'Updates.UpdateFile',
		'Updates.UpdateFolder',
		'Updates.UpdateUnzip',
		'Files.Attachment' => [
			'file' => [
				//'thumbnails' => false,
			]
		],
		'AuthorizationKeys.AuthorizationKey',
		'Topics.Topics' => array(
			'fields' => array(
				'title' => 'filename',
				'summary' => 'description',
				'path' => '/:plugin_key/update_files/view/:block_id/:content_key',
			),
		),
		// 自動でメールキューの登録, 削除。ワークフロー利用時はWorkflow.Workflowより下に記述する
		'Mails.MailQueue' => array(
			'embedTags' => array(
				'X-SUBJECT' => 'UpdateFile.filename',
				'X-BODY' => 'UpdateFile.description',
			),
		),
		//多言語
		'M17n.M17n' => array(
			'commonFields' => array(
				'update_file_tree_parent_id',
				'update_file_tree_id',
				'is_folder',
				'use_auth_key',
			),
			'associations' => array(
				'UploadFilesContent' => array(
					'class' => 'Files.UploadFilesContent',
					'foreignKey' => 'content_id',
					'isM17n' => true
				),
				'AuthorizationKey' => array(
					'class' => 'AuthorizationKeys.AuthorizationKey',
					'foreignKey' => 'content_id',
					'fieldForIdentifyPlugin' => array('field' => 'model', 'value' => 'UpdateFile'),
					'isM17n' => false
				),
			),
			'afterCallback' => false,
		),
	);

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'UpdateFileTree' => array(
			'type' => 'LEFT',
			'className' => 'Updates.UpdateFileTree',
			'foreignKey' => 'update_file_tree_id',
			//'conditions' => 'CabinetFileTree.update_file_key=CabinetFile.key',
			//'conditions' => 'CabinetFileTree.update_file_id=CabinetFile.id',
			'fields' => '',
			'order' => ''
		),
	);

/**
 * beforeValidate
 *
 * @param array $options Options
 * @return bool
 */
	public function beforeValidate($options = array()) {
		$validate = array(
			'filename' => array(
				'notBlank' => [
					'rule' => array('notBlank'),
					'message' => sprintf(
						__d('net_commons', 'Please input %s.'),
						__d('cabinets', 'Filename')
					),
					//'allowEmpty' => false,
					'required' => true,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
				],
			),
			'withOutExtFileName' => [
				'rule' => ['validateWithOutExtFileName'],
				'message' => sprintf(
					__d('net_commons', 'Please input %s.'),
					__d('cabinets', 'Filename')
				),
			],
			'status' => array(
				'numeric' => array(
					'rule' => array('numeric'),
					//'message' => 'Your custom message here',
					//'allowEmpty' => false,
					//'required' => false,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
				),
			),
			'is_auto_translated' => array(
				'boolean' => array(
					'rule' => array('boolean'),
					//'message' => 'Your custom message here',
					//'allowEmpty' => false,
					//'required' => false,
					//'last' => false, // Stop validation after this rule
					//'on' => 'create', // Limit validation to 'create' or 'update' operations
				),
			),
		);
		if ($this->useNameValidation) {
			$validate['filename']['filename'] = [
				'rule' => ['validateFilename'],
				'message' => __d('cabinets', 'Invalid character for file/folder name.'),
			];
		}

		$this->validate = Hash::merge($this->validate, $validate);

		return parent::beforeValidate($options);
	}

/**
 * Called before each find operation. Return false if you want to halt the find
 * call, otherwise return the (modified) query data.
 *
 * @param array $query Data used to execute this query, i.e. conditions, order, etc.
 * @return mixed true if the operation should continue, false if it should abort; or, modified
 *  $query to continue with new $query
 * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#beforefind
 */
	public function beforeFind($query) {
		if (Hash::get($query, 'recursive', $this->recursive) > -1) {
			$belongsTo = array(
				'belongsTo' => array(
					'Update' => array(
						'className' => 'Updates.Update',
						'foreignKey' => false,
						'conditions' => array(
							'UpdateFile.update_key = Update.key',
							'OR' => array(
								'Update.is_translation' => false,
								'Update.language_id' => Current::read('Language.id', '0'),
							),
						),
						'order' => ''
					),
				)
			);

			$this->bindModel($belongsTo, true);
		}
		return true;
	}

/**
 * Called before each save operation, after validation. Return a non-true result
 * to halt the save.
 *
 * @param array $options Options passed from Model::save().
 * @return bool True if the operation should continue, false if it should abort
 * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#beforesave
 * @see Model::save()
 * @throws InternalErrorException
 */
	public function beforeSave($options = array()) {
		if (isset($this->data['UpdateFileTree'])) {
			// treeはファイルなら常に新規INSERT フォルダだったらアップデート
			if ($this->data['UpdateFile']['is_folder']) {
				// フォルダは treeをupdate
				//if(isset($data['CabinetFileTree']['id']) === false){
				//	$data['CabinetFileTree']['id'] = null;
				//}
			} else {
				// ファイルは treeを常にinsert
				$this->data['UpdateFileTree']['id'] = null;
			}

			$this->UpdateFileTree->create();
			$treeData = $this->UpdateFileTree->save($this->data['UpdateFileTree']);
			if (! $treeData) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}
			$this->data['UpdateFileTree'] = $treeData['UpdateFileTree'];
			$this->data[$this->alias]['update_file_tree_id'] = $this->data['UpdateFileTree']['id'];
		}

		return parent::beforeSave($options);
	}

/**
 * Called after each successful save operation.
 *
 * @param bool $created True if this save created a new record
 * @param array $options Options passed from Model::save().
 * @return void
 * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#aftersave
 * @see Model::save()
 * @throws InternalErrorException
 */
	public function afterSave($created, $options = array()) {
		if (isset($this->data['UpdateFileTree'])) {
			$update = array(
				'UpdateFileTree.update_file_key' => '\'' . $this->data[$this->alias]['key'] . '\'',
			);
			$conditions = array(
				'UpdateFileTree.id' => $this->data['UpdateFileTree']['id']
			);
			if (! $this->UpdateFileTree->updateAll($update, $conditions)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}
			$this->data['UpdateFileTree']['update_file_key'] = $this->data[$this->alias]['key'];
		}

		parent::afterSave($created, $options);
	}

/**
 * modifiedを常に更新
 *
 * @param null $data 登録データ
 * @param bool $validate バリデートを実行するか
 * @param array $fieldList フィールド
 * @return mixed
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
	public function save($data = null, $validate = true, $fieldList = array()) {
		// 保存前に modified フィールドをクリアする
		$this->set($data);
		$isNoUnsetModified = Hash::get($this->data, $this->alias . '._is_no_unset_modified');
		if (isset($this->data[$this->alias]['modified']) && !$isNoUnsetModified) {
			unset($this->data[$this->alias]['modified']);
		}
		return parent::save($this->data, $validate, $fieldList);
	}

/**
 * save ファイル
 *
 * @param array $data CabinetFileデータ
 * @return bool|mixed
 * @throws InternalErrorException
 */
	public function saveFile($data) {
		$this->begin();
		$this->_autoRename($data);
		try {
			// 常に新規登録
			$this->create();
			unset($data[$this->alias]['id']);

			$data['UpdateFile']['update_file_tree_parent_id'] = $data['UpdateFileTree']['parent_id'];

			// 先にvalidate 失敗したらfalse返す
			$this->set($data);
			// if (!$this->validates($data)) { ★★
			// 	$this->rollback();
			// 	return false;
			// }
			if ($data['UpdateFile']['is_folder']) {
				// Folderは新着にのせたくないのでTopicディセーブル
				$this->Behaviors->disable('Topics');
			}
			if (($savedData = $this->save($data, false)) === false) {
				//このsaveで失敗するならvalidate以外なので例外なげる
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}
			$this->Behaviors->enable('Topics');

			// Cabinet.update_size同期
			$this->updateUpdateUpdateSize($data['UpdateFile']['update_key']);

			//多言語化の処理
			$this->set($savedData);
			$this->saveM17nData();

			$this->commit();
			return $savedData;

		} catch (Exception $e) {
			$this->rollback($e);
		}
	}

/**
 * ファイル削除
 *
 * @param string $key CabinetFile.key
 * @return bool
 * @throws Exception
 * @throws null
 */
	public function deleteFileByKey($key) {
		$this->begin();
		try {
			$deleteFile = $this->find('first', array(
				'recursive' => 0,
				'conditions' => array(
					'UpdateFile.key' => $key
				)
			));

			if ($deleteFile['UpdateFile']['is_folder']) {
				$this->_deleteFolder($deleteFile);
			} else {
				$this->_deleteFile($deleteFile);
			}
			$this->updateUpdateUpdateSize($deleteFile['UpdateFile']['updatekey']);

			$this->commit();
			return;
		} catch (Exception $e) {
			$this->rollback($e);
			throw $e;
		}
	}

/**
 * ファイル削除処理
 *
 * @param array $updateFile CabinetFile データ ファイル
 * @throws InternalErrorException
 * @return bool
 */
	protected function _deleteFile($updateFile) {
		//コメントの削除
		$this->deleteCommentsByContentKey($updateFile['UpdateFile']['key']);

		// CabinetFileTreeも削除
		$conditions = [
			'update_file_key' => $updateFile['UpdateFile']['key'],
		];
		if (! $this->UpdateFileTree->deleteAll($conditions, true, true)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}

		$conditions = array('UpdateFile.key' => $updateFile['UpdateFile']['key']);
		if (! $this->deleteAll($conditions, true, true)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}
		return true;
	}

/**
 * フォルダ削除処理
 *
 * @param array $updateFile CabinetFileデータ フォルダ
 * @throws InternalErrorException
 * @return bool
 */
	protected function _deleteFolder($updateFile) {
		$key = $updateFile['UpdateFile']['key'];

		// 子ノードを全て取得
		$children = $this->UpdateFileTree->children(
			$updateFile['UpdateFileTree']['id'],
			false,
			null,
			null,
			null,
			1,
			0
		);

		// CabinetFileTreeも削除 Treeビヘイビアにより子ノードのTreeデータは自動的に削除される
		$conditions = [
			'update_file_key' => $key,
		];
		if (!$this->UpdateFileTree->deleteAll($conditions, true, true)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}

		if ($children) {
			foreach ($children as $child) {
				if ($child['UpdateFile']['is_folder']) {
					// folder delete
					$conditions = array('UpdateFile.key' => $child['UpdateFile']['key']);
					if (!$this->deleteAll($conditions)) {
						throw new InternalErrorException(
							__d('net_commons', 'Internal Server Error')
						);
					}
				} else {
					if ($child['UpdateFile']['is_latest']) {
						$conditions = array('UpdateFile.key' => $child['UpdateFile']['key']);
						if (!$this->deleteAll($conditions, true, true)) {
							throw new InternalErrorException(
								__d('net_commons', 'Internal Server Error')
							);
						}
					} else {
						// is_latestでなければ履歴データとしてCabinetFileは残してTreeだけ削除（ツリービヘイビアが勝手にけしてくれる）
					}
				}
			}
		}
		$conditions = array('UpdateFile.key' => $key);
		if ($this->deleteAll($conditions, true, true)) {
			return true;
		} else {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}
	}

/**
 * php builtinのbasenameがlocale依存なので自前で
 *
 * @param string $filePath ファイルパス
 * @return string basename
 */
	public function basename($filePath) {
		// Win pathを / 区切りに変換しちゃう
		$filePath = str_replace('\\', '/', $filePath);
		$separatedPath = explode('/', $filePath);
		// 最後を取り出す
		$basenaem = array_pop($separatedPath);
		return $basenaem;
	}

/**
 * 拡張子抜きのファイル名と拡張子にわける
 *
 * @param string $fileName ファイル名
 * @return array [ファイル名,拡張子]
 */
	public function splitFileName($fileName) {
		// .あるか
		if (strpos($fileName, '.')) {
			// .あり
			$splitFileName = explode('.', $fileName);
			$extension = array_pop($splitFileName); // 最後の.以降が拡張子
			$withOutExtFilename = implode('.', $splitFileName);
			$ret = [
				$withOutExtFilename,
				$extension
			];
		} else {
			// .なし
			$ret = [
				$fileName,
				null
			];
		}
		return $ret;
	}

/**
 * 同一フォルダに同じ名前のファイル・フォルダがあるか
 *
 * @param array $updateFile CabinetFile データ
 * @return bool
 */
	protected function _existSameFilename($updateFile) {
		$conditions = [
			'UpdateFile.key !=' => $updateFile['UpdateFile']['key'],
			'UpdateFileTree.parent_id' => $updateFile['UpdateFileTree']['parent_id'],
			'UpdateFile.filename' => $updateFile['UpdateFile']['filename'],
		];
		$conditions = $this->getWorkflowConditions($conditions);
		$count = $this->find('count', ['conditions' => $conditions]);
		return ($count > 0);
	}

/**
 * 自動リネーム
 *
 * 同一フォルダ内で名前が衝突したら自動でリネームする
 *
 * @param array &$updateFile CabinetFile データ
 * @return void
 */
	protected function _autoRename(& $updateFile) {
		$index = 0;
		if ($updateFile['UpdateFile']['is_folder']) {
			// folder
			$baseFolderName = $updateFile['UpdateFile']['filename'];
			while ($this->_existSameFilename($updateFile)) {
				// 重複し続ける限り数字を増やす
				$index++;
				$newFilename = sprintf('%s%03d', $baseFolderName, $index);
				$updateFile['UpdateFile']['filename'] = $newFilename;
			}
			$this->data['UpdateFile']['filename'] = $updateFile['UpdateFile']['filename'];
		} else {
			list($baseFileName, $ext) = $this->splitFileName(
				$updateFile['UpdateFile']['filename']
			);
			$extString = is_null($ext) ? '' : '.' . $ext;

			while ($this->_existSameFilename($updateFile)) {
				// 重複し続ける限り数字を増やす
				$index++;
				$newFilename = sprintf('%s%03d', $baseFileName, $index);
				$updateFile['UpdateFile']['filename'] = $newFilename . $extString;
			}
			$this->data['UpdateFile']['filename'] = $updateFile['UpdateFile']['filename'];
		}
	}

/**
 * 解凍してもよいファイルかチェック
 *
 * @param array $updateFile CabinetFile data
 * @return bool
 * @see https://github.com/NetCommons3/NetCommons3/issues/1024
 */
	public function isAllowUnzip($updateFile) {
		// zip以外NG
		if (Hash::get($updateFile, 'UploadFile.file.extension') != 'zip') {
			return false;
		}
		//未承認ファイルはNG
		if (Hash::get($updateFile, 'UpdateFile.status') != WorkflowComponent::STATUS_PUBLISHED) {
			return false;
		}
		// ダウンロードパスワードが設定されてたらNG
		if (isset($updateFile['AuthorizationKey'])) {
			return false;
		}

		return true;
	}
}
