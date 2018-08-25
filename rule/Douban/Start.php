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
echo "|       ����ɼ��ű�      |\r\n";
echo "|       ���ߣ�yyyvy       |\r\n";
echo "|       QQ��76836785      |\r\n";
echo "|       �汾��v1.0.1      |\r\n";
echo "---------------------------\r\n";

fwrite(STDOUT,'������Ⱥ��ID�� ');
$groudid = trim(fgets(STDIN));

fwrite(STDOUT,'������ҳ������1-20:');
$writepage = trim(fgets(STDIN));
echo "Ⱥ�飺".$groudid."ҳ�룺 ".$writepage."\r\n";

$writepage = explode('-',$writepage);
$page_start = $writepage[0];    //��ʼҳ��
$page_end = $writepage[1];  //����ҳ
_nextpage();

//��ҳ�ɼ�
function _nextpage(){
    if($GLOBALS['page_start'] <= $GLOBALS['page_end']){
        echo "���ڲɼ���".$GLOBALS['page_start']."ҳ��\r\n";
        $result = _Start($GLOBALS['groudid'],$GLOBALS['page_start']);
        if($result){
            $GLOBALS['page_start']++;
            _nextpage();
        }else{

        }
    }else{
        echo "--------------���вɼ���ɣ�\r\n";
    }
}

//�����ɼ�
function _Start($groudid,$page){
    $datalist = _CollectList($groudid,$page);
    foreach ($datalist as $value){
        $list = _ReadFile();
        if(!in_array($value['link'],$list)){
            _WriteFile($value['link']);
            $CollectContent = _CollectContent($value['link']);
            if($CollectContent){
                echo $value['link']." ----- �ɼ���ɣ�\r\n";
            }else{
                echo $value['link']." ----- �ɼ�ʧ�ܣ�\r\n";
            }
        }else{
            echo $value['link']." ----- �Ѳɼ�����\r\n";
        }
    }
    return true;
}

//��ȡ�ɼ��б�
function _CollectList($groudid,$page){

    if($page == 1){
        $page = 0;
    }else{
        $page = $page*25-25;
    }
    $url = 'https://www.douban.com/group/'.$groudid.'/discussion?start='.$page;

    // �ɼ�����
    $rules = [
        'link' => ['.olt .title a','href']
    ];

    //����α��UA��IP
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

//�ɼ���������
function _CollectContent($url){
    // �ɼ�����
    $rules = [
        'src' => ['.topic-richtext img','src']
    ];

    //����α��UA��IP
    $ql = QueryList::getInstance();
    $ql->use(DisguisePlugin::class);
    $ql->use(DisguisePlugin::class,'disguiseIp','disguiseUa');
    $data = $ql::get($url)->rules($rules)->query()->getData()->All();
    foreach ($data as $value) {
        _DownImg($value['src']);
    }
    return true;
}

//д��ɼ��ļ�
function _WriteFile($str){
    file_put_contents('HaveCollect.txt', $str."\r\n", FILE_APPEND);
}

//��ȡ�Ѳɼ��ļ�
function _ReadFile(){
    $file_path  = "HaveCollect.txt";
    $list = [];
    if(file_exists($file_path )){
        $str = file_get_contents($file_path);//�������ļ����ݶ��뵽һ���ַ�����
        $list = explode("\r\n", $str);
    }
    return $list;
}

//����ͼƬ
function _DownImg($url){
    $ch = curl_init();  //��ʼ��һ��curl���
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);   //HTTPS
    curl_setopt($ch, CURLOPT_HEADER, 0);  //����ʱ�Ὣͷ�ļ�����Ϣ��Ϊ�����������
    curl_setopt($ch,CURLOPT_TIMEOUT,60); //���ó�ʱʱ��
    $res = curl_exec($ch);  //ִ��curl
    curl_close($ch);  //�ر�curl�Ự
    $fp = fopen('images/'.md5($url).'.jpg', 'a'); //�����ļ�
    fwrite($fp, $res);  //д������
    fclose($fp);  //�رվ��
}