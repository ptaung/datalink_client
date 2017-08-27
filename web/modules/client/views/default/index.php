<?php

use yii\helpers\Url;

$url = Url::to(['getlogprocess']);
$js = '
function fmt(value) {
        value = isNaN(value) ? 0 : value;
        var num = parseFloat(value);
        var bytes = num.toFixed(2);
        if (bytes < 1024) {
            return bytes + " B";
        } else if (bytes < 1024 * 1024) {
            return roundToTwo(bytes / 1024) + " KB";
        } else if (bytes < 1024 * 1024 * 1024) {
            return roundToTwo(bytes / 1024 / 1024) + " MB";
        } else {
            return roundToTwo(bytes / 1024 / 1024 / 1024) + " GB";
        }
}
function format(value, t) {
        var variable;
        if (typeof t === "undefined")
            t = 0;
        if (typeof value === "undefined")
            variable = t;
        else if (value == "NaN")
            variable = t
        else
            variable = value;
        return variable;
}
function roundToTwo(num) {
        return +(Math.round(num + "e+2") + "e-2");
}
function refresh() {
      $.ajax({
                url: "' . $url . '",
                    dataType: "json",
                success: function(data) {
                    $("#process_all").html(data.process_all);
                    $("#process_current").html(data.process_current);
                    $("#process_table").html(data.process_table);
                    $("#upload_current_split").html(data.upload_current_split);
                    $("#upload_current_process").html(data.upload_current_process);
                    $("#txtsize").html(fmt(data.txtsize));
                    $("#zipsize").html(fmt(data.zipsize));
                    $("#syncprocess").html(format(((data.process_current * 100) / data.process_all).toFixed(2)) + "% (<b>" + format(data.process_all) + "/" + format(data.process_current) + "</b> ) " + format(data.process_table, ""));
                    $("#syncpersent").css("width", ((data.process_current * 100) / data.process_all).toFixed(2) + "%");
                    $("#syncsubprocess").html(format(((data.upload_current_process * 100) / data.upload_current_split).toFixed(2)) + "% (<b>" + format(data.upload_current_split) + "/" + format(data.upload_current_process) + "</b> ) " + format(data.process_table, ""));
                    $("#syncsubpersent").css("width", ((data.upload_current_process * 100) / data.upload_current_split).toFixed(2) + "%");

                }
        })
     setTimeout(refresh, 3000); // restart the function every 5 seconds
 }
 refresh();';
$this->registerJs($js, $this::POS_READY);
?>


<br>
<br>
<br>
<div class="panel panel-primary">
    <div class="panel-heading">
        <div class="">
            <div class="btn btn-primary pull-right" id='sync'>Click ส่งข้อมูล</div>
            <div class="btn btn-success pull-right" id='sync2'>ONLINE</div>
        </div>
        <h3 class="panel-title">NODE-SERVICE <?= \Yii::$app->params['hospital_hospcode'] ?>
            <p class="small">ข้อมูลผ่านระบบเว็บเซอร์วิสระหว่างหน่วยบริการกับส่วนกลาง</p>
        </h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class='col-md-12'>
                สถานะการทำงาน <span id="syncprocess">0%</span>
                <div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-info progress-bar-striped active" id="syncpersent" style="width: 0%;">
                            <span class="">Complete (success)</span>
                        </div>
                    </div>
                </div>
                กำลังส่ง <span id="syncsubprocess">0%</span>
                <div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-success progress-bar-striped active" id="syncsubpersent" style="width: 0%;">
                            <span class="">Complete (success)</span>
                        </div>
                    </div>
                </div>
                Error Messages :: <span class="syncErrorCount text-danger"></span>
                <div class="well well-sm small">
                    <div class="small" id="syncError"></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">ข้อมูลการเชื่อมต่อฐานข้อมูลหน่วยบริการ</h3>
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <span class="label label-success pull-right" id="syncStart">127.0.0.1</span>
                            Host ฐานข้อมูล
                        </li>
                        <li class="list-group-item">
                            <span class="label label-success pull-right" id="syncFinish">hosxp_pcu</span>
                            ชื่อฐานข้อมูล
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="tableAll">5.1.47-rel11.2-log</span>
                            เวอร์ชันฐานข้อมูล
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="tableSync">000</span>
                            จำนวน Tables ที่ส่ง
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="tableSync"> Rows</span>
                            จำนวน SplitLimit ที่ส่ง
                        </li>
                        <li class="list-group-item">
                            <span class="label label-danger pull-right" id="syncMessage">-</span>
                            Message
                        </li>
                        <li class="list-group-item">
                            <span class="label label-danger pull-right syncErrorCount">000</span>
                            Error
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">การเชื่อมต่อเว็บเซอร์วิส</h3>
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <span class="label label-success pull-right ">
                                <span class="glyphicon glyphicon-cloud" aria-hidden="true"></span> <?= \Yii::$app->params['webService_baseUrl'] ?>
                            </span>
                            Ws-URL
                        </li>
                        <li class="list-group-item">
                            <span class="label label-danger pull-right "><span class="glyphicon glyphicon-lock" aria-hidden="true"></span> AES-256-bit</span>
                            Data Encryption
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="txtsize1">0 MB</span>
                            ปริมาณข้อมูล
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="zipsize1">0 MB</span>
                            ปริมาณข้อมูลบีบอัด
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="uploadsize1">0 MB</span>
                            ปริมาณข้อมูลที่ Upload
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="uploadtime1">431</span>
                            จำนวน Tables Sync
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="usetime1">2016-11-03 12:50:08</span>
                            ช่วงที่ต้องส่งครั้งต่อไป
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">การประมวลผล</h3>
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <span class="label label-success pull-right "><span class="glyphicon glyphicon-cloud" aria-hidden="true"></span> </span>
                            Webservice provider
                        </li>
                        <li class="list-group-item">
                            <span class="label label-danger pull-right "><span class="glyphicon glyphicon-lock" aria-hidden="true"></span> AES-256-bit</span>
                            Data Encryption
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="process_all">0</span>
                            จำนวนตารางที่ต้องส่ง
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="zipsize">0 MB</span>
                            ปริมาณข้อมูลบีบอัด
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="txtsize">0 MB</span>
                            ปริมาณข้อมูล
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="uploadtime">-</span>
                            ใช้เวลา Upload ข้อมูล
                        </li>
                        <li class="list-group-item">
                            <span class="label label-info pull-right" id="usetime">-</span>
                            ใช้เวลาทั้งหมด
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

