<?php
/**
 * UpdateFilesEdit
 *
 * @property NetCommonsWorkflow $NetCommonsWorkflow
 * @property PaginatorComponent $Paginator
 * @property UpdateFile $UpdateFile
 * @property UpdateCategory $UpdateCategory
 * @property NetCommonsComponent $NetCommons
 */
App::uses('UpdatesAppController', 'Updates.Controller');

/**
 * UpdateFilesEdit Controller
 *
 *
 * @author   Ryuji AMANO <ryuji@ryus.co.jp>
 * @link     http://www.netcommons.org NetCommons Project
 * @license  http://www.netcommons.org/license.txt NetCommons License
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class UpdateFilesEditController extends UpdatesAppController {

/**
 * @var array use models
 */
	public $uses = array(
		'Updates.UpdateFile',
		'Updates.UpdateFileTree',
		'Workflow.WorkflowComment',
	);

/**
 * Components
 *
 * @var array
 */
	public $components = array(
		'NetCommons.Permission' => array(
			//アクセスの権限
			'allow' => array(
				'add,edit,delete,move' => 'content_creatable',
				// フォルダの作成・編集は公開権限以上
				'add_folder,edit_folder' => 'content_publishable',
				'unzip' => 'content_publishable'
			),
		),
		'Workflow.Workflow',

		'NetCommons.NetCommonsTime',
		'Files.FileUpload',
		'Files.Download',
	);

/**
 * @var array helpers
 */
	public $helpers = array(
		'NetCommons.BackTo',
		'NetCommons.NetCommonsForm',
		'Workflow.Workflow',
		'NetCommons.NetCommonsTime',
		//'Likes.Like',
	);

/**
 * beforeFilter
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();
		$this->_update = $this->Update->find('first', array(
			'recursive' => 0,
			'conditions' => $this->Update->getBlockConditionById(),
		));
		$this->set('update', $this->_update);
	}

/**
 * 親フォルダのURLを取得
 *
 * @param int $parentId 親ID
 * @return string
 */
	private function __getParentFolderUrl($parentId) {
		$parentFolder = $this->UpdateFileTree->find('first', array(
			'recursive' => 0,
			'conditions' => array(
				'UpdateFileTree.id' => $parentId
			)
		));
		$url = NetCommonsUrl::actionUrl(
			array(
				'controller' => 'update_files',
				'action' => 'index',
				'block_id' => Current::read('Block.id'),
				'frame_id' => Current::read('Frame.id'),
				'key' => Hash::get($parentFolder, 'UpdateFile.key', null)
			)
		);
		return $url;
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		//レイアウトの設定
		$this->viewClass = 'View';
		$this->layout = 'NetCommons.modal';

		$this->set('isEdit', false);

		$updateFile = $this->UpdateFile->getNew();
		$this->set('updateFile', $updateFile);

		if ($this->request->is('post')) {

			if (!Hash::get($this->request->data, 'UpdateFile.use_auth_key', false)) {
				// 認証キーを使わない設定だったら、認証キーのPOST値を握りつぶす
				unset($this->request->data['AuthorizationKey']);
			}

			$this->UpdateFile->create();

			// set status
			$status = $this->Workflow->parseStatus();
			$this->request->data['UpdateFile']['status'] = $status;

			// set update_key
			$this->request->data['UpdateFile']['update_key'] = $this->_update['Update']['key'];
			// set language_id
			$this->request->data['UpdateFile']['language_id'] = Current::read('Language.id');
			// is_folderセット
			$this->request->data['UpdateFile']['is_folder'] = 0;
			//$this->request->data['UpdateFileTree']['parent_id'] = null;
			$this->request->data['UpdateFileTree']['update_key'] = $this->_update['Update']['key'];

			// タイトルをファイル名にする
			$filename = $this->request->data['UpdateFile']['file']['name'];
			$this->request->data['UpdateFile']['filename'] = $filename;
			if (($this->UpdateFile->saveFile($this->request->data))) {
				$url = $this->__getParentFolderUrl(
					$this->request->data['UpdateFileTree']['parent_id']
				);
				return $this->redirect($url);
			}

			$this->NetCommons->handleValidationError($this->UpdateFile->validationErrors);

		} else {
			$this->request->data = $updateFile;
			$this->request->data['UpdateFileTree']['parent_id'] = Hash::get(
				$this->request->named,
				'parent_id',
				null
			);
		}

		$parentId = $this->request->data['UpdateFileTree']['parent_id'];
		if ($parentId > 0) {
			$folderPath = $this->UpdateFileTree->getPath($parentId, null, 0);
		} else {
			$folderPath = [];
		}

		$folderPath[] = [
			'UpdateFile' => [
				'filename' => __d('cabinet', 'Add File')
			]
		];
		$this->set('folderPath', $folderPath);

		//$this->render('form');
	}

