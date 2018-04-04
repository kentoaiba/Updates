<?php
/**
 * UpdateFile Model
 *
 * @property UpdateCategory $UpdateCategory
 * @property UpdateFileTagLink $UpdateFileTagLink
 *
 * @author   Jun Nishikawa <topaz2@m0n0m0n0.com>
 * @link     http://www.netcommons.org NetCommons Project
 * @license  http://www.netcommons.org/license.txt NetCommons License
 */

App::uses('UpdatesAppModel', 'Updates.Model');
App::uses('NetCommonsTime', 'NetCommons.Utility');
//App::uses('AttachmentBehavior', 'Files.Model/Behavior');

/**
 * Summary for UpdateFile Model
 */
class UpdateFileTree extends UpdatesAppModel {

/**
 * @var int recursiveはデフォルトアソシエーションなしに
 */
	public $recursive = 0;

/**
 * use behaviors
 *
 * @var array
 */
	public $actsAs = array(
		'Tree'
	);

/**
 * beforeFind
 *
 * @param array $query クエリ
 * @return array クエリ
 */
	public function beforeFind($query) {
		$this->loadModels([
			'UpdateFile' => 'Updates.UpdateFile',
		]);

		// workflow連動でアソシエーションさせる！
		$association = [
			//'UpdateFileTree.update_file_key = UpdateFile.key'
			'UpdateFileTree.id = UpdateFile.update_file_tree_id'
		];
		$updateFileCondition = $this->UpdateFile->getWorkflowConditions($association);

		$belongsTo = [
			'belongsTo' => [
				'UpdateFile' => array(
					'className' => 'Updates.UpdateFile',
					'foreignKey' => false,
					'conditions' => $updateFileCondition,
					'fields' => '',
					'order' => ''
				),
			]
		];
		$this->bindModel($belongsTo, true);

		// recursive 0以上の時だけにする NOT NULL 条件を追加する
		$recursive = Hash::get($query, 'recursive', $this->recursive);
		if ($recursive >= 0) {
			// UpdateFileがLEFT JOIN されるが、
			// JOINできないTreeレコードを切り捨てるためにUpdateFile.id NOT NULLを条件に入れる
			$query['conditions']['NOT']['UpdateFile.id'] = null;
		}
		return $query;
	}

/**
 * modifiedを常に更新
 *
 * @param null $data 登録データ
 * @param bool $validate バリデートを実行するか
 * @param array $fieldList フィールド
 * @return mixed
 *
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
	public function save($data = null, $validate = true, $fieldList = array()) {
		// 保存前に modified フィールドをクリアする
		$this->set($data);
		if (isset($this->data[$this->alias]['modified'])) {
			unset($this->data[$this->alias]['modified']);
		}
		return parent::save($this->data, $validate, $fieldList);
	}
}
