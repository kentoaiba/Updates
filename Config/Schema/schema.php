<?php
/**
  *Schema file
  *
  *@author Liberosystem,Inc <cms@libero-sys.co.jp>
  *@link http://libero-sys.co.jp NetCommons Support project
  *@license Commercial License
  *@copyright Copyright 2018,Liberosystem, Inc
  */

/**
  *Schema file
  *
  *@author Liberosystem, Inc <cms@libero-sys.co.jp>
  *@package NetCommons\updates\Config\Schema
  *@SuppressWarnings(PHPMD.LongVariable)
  *@SuppressWarnings(PHPMD.TooManyFields)
  */

class UpdatesSchema extends CakeSchema{
  /**
   *Database connection
   *
   *@var string
   */
  public $connection = 'master';

  /**
  *before
  *
  *@param array $event event
  *@return bool
  */
  public function before($event = array()){
    return true;
  }

  /**
  *after
  *
  *@param array $event event
  *@return void
  */
   public function after($event = array()){
   }

  /**
  *update_frame_settings table
  *
  *@var array
  */
  public $update_frame_settings = array(
      'id' => array('type'=>'integer','null'=>false,'default'=>null,'unsigned'=>false,'key'=>'primary','comment'=>'ID'),
      'frame_key' => array('type'=>'string','null'=>false,'default'=>null,'key'=>'index','collate'=>'utf8mb4_general_ci',
      'comment'=>'frame_key | フレームkey = frames.key|','charset'=>'utf8mb4'),
      'display_type' => array('type'=>'integer','null'=>true,'default'=>'0','length'=>4,'unsigned'=>false,
                              'comment'=>'display_type | 表示タイプ   ||'),
      'created_user' => array('type'=>'integer','null'=>true,'default'=>null,'unsigned'=>false,
                              'comment'=>'createduser | 作成者 | users.id |'),
      'created' => array('type'=>'datetime','null'=>true,'default'=>null,'comment'=>'created datetime | 作成日時 | users.id'),
      'modified_user' => array('type'=>'integer','null'=>true,'default'=>null,'unsigned'=>false,
                               'comment'=>'modified user | 更新者 | users.id |'),
      'modified' => array('type'=>'datetime','null'=>true,'default'=>null,'comment'=>'modified datetime | 更新日時 |'),
      'indexes' => array('PRIMARY'=>array('column'=>'id','unique'=>1),
                          'frame_key'=>array('column'=>'frame_key','unique'=>0)
                        ),
      'tableParameters'=>array('charset'=>'utf8mb4','collate'=>'utf8mb4_general_ci','engine'=>'InnoDB')
    );

  /**
    *updates table
    *
    *@var array
    */
  public $updates = array(
    'id' => array('type'=>'integer','null'=>false,'default'=>null,'unsigned'=>false,'key'=>'primary','comment'=>'ID'),
    'block_key' => array('type'=>'string','null'=>false,'default'=>null,'key'=>'index','collate'=>'utf8mb4_general_ci',
                          'comment'=>'block key | ブロックkey | blocks key |','charset' => 'utf8mb4'
                        ),
    'count' => array('type'=>'integer','null'=>false,'default'=>'0','unsigned'=>false,
                     'comment'=>'Number of counts | 集計表示回数 ||'
                    ),
    'created_user' => array('type'=>'integer','null'=>true,'default'=>null,'unsigned'=>false,
                            'comment'=>'created user | 作成者 | users.id|'
                            ),
    'created' => array('type'=>'datetime','null'=>true,'default'=>null,
                       'comment'=>'created datetime | 作成日時 | users.id|'
                      ),
    'modified_user' => array('type'=>'integer','null'=>true,'default'=>null,'unsigned'=>false,
                             'comment'=>'modified user | 更新者 | users.id'
                            ),
    'modified' => array('type'=>'datetime','null'=>true,'default'=>null,
                        'comment'=>'modified datetime | 更新日時 ||'
                        ),
    'indexes' => array('PRIMARY'=>array('column'=>'id','unique'=>1),
                       'block_key'=>array('column'=>'block_key','unique'=>0)
                      ),
    'tableParameters' => array('charset'=>'utf8mb4','collate'=>'utf8mb4_general_ci','engine'=>'InnoDB')
  );

