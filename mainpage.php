<?php
$proxy_url = getenv('PROXY_URL') ?: 'http://proxy.g0v.ronny.tw/';
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>判決書 Parser</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
</head>
<body>
<form method="get" id="form">
    URL: <input type="text" size="100" id="url" placeholder="請貼上判決的友善列印頁網址，或者是判決書第一行也就是像「臺灣臺北地方法院刑事判決 100年度某字5566號」">
    <button type="submit">GET</button>
</form>
<div id="message"></div>
分享網址: <input type="text" readonly="readonly" id="share-url" size="90"><br>
短網址: <input type="text" readonly="readonly" id="short-url" size="90"><br>
Viewer網址: <input type="text" readonly="readonly" id="viewer-url" size="90"><br>
Editor網址: <input type="text" readonly="readonly" id="editor-url" size="90"><br>
<a href="#" id="full-version">維基百科 InfoBox 及 JSON 轉換版</a>
<div id="full-area" style="display:none">
<div id="wiki-area">
Wiki Infobox:
<textarea style="width:100%; height: 300px" id="wiki"></textarea>
</div>
Result:
<textarea style="width:100%; height: 500px" id="textarea"></textarea>
</div>
<script src="js/judge_parse.js"></script>
<script>
$('#full-version').click(function(e){
    e.preventDefault();
    $('#full-area').toggle();
});
$('#form').submit(function(e){
        e.preventDefault();
        var url = $('#url').val();
        var proxy_url = <?= json_encode($proxy_url) ?> + '/proxy.php?url=' + encodeURIComponent(url);

        if (url.match('/FJUD/FJUDQRY03_1.aspx')) { // 單一判決頁
            var type = 'FJUDQRY03_1';
        } else if (!url.match('^http')) {
            try {
                var result = parse_court(url, empty_result());
            } catch (s) {
                $('#message').text('字號無法判斷: ' + s);
                return;
            }
            var url = 'http://jirs.judicial.gov.tw/FJUD/FJUDQRY02_1.aspx?cw=1&v_court=' + encodeURIComponent(result['法院'].ID + ' ' + court[result['法院'].ID]) + '&v_sys=' + result['裁判種類'].ID + '&jud_year=' + result['裁判字號']['年'] + '&jud_case=' + encodeURIComponent(result['裁判字號']['字']) + '&jud_no=' + result['裁判字號']['號'] + '&jud_title=&keyword=&sdate=19110101&edate=99991231&searchkw=';
            $('#share-url').val(url);
            url = 'http://judicial.ronny.tw/' + encodeURIComponent(result['法院'].ID) + '/' + result['裁判種類'].ID + '/' + result['裁判字號']['年'] + '/' + encodeURIComponent(result['裁判字號']['字']) + '/' + result['裁判字號']['號'];
            $('#short-url').val(url);
            $('#message').text('已產生可分享此案件連結，詳細資訊可入內透過友善列印取出');
            $('#textarea').text(JSON.stringify(result, true, 2));
            $('#wiki-area').hide();
            return;
        } else if (url.match('/FJUD/PrintFJUD03_0.aspx')) { // 友善列印頁
            var type = 'PrintFJUD03_0';
        } else if (url.match('/FJUD/HISTORYSELF.aspx')) { // 歷審頁
            var type = 'HISTORYSELF';
        } else {
            $('#message').text('目前只允許處理單一判決頁、友善列印頁或是歷審案件查詢頁');
            return;
        }
        
        $('#message').text('讀取中...');
        $.get(proxy_url, function(text){
            var result;
            $('#message').text('');
            try {
                if ('FJUDQRY03_1' == type) {
                        result = parse_from_page(text);
                } else if ('PrintFJUD03_0' == type) {
                    result = parse_from_print_page(text, url);
                } else if ('HISTORYSELF' == type) {
                    result = parse_history(text);
                    $('#textarea').text(JSON.stringify(result, true, 2));
                    $('#wiki-area').hide();
                    return;
                }
            } catch (s) {
                $('#message').text('字號無法判斷: ' + s);
                throw s;
                return;
            }
            $('#textarea').text(JSON.stringify(result, true, 2));
            $('#wiki-area').show();
            $('#wiki').text(to_wiki_infobox(result));
            $('#share-url').val(result["連結"]["列表"]);
            $('#short-url').val(result["連結"]["列表短網址"]);
            $('#viewer-url').val(result["連結"]["列表短網址"] + '/' + result['裁判日期'].SOURCE + '/' + result['jcheck'] + '/viewer');
            $('#editor-url').val(result["連結"]["列表短網址"] + '/' + result['裁判日期'].SOURCE + '/' + result['jcheck'] + '/editor');
        });
});
</script>
</body>
</html>
