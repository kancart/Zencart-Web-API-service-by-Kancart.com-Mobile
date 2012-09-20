<?php
if ($user_lang == 'en') {
    $lang_testing = ' Testing your plugin  ...';
    $lang_tryagain = 'Try Again';
    $lang_test_failed = 'Sorry, test failed.   ';
    $lang_ftp_intro = 'We do not have the permission to upgrade your plugin. Please make sure the following paths have write access: ';
    $lang_general_intro = '';
    $lang_show_detail_log = 'Show Detail Log';
    $lang_upgrade_now = 'Upgrade Now';
    $lang_or = '-OR-';
    $lang_youcanuseftp = 'You can offer the ftp for us to upgrade:';
    $lang_access_ok = 'Access OK! You can upgrade your plugin now:';
    $lang_read_more = 'You can see detail log here:';
    $lang_upgrade_success = 'Congratulations! You have successfully upgraded kancartplugin. ';
    $lang_img_url = "http://www.kancart.com/images/en/upgrade_loader_bar.gif";
    $lang_upgrade_failed = 'Sorry, upgrade failed.  ';
    $lang_path_intro = 'Please enter your zencart path under your FTP';
} else {
    $lang_testing = ' 正在检测您的插件  ...';
    $lang_tryagain = '再试一次';
    $lang_test_failed = '检测插件过程中遇到问题。   ';
    $lang_ftp_intro = '升级插件缺少权限。请确保以下路径为可写： ';
    $lang_general_intro = '';
    $lang_show_detail_log = '查看详细日志';
    $lang_upgrade_now = '立刻升级';
    $lang_or = '或者';
    $lang_youcanuseftp = '您可以提供FTP地址来进行升级：';
    $lang_access_ok = '检测完毕！ 您可以立即升级您的插件：';
    $lang_upgrade_success = '您成功地升级了kancart插件！ ';
    $lang_read_more = '您可以在这里查看详细日志：';
    $lang_upgrade_failed = '很抱歉，更新失败了。 ';
    $lang_img_url = "http://www.kancart.com/images/zh-c/upgrade_loader_bar.gif";
    $lang_path_intro = '请输入您FTP下的zencart路径';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html;  charset=utf-8" />
        <title>Zencart Auto Upgrade</title>
        <script src="http://www.kancart.com/js/jquery-1.4.1.min.js" type="text/javascript"></script>
        <style type="text/css">
            .upgrade_now {
                display: block; 
                height: 36px; 
                width: 155px;
                background: url(http://www.kancart.com/images/en/upgrade_now_btn.png) no-repeat 0px 0px; 
                color: #d84700;
                border-style: none;}
            .upgrade_now:hover {
                background: url(http://www.kancart.com/images/en/upgrade_now_btn.png) no-repeat 0px -36px;
                border-style: none;
            }
        </style>
    </head>
    <body>
        <script>       
            function ftp_upgrade(){
                $.ajax({
                    url: 'kc_server.php',
                    dataType:'json',
                    type:'post',  
                    data:{
                        'action':'ftp_mode',
                        'host':$("#host")[0].value,
                        'port':$("#port")[0].value,
                        'path':$("#path")[0].value,
                        'username':$("#username")[0].value,
                        'password':$("#password")[0].value                        
                    },
                    beforeSend: function(XMLHttpRequest){
                        //ShowLoading();
                        $("#ftp_upgrade_btn")[0].innerHTML = '<img src="<?php echo $lang_img_url ?>"/>';
                    },
                    success: function(json){
                        log_content = "";
                        logs = json;
                        for(var key in logs['logs']){
                            log_content = log_content +key + "\n";
                            for(var i in logs['logs'][key]){
                                log_content = log_content + logs['logs'][key][i] + "\n";
                            }
                        }
                        if(logs['result'] == 'success'){
                            $("#result_show")[0].innerHTML = "<?php echo $lang_upgrade_success ?>";
                        }else{
                            $("#result_show")[0].innerHTML = "<?php echo $lang_upgrade_failed ?>";
                        }
                        $("#log_info")[0].innerHTML = log_content;
                        $("#maincontain")[0].innerHTML =  $("#result_contain")[0].innerHTML;
                    },
                    complete: function(XMLHttpRequest, textStatus){
                        //HideLoading();
                    },
                    error: function(){
                        //alert('Error loading data');
                    }
                });
            }
            
            function general_upgrade(){
                $.ajax({
                    url: 'kc_server.php?action=php_mode',
                    dataType:'json',
                    type:'get',   
                    beforeSend: function(XMLHttpRequest){
                        //ShowLoading();
                        $("#general_upgrade_btn")[0].innerHTML = '<img src="<?php echo $lang_img_url ?>"/>';
                    },
                    success: function(json){
                        log_content = "";
                        logs = json;
                        for(var key in logs['logs']){
                            log_content = log_content +key + "\n";
                            for(var i in logs['logs'][key]){
                                log_content = log_content + logs['logs'][key][i] + "\n";
                            }
                        }
                        if(logs['result'] == 'success'){
                            $("#result_show")[0].innerHTML = "<?php echo $lang_upgrade_success ?>";
                        }else{
                            $("#result_show")[0].innerHTML = "<?php echo $lang_upgrade_failed ?>";
                        }
                        $("#log_info")[0].innerHTML = log_content;
                        $("#maincontain")[0].innerHTML =  $("#result_contain")[0].innerHTML;
                    },
                    complete: function(XMLHttpRequest, textStatus){
                        //HideLoading();
                    },
                    error: function(){
                        //alert('Error loading data');
                    }
                });
            }
            
            function test_plugin_status(){
                $.ajax({
                    url: 'kc_server.php?action=access_check',
                    dataType:'json',
                    type:'get',   
                    beforeSend: function(XMLHttpRequest){
                        //ShowLoading();
                    },
                    success: function(json){
                        if(json['result'] == true){
                            $("#maincontain")[0].innerHTML = $("#general_mode")[0].innerHTML;
                        }else{
                            $("#maincontain")[0].innerHTML = $("#ftp_mode")[0].innerHTML;
                            test_result_content = "";
                            for(var i in json['info']){
                                test_result_content = test_result_content + json['info'][i] +"<br />";
                            }
                            $("#file_need_access")[0].innerHTML = test_result_content;
                        }
                    },
                    complete: function(XMLHttpRequest, textStatus){
                        //HideLoading();
                    },
                    error: function(){
                        //alert('Error loading data');
                        $("#test_text")[0].innerHTML = '<?php echo $lang_test_failed ?><input type="button" value="<?php echo $lang_tryagain ?>" onclick="location.href=\'\'"/>';                    }
                });
            }        
            
            default_path_intro = "<?php echo $lang_path_intro ?>";
            function pre_enter(obj){
                if (obj.value == "") {
                    obj.style.color = "#a2a2a2";
                    obj.value = default_path_intro;
                }
            }
            
            function enter_now(obj){
                obj.style.color = "#000000";
                if(obj.value == default_path_intro){
                    obj.value = "";
                }
            }
            onload = function(){
                test_plugin_status();
            }
        </script>

        <div id="container" style="width:900px;margin:auto;">
            <div class="title" style="font-size: 30px;color:#93C905;height:60px;border-bottom: 1px solid #999;">
                <div id="logo" style="margin:20px;vertical-align: middle;display:table-cell;">
                    <img src="http://www.kancart.com/images/kancart_logo.gif"/>
                    <span style="margin-left:230px;margin-top:-35px;display:block">Zencart Plugin Auto Upgrade</span>
                </div>                
            </div>
            <div id="maincontain" style="width:100%;margin-top:20px;">
                <div style="width:600px;height:100px;margin:30px auto;text-align: center;border: 1px solid #aaa;color:#93C905;font-weight: bolder;">
                    <div style="height:30px;margin-top:35px;;font-size: 20px;" id="test_text"><?php echo $lang_testing ?></div>
                </div>
            </div>
            <div id="right_check" style="display:none;">
                <div id="ftp_mode">
                    <div id="ftp_intro">
                        <?php echo $lang_ftp_intro ?><input type="button" value="<?php echo $lang_tryagain ?>" onclick="location.href=''"/>
                        <div id="file_need_access" style="margin:15px;color:#990000;width:848px;border:1px solid #ddd;padding:10px;overflow-x:auto; "></div>
                        <?php echo $lang_or ?> 
                    </div><br />
                    <?php echo $lang_youcanuseftp ?>
                    <div id="ftp_form" style="margin:15px;width:828px;;border:1px solid #ddd;padding:20px;">
                        <table style="float:left;">
                            <tr>
                                <td width="80px;">   
                                </td>
                                <td>
                                </td>
                            </tr>
                            <tr>
                                <td>   
                                    Host:
                                </td>
                                <td>
                                    <input name="host" id="host" style="width:170px;" value=""/><span style="margin:0 10px;"> port: </span><input name="port" id="port" style="width:50px;" value="21"/>
                                </td>
                            </tr>
                            <tr>
                                <td>   
                                    Path:
                                </td>
                                <td>
                                    <input name="path" id="path" style="width:300px;color:#a2a2a2;" value="<?php echo $lang_path_intro ?>" onblur="pre_enter(this);" onfocus="enter_now(this)"/>
                                </td>
                            </tr>
                            <tr>
                                <td>   
                                    Username:
                                </td>
                                <td>
                                    <input name="username" id="username" style="width:300px;" value=""/>
                                </td>
                            </tr>
                            <tr>
                                <td>   
                                    Password:
                                </td>
                                <td>
                                    <input name="password" id="password" style="width:300px;" value="" type="password"/>
                                </td>
                            </tr>
                        </table>
                        <div id="ftp_upgrade_btn" style="float:left;margin:40px 0 0 150px;" >
                            <input type="button"  class="upgrade_now"  onclick="ftp_upgrade();" onfocus="this.blur()"/> 
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                </div>
                <div id="general_mode">
                    <div id="general_intro" style="float:left;padding-top:10px;padding-right:8px;">
                        <?php echo $lang_access_ok ?>
                    </div>
                    <div id="general_upgrade_btn" style="float:left" >
                        <input type="button" class="upgrade_now" onfocus="this.blur()" onclick="general_upgrade();"/> 
                    </div>
                    <div style="clear:both;"></div>
                </div>
            </div>
            <div id="result_contain" style="display:none;">
                <div style="float:left;padding-top:5px;"><span id="result_show" style="font-size:18px;color:#93C905;"></span><span style="font-size:14px;"><?php echo $lang_read_more ?></span></div>
                <input type="button" style="float:left;margin-left: 5px;" value="<?php echo $lang_show_detail_log; ?>" onclick="$('#log_detail')[0].style.display = 'block';"/> 
                <div style="clear:both;"></div>
                <div id="log_detail" style="display:none;">
                    <textarea id="log_info" style="width:600px;height:200px;margin:15px;padding:10px;"></textarea>
                </div>
            </div>
        </div>
    </body>
</html>