/**
 * edit method
 *
 * @throws ForbiddenException
 * @return void
 */
	public function edit() {
		$this->set('isEdit', true);
		$key = Hash::get($this->request->params, 'key');

		//  keyのis_latstを元に編集を開始
		$conditions = $this->UpdateFile->getWorkflowConditions([
			'UpdateFile.key' => $key,
			'UpdateFile.update_key' => Hash::get($this->_update, 'Update.key'),
		]);
		$updateFile = $this->UpdateFile->find('first', ['conditions' => $conditions]);
		if (empty($updateFile)) {
			return $this->throwBadRequest();
		}

		// フォルダならエラー
		if ($updateFile['UpdateFile']['is_folder'] == true) {
			return $this->throwBadRequest();
		}
		if ($this->UpdateFile->canEditWorkflowContent($updateFile) === false) {
			return $this->throwBadRequest();
		}

		$treeId = $updateFile['UpdateFileTree']['id'];
		$folderPath = $this->UpdateFileTree->getPath($treeId, null, 0);
		$this->set('folderPath', $folderPath);

		if ($this->request->is(array('post', 'put'))) {
			$this->UpdateFile->create();
			$status = $this->Workflow->parseStatus();

			// ファイル名変更
			if ($this->request->data['UpdateFile']['file']['error'] == UPLOAD_ERR_NO_FILE) {
				// 新たなアップロードがなければ元の拡張子をつける。
				list($withOutExtFileName, $ext) = $this->UpdateFile->splitFileName(
					$updateFile['UpdateFile']['filename']
				);
			} else {
				// 新たなアップロードがあれば新たなファイルの拡張子をつける
				$ext = pathinfo(
					$this->request->data['UpdateFile']['file']['name'],
					PATHINFO_EXTENSION
				);
			}
			$this->request->data['UpdateFile']['filename'] =
				$this->request->data['UpdateFile']['withOutExtFileName'];
			if ($ext !== null) {
				$this->request->data['UpdateFile']['filename'] .= '.' . $ext;
				$this->request->data['UpdateFile']['extension'] = $ext;
			}

			$this->request->data['UpdateFile']['status'] = $status;
			// set update_key
			$this->request->data['UpdateFile']['update_key'] = $this->_update['Update']['key'];
			// set language_id
			$this->request->data['UpdateFile']['language_id'] = Current::read('Language.id');

			$data = Hash::merge($updateFile, $this->request->data);

			if (!Hash::get($this->request->data, 'UpdateFile.use_auth_key', false)) {
				// 認証キーを使わない設定だったら、認証キーのPOST値を握りつぶす
				unset($data['AuthorizationKey']);
			}

			if ($this->UpdateFile->saveFile($data)) {
				$url = $this->__getParentFolderUrl(
					$this->request->data['UpdateFileTree']['parent_id']
				);
				return $this->redirect($url);
			}

			$this->NetCommons->handleValidationError($this->UpdateFile->validationErrors);

		} else {

			$this->request->data = $updateFile;
			// 拡張子はとりのぞいておく
			list($withOutExtFileName, $ext) = $this->UpdateFile->splitFileName(
				$updateFile['UpdateFile']['filename']
			);
			$this->request->data['UpdateFile']['withOutExtFileName'] = $withOutExtFileName;
			$this->request->data['UpdateFile']['extension'] = $ext;
		}

		$this->set('updateFile', $updateFile);
		$this->set('isDeletable', $this->UpdateFile->canDeleteWorkflowContent($updateFile));

		$this->render('form');
	}

