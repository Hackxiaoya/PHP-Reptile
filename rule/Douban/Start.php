<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018.8.25
 * Time: 3:33
 */
header("Content-Type: text/html;charset=GB2312");
require '../../vendor/autoload.php';
use QL\QueryList;
use QL\Ext\DisguisePlugin;


echo "---------------------------\r\n";
echo "|       豆瓣采集脚本      |\r\n";
echo "|       作者：yyyvy       |\r\n";
echo "|       QQ：76836785      |\r\n";
echo "|       版本：v1.0.1      |\r\n";
echo "---------------------------\r\n";

fwrite(STDOUT,'请输入群组ID： ');
$groudid = trim(fgets(STDIN));

fwrite(STDOUT,'请输入页数，例1-20:');
$writepage = trim(fgets(STDIN));
echo "群组：".$groudid."页码： ".$writepage."\r\n";

$writepage = explode('-',$writepage);
$page_start = $writepage[0];    //开始页数
$page_end = $writepage[1];  //结束页
_nextpage();

//翻页采集
function _nextpage(){
    if($GLOBALS['page_start'] <= $GLOBALS['page_end']){
        echo "正在采集第".$GLOBALS['page_start']."页！\r\n";
        $result = _Start($GLOBALS['groudid'],$GLOBALS['page_start']);
        if($result){
            $GLOBALS['page_start']++;
            _nextpage();
        }else{

        }
    }else{
        echo "--------------所有采集完成！\r\n";
    }
}

//开启采集
function _Start($groudid,$page){
    $datalist = _CollectList($groudid,$page);
    foreach ($datalist as $value){
        $list = _ReadFile();
        if(!in_array($value['link'],$list)){
            _WriteFile($value['link']);
            $CollectContent = _CollectContent($value['link']);
            if($CollectContent){
                echo $value['link']." ----- 采集完成！\r\n";
            }else{
                echo $value['link']." ----- 采集失败！\r\n";
            }
        }else{
            echo $value['link']." ----- 已采集过！\r\n";
        }
    }
    return true;
}

//获取采集列表
function _CollectList($groudid,$page){

    if($page == 1){
        $page = 0;
    }else{
        $page = $page*25-25;
    }
    $url = 'https://www.douban.com/group/'.$groudid.'/discussion?start='.$page;

    // 采集规则
    $rules = [
        'link' => ['.olt .title a','href']
    ];

    //设置伪造UA、IP
    $ql = QueryList::getInstance();
    $ql->use(DisguisePlugin::class);
    $ql->use(DisguisePlugin::class,'disguiseIp','disguiseUa');
    /*$data = $ql->get($url,[],[
        'proxy' => [
            'http' => '115.213.234.192:3456',
            'https' => '115.213.234.192:3456',
        ]
    ])->rules($rules)->query()->getData()->All();*/
    $data = $ql->get($url)->rules($rules)->query()->getData()->All();
    return $data;
}

//采集文章内容
function _CollectContent($url){
    // 采集规则
    $rules = [
        'src' => ['.topic-richtext img','src']
    ];

    //设置伪造UA、IP
    $ql = QueryList::getInstance();
    $ql->use(DisguisePlugin::class);
    $ql->use(DisguisePlugin::class,'disguiseIp','disguiseUa');
    $data = $ql::get($url)->rules($rules)->query()->getData()->All();
    foreach ($data as $value) {
        _DownImg($value['src']);
    }
    return true;
}

//写入采集文件
function _WriteFile($str){
    file_put_contents('HaveCollect.txt', $str."\r\n", FILE_APPEND);
}

//读取已采集文件
function _ReadFile(){
    $file_path  = "HaveCollect.txt";
    $list = [];
    if(file_exists($file_path )){
        $str = file_get_contents($file_path);//将整个文件内容读入到一个字符串中
        $list = explode("\r\n", $str);
    }
    return $list;
}

//下载图片
function _DownImg($url){
    $ch = curl_init();  //初始化一个curl句柄
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);   //HTTPS
    curl_setopt($ch, CURLOPT_HEADER, 0);  //启用时会将头文件的信息作为数据流输出。
    curl_setopt($ch,CURLOPT_TIMEOUT,60); //设置超时时间
    $res = curl_exec($ch);  //执行curl
    curl_close($ch);  //关闭curl会话
    $fp = fopen('images/'.md5($url).'.jpg', 'a'); //创建文件
    fwrite($fp, $res);  //写入数据
    fclose($fp);  //关闭句柄
}