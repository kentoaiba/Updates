<?php
/**
 * CabinetsApp
 */
App::uses('AppController', 'Controller');

/**
 * Class CabinetsAppController
 *
 * @property CabinetFrameSetting $CabinetFrameSetting
 */
class UpdatesAppController extends AppController {

/**
 * @var array キャビネット名
 */
	protected $_updateTitle;

/**
 * @var array キャビネット設定
 */
	protected $_updateSetting;

/**
 * @var array フレーム設定
 */
	protected $_frameSetting;

/**
 * use components
 *
 * @var array
 */
	public $components = array(
		//'NetCommons.NetCommonsBlock',
		//'NetCommons.NetCommonsFrame',
		'Pages.PageLayout',
		'Security',
	);

/**
 * @var array helpers
 */
	public $helpers = array(
		'Workflow.Workflow',
		//'Cabinets.CabinetsFormat',
	);

/**
 * @var array use model
 */
	public $uses = array(
		'Updates.Update',
	);
}