/**
 * add method
 *
 * @return void
 */
	public function add_folder() {
		$this->set('isEdit', false);

		$updateFile = $this->UpdateFile->getNew();
		$this->set('updateFile', $updateFile);

		if ($this->request->is('post')) {
			$this->UpdateFile->create();

			// set status folderは常に公開
			$status = WorkflowComponent::STATUS_PUBLISHED;
			$this->request->data['UpdateFile']['status'] = $status;

			// set update_key
			$this->request->data['UpdateFile']['update_key'] = $this->_update['Update']['key'];
			// set language_id
			$this->request->data['UpdateFile']['language_id'] = Current::read('Language.id');
			// is_folderセット
			$this->request->data['UpdateFile']['is_folder'] = 1;
			//$this->request->data['UpdateFileTree']['parent_id'] = null;
			$this->request->data['UpdateFileTree']['update_key'] = $this->_update['Update']['key'];

			if (($result = $this->UpdateFile->saveFile($this->request->data))) {
				$url = NetCommonsUrl::actionUrl(
					array(
						'controller' => 'update_files',
						'action' => 'folder_detail',
						'block_id' => Current::read('Block.id'),
						'frame_id' => Current::read('Frame.id'),
						'key' => $result['UpdateFile']['key']
					)
				);
				return $this->redirect($url);
			}

			$this->NetCommons->handleValidationError($this->UpdateFile->validationErrors);

		} else {
			$this->request->data = $updateFile;
			$this->request->data['UpdateFileTree']['parent_id'] = Hash::get(
				$this->request->named,
				'parent_id',
				null
			);
		}

		$parentId = $this->request->data['UpdateFileTree']['parent_id'];
		if ($parentId > 0) {
			$folderPath = $this->UpdateFileTree->getPath($parentId, null, 0);
		} else {
			$folderPath = [];
		}

		$folderPath[] = [
			'UpdateFile' => [
				'filename' => __d('cabinet', 'Add Folder')
			]
		];
		$this->set('folderPath', $folderPath);

		$this->render('folder_form');
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @throws ForbiddenException
 * @throws InternalErrorException
 * @return void
 */
	public function edit_folder() {
		$this->set('isEdit', true);
		//$key = $this->request->params['named']['key'];
		$key = $this->request->params['key'];

		//  keyのis_latstを元に編集を開始
		$conditions = $this->UpdateFile->getWorkflowConditions([
			'UpdateFile.key' => $key,
			'UpdateFile.update_key' => $this->_update['Update']['key']
		]);
		$updateFile = $this->UpdateFile->find('first', ['conditions' => $conditions]);
		if (empty($updateFile)) {
			//  404 NotFound
			throw new NotFoundException();
		}
		if ($updateFile['UpdateFile']['is_folder'] == false) {
			throw new InternalErrorException();
		}
		if ($this->UpdateFile->canEditWorkflowContent($updateFile) === false) {
			throw new ForbiddenException(__d('net_commons', 'Permission denied'));
		}

		$treeId = $updateFile['UpdateFileTree']['id'];
		$folderPath = $this->UpdateFileTree->getPath($treeId, null, 0);
		$this->set('folderPath', $folderPath);

		if ($this->request->is(array('post', 'put'))) {

			$this->UpdateFile->create();
			//$this->request->data['UpdateFile']['update_key'] = ''; // https://github.com/NetCommons3/NetCommons3/issues/7 対策

			// set status folderは常に公開
			$status = WorkflowComponent::STATUS_PUBLISHED;
			$this->request->data['UpdateFile']['status'] = $status;

			// set update_key
			$this->request->data['UpdateFile']['update_key'] = $this->_update['Update']['key'];
			// set language_id
			$this->request->data['UpdateFile']['language_id'] = Current::read('Language.id');

			$data = Hash::merge($updateFile, $this->request->data);

			unset($data['UpdateFile']['id']); // 常に新規保存

			if ($this->UpdateFile->saveFile($data)) {
				$url = NetCommonsUrl::actionUrl(
					array(
						'controller' => 'update_files',
						'action' => 'folder_detail',
						'frame_id' => Current::read('Frame.id'),
						'block_id' => Current::read('Block.id'),
						'key' => $data['UpdateFile']['key']
					)
				);

				return $this->redirect($url);
			}

			$this->NetCommons->handleValidationError($this->UpdateFile->validationErrors);

		} else {

			$this->request->data = $updateFile;

		}

		$this->set('updateFile', $updateFile);
		$this->set('isDeletable', $this->UpdateFile->canDeleteWorkflowContent($updateFile));

		$this->render('folder_form');
	}

/**
 * フォルダ選択画面
 *
 * @return void
 */
	public function select_folder() {
		// 移動するファイル・フォルダを取得
		$key = isset($this->request->params['key']) ? $this->request->params['key'] : null;
		$conditions = $this->UpdateFile->getWorkflowConditions([
			'UpdateFile.key' => $key,
			'UpdateFile.update_key' => $this->_update['Update']['key']
		]);
		$updateFile = $this->UpdateFile->find('first', ['conditions' => $conditions]);
		if ($updateFile) {
			$currentTreeId = $updateFile['UpdateFileTree']['parent_id'];
		} else {
			// 新規フォルダ作成時はkeyが拾えないのでparent_idで現在位置を特定
			$currentTreeId = Hash::get($this->request->named, 'parent_id', null);
		}

		$this->set('currentTreeId', $currentTreeId);
		//レイアウトの設定
		$this->viewClass = 'View';
		$this->layout = 'NetCommons.modal';

		// 全フォルダツリーを得る
		$conditions = [
			'UpdateFile.is_folder' => 1,
			'UpdateFile.update_key' => $this->_update['Update']['key'],
		];
		// 移動するのがフォルダだったら、下位フォルダを除外する
		if (isset($updateFile) && Hash::get($updateFile, 'UpdateFile.is_folder')) {
			$conditions['NOT'] = array(
				'AND' => array(
					'UpdateFileTree.lft >=' => $updateFile['UpdateFileTree']['lft'],
					'UpdateFileTree.rght <=' => $updateFile['UpdateFileTree']['rght']
				)
			);
		}

		$folders = $this->UpdateFileTree->find(
			'threaded',
			['conditions' => $conditions, 'recursive' => 0, 'order' => 'UpdateFile.filename ASC']
		);
		$this->set('folders', $folders);

		// カレントフォルダのツリーパスを得る
		if ($currentTreeId > 0) {
			$folderPath = $this->UpdateFileTree->getPath($currentTreeId, null, 0);
			$this->set('folderPath', $folderPath);
			$nestCount = count($folderPath);
			if ($nestCount > 1) {
				// 親フォルダあり
				$url = NetCommonsUrl::actionUrl(
					[
						'key' => $folderPath[$nestCount - 2]['UpdateFile']['key'],
						'block_id' => Current::read('Block.id'),
						'frame_id' => Current::read('Frame.id'),
					]
				);

			} else {
				// 親はキャビネット
				$url = NetCommonsUrl::backToIndexUrl();
			}
			$this->set('parentUrl', $url);
		} else {
			// ルート
			$this->set('folderPath', array());
			$this->set('parentUrl', false);
		}
	}

/**
 * ファイル・フォルダ移動
 *
 * @return void
 * @throws ForbiddenException
 */
	public function move() {
		$this->request->allowMethod('post', 'put');

		$key = $this->request->params['key'];

		// keyのis_latestを元に編集を開始
		$conditions = $this->UpdateFile->getWorkflowConditions([
			'UpdateFile.key' => $key,
			'UpdateFile.update_key' => $this->_update['Update']['key']
		]);
		$updateFile = $this->UpdateFile->find('first', ['conditions' => $conditions]);
		$parentId = Hash::get($this->request->data, 'UpdateFileTree.parent_id', null);

		$updateFile['UpdateFileTree']['parent_id'] = $parentId;
		// フォルダの移動は公開権限が必要
		if ($updateFile['UpdateFile']['is_folder']) {
			if (!Current::permission('content_publishable')) {
				throw new ForbiddenException(__d('net_commons', 'Permission denied'));
			}
		}

		// 編集できるユーザかチェック
		if ($this->UpdateFile->canEditWorkflowContent($updateFile) === false) {
			return $this->throwBadRequest();
		}

		// 権限に応じたステータスをセット
		// 公開されてるファイルを公開権限がないユーザが移動したら承認待ちにもどす
		$isPublish =
			($updateFile['UpdateFile']['status'] == WorkflowComponent::STATUS_PUBLISHED);
		if ($isPublish && !Current::permission('content_publishable')) {
			$updateFile['UpdateFile']['status'] = WorkflowComponent::STATUS_APPROVAL_WAITING;
		}

		$result = $this->UpdateFile->saveFile($updateFile);
		//$result = $this->UpdateFileTree->save($updateFile);

		if ($result) {
			//正常の場合
			//if($updateFile['UpdateFile']['is_folder']) {
			// reloadするのでSession::flash
			//$this->Flash->set(__d('updates', '移動しました'), );
			//$this->Session->setFlash('移動しました');

			//}else{
			$this->NetCommons->setFlashNotification(
				__d('cabinet', 'Moved.'),
				array(
					'class' => 'success',
					'ajax' => !$updateFile['UpdateFile']['is_folder']
				)
			);
			//}
		} else {
			$this->NetCommons->setFlashNotification(
				__d('cabinet', 'Move failed'),
				array(
					'class' => 'danger',
				)
			);
			//$this->NetCommons->handleValidationError($this->RolesRoomsUser->validationErrors);
		}
		//$this->set('_serialize', ['message']);
		$this->emptyRender();
	}

/**
 * フォルダパスをJsonで返す
 *
 * @return void
 */
	public function get_folder_path() {
		$treeId = Hash::get($this->request->named, 'tree_id', null);
		$folderPath = $this->UpdateFileTree->getPath($treeId, null, 0);
		//foreach($folderPath as &$folder){
		//	$folder['url'] =
		//}
		$this->set('folderPath', $folderPath);
		$this->set('code', 200);
		$this->set('_serialize', ['folderPath', 'code']);
	}

/**
 * delete method
 *
 * @throws InternalErrorException
 * @return void
 */
	public function delete() {
		$this->request->allowMethod('post', 'delete');

		$key = $this->request->data['UpdateFile']['key'];

		$conditions = [
			'UpdateFile.key' => $key,
			'UpdateFile.is_latest' => 1,
		];
		$updateFile = $this->UpdateFile->find('first', ['conditions' => $conditions]);

		// フォルダを削除できるのは公開権限のあるユーザだけ。
		if ($updateFile['UpdateFile']['is_folder'] && !Current::permission('content_publishable')) {
			return $this->throwBadRequest();
		}

		// 権限チェック
		if ($this->UpdateFile->canDeleteWorkflowContent($updateFile) === false) {
			return $this->throwBadRequest();
		}

		if ($this->UpdateFile->deleteFileByKey($key) === false) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}
		return $this->redirect(
			NetCommonsUrl::actionUrl(
				array(
					'controller' => 'update_files',
					'action' => 'index',
					'frame_id' => Current::read('Frame.id'),
					'block_id' => Current::read('Block.id')
				)
			)
		);
	}

