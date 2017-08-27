<?php

$script = <<<SKRIPT

    //var url = "http://127.0.0.1/auth/web/index.php/oauth2/token";

     var datajson = {
     'grant_type': 'password',
     'username': '<some login from your user table>',
     'password': '<real pass>',
     'client_id': 'testclient',
     'client_secret': 'testpass'
     };

/*
     $.post(url, {'data': datajson}, function (data, status) {
     alert("Data: " + datajson + "\nStatus: " + status);
     });
     */
    $(function () {

         $.post("http://127.0.0.1/auth/web/index.php/oauth2/token",{'data': datajson},function(data){
            alert(data);
         });
/*
        $.ajax({
            url: "http://127.0.0.1/auth/web/index.php/oauth2/token",
            type: 'post',
            data: {
                grant_type: 'password',
                username: '<some login from your user table>',
                password: '<real pass>',
                client_id: 'testclient',
                client_secret: 'testpass'
            },
            success: function (data) {
                alert(data);
            }
        });
  */
    });
SKRIPT;
$this->registerJs($script);
