<?php echo $this->NetCommonsHtml->script('/updates/js/updates.js'); /*updates.js の読み込み*/ ?>
<?php echo $this->NetCommonsHtml->css('/updates/css/updates.css'); /*updates.css の読み込み*/ ?>

<?php
/**
  *Totals view temmplate
  *
  * ・Migration/●●●_records.php で定義されている、
  * 　default_action で、最初に起動するController&関数を指定している。
  * ・上記マイグレーションファイルで定義している 
  *	 defeult_setting_action では、ブロックIDなどを設定するController&関数をよびだしている
  * ・UpdateBlocksController でset された変数は、ViewVarsに格納されるので、Update/view.ctp 内で 
  * 　呼び出し・利用が可能（View側で使用しなくても問題ない）
  */
?>

<!-- divでangularJSを使いコントローラの指定している場合は、親要素のコントローラ→子要素のコントローラ という順番で起動する -->

<!-- updates.js の Updates関数を呼び出す定義。レンダリング時に起動する -->
<div ng-controller="Updates" ng-init="init(
	 <?php echo Current::read('Block.id') ?>,
	 <?php echo Current::read('Frame.id') ?>
	 )">

	<!-- updates.js の UpdateFile.edit関数を呼び出す定義。Viewのレンダリング時に起動する -->
	<div class="updateFiles form" ng-controller="UpdateFile.edit" ng-init="init(
	 <?php echo Hash::get($this->request->data, 'UpdateFileTree.parent_id', 0); ?>
	 )"
		id="updateFileForm_<?php echo Current::read('Frame.id') ?>"
	>

		<?php /* フォームの作成。ボタンを押すと（submit)フォームの中身が request(request->data) に送られる */
			echo $this->NetCommonsForm->create(
				'UpdateFile',
				array(
					'inputDefaults' => array(
						'div' => 'form-group',
						'class' => 'form-control',
						'error' => false,
					),
					'div' => 'form-control',
					'novalidate' => true,
					'type' => 'file',
				)
			);
		?>

		<div>
			<!-- アップロードを選ぶための参照ボタン＆選択したファイル名を表示するテキストビューを作成 -->
			<!-- おそらくuploadFileのなかでinputを設置し、Form終了時にPostを実施している -->
			<?php 
				echo $this->NetCommonsForm->uploadFile(
				'file',
				['label' => __d('cabinets', 'File'), 'remove' => false]
			) ?>
		</div>

		<?php echo $this->Workflow->buttons('UpdateFile.status'); /* キャンセル/一時保存/決定ボタンの作成 */ ?>

		<?php echo $this->NetCommonsForm->end() /* フォーム終わり */ ?>

		<!-- addLink() 関数を呼び出しているが、実際に動くのはControllerにいる add() 関数 -->
		<!-- UpdateController.add() は render()で指定をしない限り、関数名と同じViewを探しにいく為
			 View内のaddLink() → Controller/add() → View/add.ctp	という流れ    　-->
		<div class="pull-right">
			<?php echo $this->Button->addLink(
						'',
						null,
						['tooltip' => 'testButton']
					); ?>
		</div>
	</div>
</div>
	

