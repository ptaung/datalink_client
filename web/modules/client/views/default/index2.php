<?php

use yii\helpers\Html;

$js = 'function refresh() {
     $.pjax.reload({container:"#clientprocess"});
     setTimeout(refresh, 5000); // restart the function every 5 seconds
 }
 refresh();';
$this->registerJs($js, $this::POS_READY);

use kartik\grid\GridView;
?>
<?php \yii\widgets\Pjax::begin(['id' => 'clientprocess']); ?>
<?php

$gridColumns = [
    ['class' => 'kartik\grid\SerialColumn'],
    'label',
];
echo GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => $gridColumns,
    'containerOptions' => ['style' => 'overflow: auto'], // only set when $responsive = false
    /*
      'beforeHeader' => [
      [
      'columns' => [
      ['content' => 'Header Before 1', 'options' => ['colspan' => 4, 'class' => 'text-center warning']],
      ['content' => 'Header Before 2', 'options' => ['colspan' => 4, 'class' => 'text-center warning']],
      ['content' => 'Header Before 3', 'options' => ['colspan' => 3, 'class' => 'text-center warning']],
      ],
      'options' => ['class' => 'skip-export'] // remove this row from export
      ]
      ],
     *
     */
    'export' => FALSE,
    'pjax' => true,
    'bordered' => true,
    'striped' => false,
    'condensed' => false,
    'responsive' => true,
    'hover' => true,
    'floatHeader' => true,
    #'floatHeaderOptions' => ['scrollingTop' => $scrollingTop],
    'showPageSummary' => true,
    'panel' => [
        'type' => GridView::TYPE_PRIMARY
    ],
]);
?>
<?php \yii\widgets\Pjax::end(); ?>