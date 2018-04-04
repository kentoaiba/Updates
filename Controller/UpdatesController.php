<?php
/**
  *AccessCounters Countroller
  *
  *@author Liberosystem, Inc. <cms@libero-sys.co.jp>
  *@link http://libero-sys.co.jp NetCommons Support Project
  *@license Commercial License
  *@copyright Copyright 2018,NetCommons Prject
  */

App::uses('UpdatesAppController','Updates.Controller');
App::uses('ZipDownloader', 'Files.Utility');
App::uses('TemporaryFolder', 'Files.Utility');

/**
  *AccessCounters Controller
  *
  *@author Liberosystem, Inc. <cms@libero-sys.co.jp>
  *@package NetCommons\Totals\Controller
  */
class UpdatesController extends UpdatesAppController {
	/**
	  *use component
	  *
	  *@var array
	  */
	public $components = array(
		'Workflow.Workflow',
		'NetCommons.Permission' => array(
			'allow' => array(
				'add,edit,delete' => 'block_editable',
			),
		),
	);

	/**
	  *use models 
	  *
	  *@var array
	  */
	public $uses = array(
		'Blocks.Block',
		'Blocks.BlocksLanguage',
		'Updates.Update',
		'Updates.UpdateFile',
		'Workflow.WorkflowComment',
	);

	/**
	 * use helper
	 */
	public $helper = array(
		'Workflow.Workflow',
		'Blocks.BlockForm',
		'Blocks.BlockTabs' => array(
			'mainTabs' => array(
				'block_index',
				'frame_settings'
			),
			'blockTabs' => array(
				'block_settings' => array('url' => array('controller'=>'updates'))
			),
		),
	);	

	/**
	 * ・Updetaモデルからレコードを取得する 
	 *　・ViewVarsに$resultをセットする
	 * ・必要であれば、View側で$resultを使用する
	 */
	public function view(){
		$result = $this->Update->getRecords();
		$this->set('result',$result);

		$this->log("UpdateController/params","debug"); //debug
		$this->log($this->params,"debug"); //debug

		// $result = $this->UpdateFile->saveFile($this->request->data);

		// -------------------------------------------
		// $updateFile = $this->UpdateFile->getNew();
		// $this->set('updateFile', $updateFile);

		// if ($this->request->is('post')) {

		// 	if (!Hash::get($this->request->data, 'UpdateFile.use_auth_key', false)) {
		// 		// 認証キーを使わない設定だったら、認証キーのPOST値を握りつぶす
		// 		unset($this->request->data['AuthorizationKey']);
		// 	}

		// 	$this->UpdateFile->create();

		// 	// set status
		// 	$status = $this->Workflow->parseStatus();
		// 	$this->request->data['UpdateFile']['status'] = $status;

		// 	// set update_key
		// 	$this->request->data['UpdateFile']['update_key'] = $this->_update['Update']['key'];
		// 	// set language_id
		// 	$this->request->data['UpdateFile']['language_id'] = Current::read('Language.id');
		// 	// is_folderセット
		// 	$this->request->data['UpdateFile']['is_folder'] = 0;
		// 	//$this->request->data['UpdateFileTree']['parent_id'] = null;
		// 	$this->request->data['UpdateFileTree']['update_key'] = $this->_update['Update']['key'];

		// 	// タイトルをファイル名にする
		// 	$filename = $this->request->data['UpdateFile']['file']['name'];
		// 	$this->request->data['UpdateFile']['filename'] = $filename;
		// 	if (($this->UpdateFile->saveFile($this->request->data))) {
		// 		$url = $this->__getParentFolderUrl(
		// 			$this->request->data['UpdateFileTree']['parent_id']
		// 		);
		// 		return $this->redirect($url);
		// 	}

		// 	$this->NetCommons->handleValidationError($this->UpdateFile->validationErrors);

		// } else {
		// 	$this->request->data = $updateFile;
		// 	$this->request->data['UpdateFileTree']['parent_id'] = Hash::get(
		// 		$this->request->named,
		// 		'parent_id',
		// 		null
		// 	);
		// }

		// $parentId = $this->request->data['UpdateFileTree']['parent_id'];
		// if ($parentId > 0) {
		// 	$folderPath = $this->UpdateFileTree->getPath($parentId, null, 0);
		// } else {
		// 	$folderPath = [];
		// }

		// $folderPath[] = [
		// 	'UpdateFile' => [
		// 		'filename' => __d('updates', 'Add File')
		// 	]
		// ];
		// $this->set('folderPath', $folderPath);

	}

	/**
	 *
	 */
	public function add(){
		if($this->request->is('post')){
			$data = $this->request->data;
			$result = $this->Update->addRecords($data);

			if($result){
				$this->redirect(NetCommonsUrl::backToIndexUrl('default_action'));
				return;
			}

		}
	}

}