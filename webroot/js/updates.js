/**
 *  Multidatabases DatabaseContents JS
 *  コンテンツ編集関連フロントエンド処理
 *  webroot/js/edit_multi_database_contents.js
 *
 *  @author ohno.tomoyuki@ricksoft.jp (Tomoyuki OHNO/Ricksoft, Co., Ltd.)
 *  @link http://www.netcommons.org NetCommons Project
 *  @license http://www.netcommons.org/license.txt NetCommons License
 */

NetCommonsApp.controller('Updates',
    ['$scope', function($scope) {
      $scope.folder = [];

      console.log("updates.js / updates()"); //debug
      $scope.init = function(blockId, frameId) {
        $scope.frameId = frameId;
        $scope.blockId = blockId;
      };

      $scope.folderPath = [];


    }]
);

NetCommonsApp.controller('UpdatesContentEdit',
    ['$scope', 'NetCommonsWysiwyg', function($scope, NetCommonsWysiwyg) {
      console.log("updates.js / UpdatesContentEdit()"); //debug

      $scope.UpdateseContent = [];
      $scope.UpdatesMetadata = [];

      /**
       * tinymce
       *
       * @type {object}
       */
      $scope.tinymce = NetCommonsWysiwyg.new();

      /**
       * initialize
       *
       * @param {Object} data
       * @type {object}
       */
      $scope.initialize = function(data) {
        if (data.UpdatesContent) {
          $scope.UpdatesContent = data.UpdatesContent;
          $scope.UpdatesMetadata = data.UpdatesMetadata;
        }
      };
    }]);


NetCommonsApp.controller('Updates.path',
    ['$scope', 'NC3_URL', function($scope, NC3_URL) {
      console.log("updates.js / Updates.path()"); //debug

      $scope.init = function(folderPath, pageUrl) {

        // 一つ目だけPageUrlにする
        angular.forEach(folderPath, function(value, key) {
          if (key == 0) {
            value['url'] = pageUrl;
          } else {
            value['url'] = NC3_URL + '/updates/update_files/index/' +
                $scope.blockId + '/' + value.UpdateFile.key + '?frame_id=' + $scope.frameId;
          }

          $scope.folderPath[key] = value;
        });
      };
    }]
);

NetCommonsApp.controller('UpdateFile.addFile',
    ['$scope', '$filter', 'NetCommonsModal', '$http', 'NC3_URL',
      function($scope, $filter, NetCommonsModal, $http, NC3_URL) {
        console.log("updates.js / UpdateFile.addFile()"); //debug
        $scope.init = function(parentId) {
          $scope.parent_id = parentId;
        };

        $scope.addFile = function() {
          console.log("js/controller/UpdateFile.addFile");
          var blockId = $scope.blockId;
          var frameId = $scope.frameId;
          var url = NC3_URL + '/updates/update_files_edit/add/' + blockId;
          if ($scope.parent_id > 0) {
            url = url + '/parent_id:' + $scope.parent_id;
          }
          url = url + '?frame_id=' + frameId;
          var modal = NetCommonsModal.show($scope, 'UpdateFile.addFileModal', url);
        };
      }
    ]
);

/**
 * AddFile Modal
 */
NetCommonsApp.controller('UpdateFile.addFileModal',
    ['$scope', '$uibModalInstance', function($scope, $uibModalInstance) {
      console.log("updates.js / UpdateFile.addFileModal()"); //debug

      /**
       * dialog cancel
       *
       * @return {void}
       */
      $scope.cancel = function() {
        $uibModalInstance.dismiss('cancel');
      };
    }]
);



NetCommonsApp.controller('Updates.FolderTree',
    ['$scope', function($scope) {
      console.log("updates.js / UpdateFile.FolderTree()"); //debug

      $scope.folder = [];

      $scope.init = function(currentFolderPath) {
        angular.forEach(currentFolderPath, function(value, key) {
          $scope.folder[value] = true;
        });
      };

      $scope.toggle = function(folderId) {
        $scope.folder[folderId] = !$scope.folder[folderId];
      };
    }]
);