  /**
    *updatesData table
    *
    *@var array
    */
  public $update_data = array(
    'id' => array('type'=>'integer','null'=>false,'default'=>null,'unsigned'=>false,'key'=>'primary','comment'=>'ID'),
    'block_key' => array('type'=>'string','null'=>false,'default'=>null,'key'=>'index','collate'=>'utf8mb4_general_ci',
                          'comment'=>'block key | ブロックkey | blocks key |','charset' => 'utf8mb4'
                        ),
    'count' => array('type'=>'integer','null'=>false,'default'=>'0','unsigned'=>false,
                     'comment'=>'Number of counts | 集計表示回数 ||'
                    ),
    'created_user' => array('type'=>'integer','null'=>true,'default'=>null,'unsigned'=>false,
                            'comment'=>'created user | 作成者 | users.id|'
                            ),
    'created' => array('type'=>'datetime','null'=>true,'default'=>null,
                       'comment'=>'created datetime | 作成日時 | users.id|'
                      ),
    'modified_user' => array('type'=>'integer','null'=>true,'default'=>null,'unsigned'=>false,
                             'comment'=>'modified user | 更新者 | users.id'
                            ),
    'modified' => array('type'=>'datetime','null'=>true,'default'=>null,
                        'comment'=>'modified datetime | 更新日時 ||'
                        ),
    'indexes' => array('PRIMARY'=>array('column'=>'id','unique'=>1),
                       'block_key'=>array('column'=>'block_key','unique'=>0)
                      ),
    'tableParameters' => array('charset'=>'utf8mb4','collate'=>'utf8mb4_general_ci','engine'=>'InnoDB')
  );


/**
 * @var array cabinet_file_trees
 */
  public $update_file_trees = array(
    'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'unsigned' => false, 'key' => 'primary', 'comment' => 'ID'),
    'update_key' => array('type' => 'string', 'null' => false, 'default' => null, 'key' => 'index', 'collate' => 'utf8_general_ci', 'comment' => 'キャビネットキー', 'charset' => 'utf8'),
    'update_file_key' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => 'ファイルキー', 'charset' => 'utf8'),
    'parent_id' => array('type' => 'integer', 'null' => true, 'default' => null, 'unsigned' => false, 'key' => 'index', 'comment' => '親フォルダのID treeビヘイビア必須カラム'),
    'lft' => array('type' => 'integer', 'null' => false, 'default' => null, 'unsigned' => false, 'key' => 'index', 'comment' => 'lft  treeビヘイビア必須カラム'),
    'rght' => array('type' => 'integer', 'null' => false, 'default' => null, 'unsigned' => false, 'comment' => 'rght  treeビヘイビア必須カラム'),
    'created_user' => array('type' => 'integer', 'null' => true, 'default' => '0', 'unsigned' => false, 'comment' => '作成者'),
    'created' => array('type' => 'datetime', 'null' => true, 'default' => null, 'comment' => '作成日時'),
    'modified_user' => array('type' => 'integer', 'null' => true, 'default' => '0', 'unsigned' => false, 'comment' => '更新者'),
    'modified' => array('type' => 'datetime', 'null' => true, 'default' => null, 'comment' => '更新日時'),
    'indexes' => array(
      'PRIMARY' => array('column' => 'id', 'unique' => 1),
      'parent_id' => array('column' => 'parent_id', 'unique' => 0),
      'update_key' => array('column' => array('update_key', 'lft', 'rght'), 'unique' => 0),
      'lft' => array('column' => array('lft', 'rght'), 'unique' => 0)
    ),
    'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB')
  );


/**
 * @var array cabinet_files
 */
  public $update_files = array(
    'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'unsigned' => false, 'key' => 'primary', 'comment' => 'ID'),
    'update_key' => array('type' => 'string', 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => 'キャビネットキー', 'charset' => 'utf8'),
    'update_file_tree_parent_id' => array('type' => 'integer', 'null' => true, 'default' => null, 'unsigned' => false),
    'update_file_tree_id' => array('type' => 'integer', 'null' => true, 'default' => null, 'unsigned' => false),
    'key' => array('type' => 'string', 'null' => false, 'default' => null, 'key' => 'index', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
    'is_folder' => array('type' => 'boolean', 'null' => false, 'default' => '0'),
    'use_auth_key' => array('type' => 'boolean', 'null' => false, 'default' => '0'),
    'status' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 4, 'unsigned' => false, 'comment' => '公開状況  1:公開中、2:公開申請中、3:下書き中、4:差し戻し'),
    'is_active' => array('type' => 'boolean', 'null' => false, 'default' => '0'),
    'is_latest' => array('type' => 'boolean', 'null' => true, 'default' => null),
    'language_id' => array('type' => 'integer', 'null' => true, 'default' => null, 'unsigned' => false),
    'is_origin' => array('type' => 'boolean', 'null' => false, 'default' => '1', 'comment' => 'オリジナルかどうか'),
    'is_translation' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'comment' => '翻訳したかどうか'),
    'is_original_copy' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'comment' => 'オリジナルのコピー。言語を新たに追加したときに使用する'),
    'filename' => array('type' => 'string', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => 'タイトル', 'charset' => 'utf8'),
    'description' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '概要', 'charset' => 'utf8'),
    'created_user' => array('type' => 'integer', 'null' => true, 'default' => '0', 'unsigned' => false, 'comment' => '作成者'),
    'created' => array('type' => 'datetime', 'null' => true, 'default' => null, 'comment' => '作成日時'),
    'modified_user' => array('type' => 'integer', 'null' => true, 'default' => '0', 'unsigned' => false, 'comment' => '更新者'),
    'modified' => array('type' => 'datetime', 'null' => true, 'default' => null, 'comment' => '更新日時'),
    'indexes' => array(
      'PRIMARY' => array('column' => 'id', 'unique' => 1),
      'key' => array('column' => array('key', 'language_id'), 'unique' => 0)
    ),
    'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB')
  );
}

