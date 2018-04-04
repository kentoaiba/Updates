<?php
/**
 * Add plugin migration
 *
 * @author Liberosystem, Inc <cms@libero-sys.co.jp>
 * @link http://libero-sys.co.jp NetCommons Support Project
 * @license Commercial License
 * @copyright Copyright 2018, Liberosystem, Inc.
 */

App::uses('NetCommonsMigration', 'NetCommons.Config/Migration');
//App::Users('Space','Rooms.Model');

/**
 * Add plugin migration
 *
 * @package NetCommons\updates\Config\Migration
 */
class PluginRecords extends NetCommonsMigration {

/**
 * Migration description
 *
 * @var string
 */
	public $description = 'plugin_records';

/**
 * Actions to be performed
 *
 * @var array $migration
 */
	public $migration = array(
		'up' => array(),
		'down' => array(),
	);

/**
 * Plugin data
 *
 * @var array $migration
 */
	public $records = array(
		'Plugin' => array(
			//パブリックスペース自体
			array(
				'language_id' => '2', 
				'key' => 'updates', 
				'namespace' => 'netcommons/updates', 
				'name' => '更新', 
				'type' => 1, 
				'default_action' => 'updates/view',
				'default_setting_action'=>'update_blocks/index'
			),
			array(
				'language_id' => '1',
				'key' => 'updates',
				'namespace' => 'netcommons/updates',
				'name' => 'updates',
				'type' => 1,
				'default_action' => 'updates/view',
				'default_setting_action' => 'update_blocks/index'
			),
		),
		'PluginsRole' => array(
								array(
									'role_key' => 'room_administrator',
									'plugin_key' => 'updates',
								),
							),
		'PluginsRoom' => array(
								array('room_id'=>'1','plugin_key'=>'updates',),
								array('room_id'=>'2','plugin_key'=>'updates',),
								array('room_id'=>'3','plugin_key'=>'updates',),
		),
	);


/**
 * Before migration callback
 *
 * @param string $direction Direction of migration process (up or down)
 * @return bool Should process continue
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction Direction of migration process (up or down)
 * @return bool Should process continue
 */
	public function after($direction) {
		$this->loadModels([
			'Plugin' => 'PluginManager.Plugin',
		]);

		if($direction === 'down'){
			$this->Plugin->uninstallPlugin($this->records['Plugin'][0]['key']);
			return true;
		}

		foreach ($this->records as $model => $records){
			if(!$this->updateRecords($model,$records)){
				return false;	
			}
		}
		return true;
	}
}
