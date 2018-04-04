<?php
class UpdateBlocksController extends UpdatesAppController {

/**
 * @var array use models
 */
	public $uses = array(
		'Blocks.Block',
		'Updates.Update',
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
		'Paginator',
	);

/**
 * use helpers
 *
 * @var array
 */
	public $helpers = array(
		'Blocks.BlockForm',
		'Blocks.BlockTabs' => array(
			'mainTabs' => array(
				'block_index' => array('url' => array('controller' => 'update_blocks')),
				//'frame_settings' => array('url' => array('controller' => 'cabinet_frame_settings')),
			),
			'blockTabs' => array(
				'block_settings' => array('url' => array('controller' => 'update_blocks')),
				'mail_settings',
				'role_permissions' => array('url' => array('controller' => 'update_block_role_permissions')),
			),
		),
		'Blocks.BlockIndex',
	);

/**
 * ・001_plugin_recdords.php から起動
 * ・index()は処理が完了した後に View/UpdateBlocks/index.ctp を呼び出す
 */
public function index() {
		$this->Paginator->settings = array(
			'Update' => $this->Update->getBlockIndexSettings()
		);

		$updates = $this->Paginator->paginate('Update');
		if (!$updates) {
			$this->view = 'Blocks.Blocks/not_found';
			return;
		}

		$this->set('updates', $updates);
		$this->request->data['Frame'] = Current::read('Frame');
	}
}