/**
 * unzip action
 *
 * @return void
 */
	public function unzip() {
		$this->request->allowMethod('post', 'put');

		$key = $this->request->params['key'];
		$conditions = $this->UpdateFile->getWorkflowConditions([
			'UpdateFile.key' => $key,
			'UpdateFile.update_key' => $this->_update['Update']['key']
		]);
		$updateFile = $this->UpdateFile->find('first', ['conditions' => $conditions]);

		// 解凍しても良いかのガード条件チェック
		if (!$this->UpdateFile->isAllowUnzip($updateFile)) {
			return $this->throwBadRequest();
		}

		if (!$this->UpdateFile->unzip($updateFile)) {
			// Validate error
			$message = implode("<br />", $this->UpdateFile->validationErrors);
			$this->NetCommons->setFlashNotification(
				$message,
				[
					'class' => 'danger',
					//'interval' => NetCommonsComponent::ALERT_VALIDATE_ERROR_INTERVAL,
					//'ajax' => true,
				]
			);
			return;
		}
		// 成功した場合はリダイレクトするので、ajax = falseにしてセッションにメッセージをつんでおく
		$message = __d('cabinet', 'Unzip success.');
		$this->NetCommons->setFlashNotification(
			$message,
			array(
				'class' => 'success',
				'ajax' => false,
			)
		);
		$this->NetCommons->renderJson(['class' => 'success'], $message, 200);
	}

/**
 * 解凍してもよいファイルかチェック
 *
 * @param array $updateFile UpdateFile data
 * @return bool
 * @see https://github.com/NetCommons3/NetCommons3/issues/1024
 */
	protected function _isAllowUnzip($updateFile) {
		// zip以外NG
		if (Hash::get($updateFile, 'UploadFile.file.extension') != 'zip') {
			return false;
		}
		//未承認ファイルはNG
		if (Hash::get($updateFile, 'UploadFile.status') != WorkflowComponent::STATUS_PUBLISHED) {
			return false;
		}
		// ダウンロードパスワードが設定されてたらNG
		if (isset($updateFile['AuthorizationKey'])) {
			return false;
		}

		return true;
	}
}