NetCommonsApp.controller('UpdateFile.index',
    ['$scope', 'NetCommonsModal', '$http', 'NC3_URL',
      function($scope, NetCommonsModal, $http, NC3_URL) {
        console.log("updates.js / UpdateFile.index()"); //debug
        $scope.moved = {};
        $scope.init = function(parentId) {
          $scope.parent_id = parentId;
        };

        $scope.moveFile = function(updateFileKey, isFolder, data) {
          var modal = NetCommonsModal.show(
              $scope, 'UpdateFile.edit.selectFolder',
              NC3_URL + '/updates/update_files_edit/select_folder/' + $scope.blockId +
              '/' + updateFileKey + '?frame_id=' + $scope.frameId);
          modal.result.then(function(parentId) {

            if ($scope.parent_id != parentId) {
              // 移動を裏で呼び出す
              // get token
              $http.get(NC3_URL + '/net_commons/net_commons/csrfToken.json')
                  .then(function(response) {
                    var token = response.data;
                    var post = data;
                    post._Token.key = token.data._Token.key;

                    post.UpdateFileTree.parent_id = parentId;
                    //POSTリクエスト
                    var url = NC3_URL + '/updates/update_files_edit/move/' + $scope.blockId +
                        '/' + updateFileKey + '?frame_id=' + $scope.frameId;
                    $http.post(
                        url,
                        $.param({_method: 'POST', data: post}),
                        {cache: false,
                          headers:
                          {'Content-Type': 'application/x-www-form-urlencoded'}
                        }
                    ).then(
                        function(response) {
                          var data = response.data;
                          if (isFolder) {
                            // フォルダを動かしたらリロード
                            location.reload();
                          } else {
                            $scope.flashMessage(data.name, data.class, data.interval);
                            // 違うフォルダへ移動なので、今のフォルダ内ファイル一覧から非表示にする
                            $scope.moved[updateFileKey] = true;
                          }
                        },
                        function(response) {
                          var data = response.data;
                          // エラー処理
                          $scope.flashMessage(data.name, 'danger', 0);
                        });
                  },
                  function(response) {
                    //Token error condition
                    // エラー処理
                    var data = response.data;
                    $scope.flashMessage(data.name, 'danger', 0);
                  });
            }
          });
        };

        $scope.unzip = function(updateFileKey, data) {
          // unzipを裏で呼び出す
          // get token
          $http.get(NC3_URL + '/net_commons/net_commons/csrfToken.json')
              .then(function(response) {
                var token = response.data;
                var post = data;
                post._Token.key = token.data._Token.key;

                //POSTリクエスト
                var url = NC3_URL + '/s/_files_edit/unzip/' + $scope.blockId +
                    '/' + FileKey + '?frame_id=' + $scope.frameId;
                $http.post(
                    url,
                    $.param({_method: 'POST', data: post}),
                    {cache: false,
                      headers:
                      {'Content-Type': 'application/x-www-form-urlencoded'}
                    }
                ).then(
                    function(response) {
                      var data = response.data;
                      if (data.class == 'success') {
                        // エラーがなかったらリロードする
                        location.reload();
                      } else {
                        $scope.flashMessage(data.name, data.class, 0);
                      }
                    },
                    function(response) {
                      // エラー処理
                      var data = response.data;
                      $scope.flashMessage(data.name, data.class, 0);
                    });
              },
              function(response) {
                //Token error condition
                // エラー処理
                var data = response.data;
                $scope.flashMessage(data.name, 'danger', 3);
              });
        };
      }]
);

/**
 * Cabinets edit Javascript
 */
NetCommonsApp.controller('UpdateFile.edit',
    ['$scope', '$filter', 'NetCommonsModal', '$http', 'NC3_URL',
      function($scope, $filter, NetCommonsModal, $http, NC3_URL) {
        console.log("updates.js / UpdateFile.edit()"); //debug
        $scope.init = function(parentId, fileKey) {
          $scope.parent_id = parentId;
          $scope.parent_id = parentId;
          $scope.fileKey = fileKey;
        };

        $scope.showFolderTree = function() {

          var selectFolderUrl = NC3_URL + '/updates/update_files_edit/select_folder/' +
              $scope.blockId + '/';
          selectFolderUrl = selectFolderUrl + $scope.fileKey;
          // 新規作成時はfileKeyがないのでparent_idで現在位置を特定
          selectFolderUrl = selectFolderUrl + '/parent_id:' + $scope.parent_id;
          selectFolderUrl = selectFolderUrl + '?frame_id=' + $scope.frameId;

          var modal = NetCommonsModal.show($scope, 'UpdateFile.edit.selectFolder',
              selectFolderUrl);
          modal.result.then(function(parentId) {
            $scope.parent_id = parentId;

            // 親ツリーIDが変更されたので、パス情報を取得しなおす。
            //  Ajax json形式でパス情報を取得する

            var url = NC3_URL + '/updates/update_files_edit/get_folder_path/' +
                $scope.blockId + '/tree_id:' + $scope.parent_id + '?frame_id=' + $scope.frameId;

            $http({
              url: url,
              method: 'GET'
            })
                .success(function(data, status, headers, config) {
                  var result = [];
                  angular.forEach(data['folderPath'], function(value, key) {
                    value['url'] = NC3_URL + '/updates/update_files/index/' +
                        $scope.blockId + '/' + value.UpdateFile.key +
                        '?frame_id=' + $scope.frameId;

                    result[key] = value;
                  });
                  $scope.folderPath = result;
                })
                .error(function(data, status, headers, config) {
                  $scope.flashMessage(data.name, 'danger', 0);
                });
          });
        };

      }]
);