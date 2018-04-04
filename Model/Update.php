<?php
/**
 * Cabinet Model
 *
 * @property Block $Block
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('UpdatesAppModel', 'Updates.Model');

/**
 * Cabinet Model
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Cabinets\Model
 */
class Update extends UpdatesAppModel {

/**
 * use tables
 *
 * @var string
 */
	public $useTable = 'updates';

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array();

/**
 * use behaviors
 *
 * @var array
 */
	public $actsAs = array(
		'Blocks.Block' => array(
			'name' => 'Update.name',
			'loadModels' => array(
				'UpdateSetting' => 'Updates.UpdateSetting',
				'WorkflowComment' => 'Workflow.WorkflowComment',
			)
		),
		'NetCommons.OriginalKey',
		//多言語
		'M17n.M17n' => array(
			'keyField' => 'block_id'
		),
	);

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Block' => array(
			'className' => 'Blocks.Block',
			'foreignKey' => 'block_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
	);

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
	);

/**
 * Called during validation operations, before validation. Please note that custom
 * validation rules can be defined in $validate.
 *
 * @param array $options Options passed from Model::save().
 * @return bool True if validate operation should continue, false to abort
 * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#beforevalidate
 * @see Model::save()
 */
	public function beforeValidate($options = array()) {
		$this->validate = Hash::merge(
			$this->validate,
			array(
				'key' => array(
					'notBlank' => array(
						'rule' => array('notBlank'),
						'message' => __d('net_commons', 'Invalid request.'),
						'allowEmpty' => false,
						'required' => true,
						'on' => 'update', // Limit validation to 'create' or 'update' operations
					),
				),

				//status to set in PublishableBehavior.

				'name' => array(
					'notBlank' => array(
						'rule' => array('notBlank'),
						'message' => sprintf(
							__d('net_commons', 'Please input %s.'),
							__d('cabinets', 'Cabinet name')
						),
						'required' => true
					),
				),
			)
		);

		if (!parent::beforeValidate($options)) {
			return false;
		}

		if (isset($this->data['UpdateSetting'])) {
			$this->UpdateSetting->set($this->data['UpdateSetting']);
			if (!$this->UpdateSetting->validates()) {
				$this->validationErrors = Hash::merge(
					$this->validationErrors,
					$this->UpdateSetting->validationErrors
				);
				return false;
			}
		}

		return;
	}

	/**
	*
	*/
	public function addRecords($data){
		if(!empty($data)){
			unser($data['add']);
			unset($data['id']);
			$this->begin();
			if(! $result = $this->save($data,false)){
				throw new InternalErrorException(__d('net_commons','Internal Server Error'));
				return false;
			}
			$this->commit();
		}
		return true;
	}

	/**
	*
	*/
	public function getRecords(){
		$result =  $this->find('all');
		return $result;
	}

}


