<?php
/**
 * CabinetAppModel
 */
App::uses('AppModel', 'Model');

/**
 * Class CabinetsAppModel
 */
class UpdatesAppModel extends AppModel {

/**
 * @var null 新規空データ
 */
	protected $_newRecord = null;

/**
 * プラリマリキーを除いた新規レコード配列を返す
 * ex) array('ModelName' => array('filedName' => default, ...));
 *
 * @return array
 */
	public function getNew() {
		if (is_null($this->_newRecord)) {
			$newRecord = array();
			foreach ($this->_schema as $fieldName => $fieldDetail) {
				if ($fieldName != $this->primaryKey) {
					$newRecord[$this->name][$fieldName] = $fieldDetail['default'];
				}
			}
			$this->_newRecord = $newRecord;
		}
		return $this->_newRecord;
	}
}
