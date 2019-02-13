<?php

set_time_limit(0);

/**
 * 线程的执行任务
 * Class Threadrun
 */
class Threadrun extends Thread {

    public $url;
    public $data;
    public $params;

    public function __construct($url, $params=[]) {
        $this->url = $url;
        $this->params = $params;
    }

    public function run() {
        if(($url = $this->url)) {
            $params = [
                'goods_id'  => 1,
                'activity_id'  => 1,
                'user_id'   => isset($this->params['user_id']) ? $this->params['user_id'] : $this->getCurrentThreadId(),
            ];
            $startTime = microtime(true);
            $this->data = [
                'id'   => $params['user_id'],
                'result'  => model_http_curl_get( $url, $params ),  //发送 HTTP 请求
                'time'  => microtime(true)-$startTime,
                'now'   => microtime(true),
            ];
        }
    }
}

/**
 * 发送 HTTP 请求
 * author rongbin
 * @param $url
 * @param array $data
 * @param string $userAgent
 * @return mixed
 */
function model_http_curl_get($url,$data=[],$userAgent="") {
    $userAgent = $userAgent ? $userAgent : 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.2)';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);
    curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($curl, CURLOPT_POST, true);
    if( !empty($data) ) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}


/**
 * 执行多线程
 * author rongbin
 * @param $urls_array
 * @return mixed
 */
function model_thread_result_get($urls_array) {
    foreach ($urls_array as $key => $value) {
        $threadPool[$key] = new Threadrun($value["url"],['user_id'=>$value['user_id']]);
        $threadPool[$key]->start();
    }
    foreach ($threadPool as $thread_key => $thread_value) {
        while($threadPool[$thread_key]->isRunning()) {
            usleep(10);
        }
        if($threadPool[$thread_key]->join()) {
            $variable_data[$thread_key] = $threadPool[$thread_key]->data;
        }
    }
    return $variable_data;
}


/**
 * 友好的打印变量
 * author rongbin
 * @param $val
 */
function dump( $val ) {
    echo '<pre>';
    var_dump($val);
    echo '</pre>';
}


/**
 * 写日志
 * author rongbin
 * @param $msg
 * @param string $logPath
 */
function writeLog( $msg, $logPath='' ) {
    if( empty($logPath) ) {
        $logPath = date('Y_m_d').'.log';
    }
    if( !file_exists($logPath) ) {
        $fp = fopen( $logPath,'w' );
        fclose( $fp );
    }
    error_log( $msg.PHP_EOL, 3, $logPath);
}


/**
 * 生成日志信息
 * author rongbin
 * @param $result
 * @param $timeDiff
 * @return bool|string
 */
function createLog( $result, $timeDiff ) {
    if( empty($result) || !is_array($result) ) {
        return false;
    }
    $succeed = 0;
    $fail = 0;
    foreach( $result as $v ) {
        $times[] = $v['time'];
        $v['result'] === false ? $fail++ : $succeed++;
    }
    $totalTime = array_sum( $times );
    $maxTime = max( $times );
    $minTime = min( $times );
    $sum = count( $times );
    $avgTime = $totalTime/$sum;
    $segment = str_repeat('=',100);
    $flag = $segment . PHP_EOL;
    $flag .= '总共执行时间：' . $timeDiff . PHP_EOL ;
    $flag .= '最大执行时间：' . $maxTime . PHP_EOL;
    $flag .= '最小执行时间：' . $minTime . PHP_EOL;
    $flag .= '平均请求时间：' . $avgTime . PHP_EOL;
    $flag .= '请求数：' . $sum . PHP_EOL;
    $flag .= '请求成功数：' . $succeed . PHP_EOL;
    $flag .= '请求失败数：' . $fail . PHP_EOL;
    $flag .= $segment . PHP_EOL;
    return $flag;

}



/**
 * 发起秒杀请求
 * author rongbin
 * @param $urls
 * @param string $logPath
 * @return mixed
 */
function insertList( $urls, $logPath='' ) {
    $t = microtime(true);
    //执行多线程
    $result = model_thread_result_get($urls);
    $e = microtime(true);
    $timeDiff = $e-$t;
    echo "总执行时间：" . $timeDiff . PHP_EOL;
    foreach( $result as $v ) {
        $msg = '用户【' . $v['id'] . '】秒杀商品, 返回结果 ' . $v['result'] . ' 用时【' . $v['time'] . ' 秒】 当前时间【'.$v['now'].'】';
        writeLog( $msg,$logPath );
    }
    $logStr = createLog( $result, $timeDiff);
    writeLog( $logStr, $logPath );
    return $result;
}


//发起秒杀请求
for ($i=0; $i < 1000; $i++) {
    $urls_array[] = array("name" => "baidu", "url" => "http://***.***.com/seckill/shopping/listinsert");
}

$list = insertList( $urls_array, './inset.log' );

//发起秒杀结果查询请求
$urls_array = [];
foreach( $list as $v ) {
    if( $v['result'] === false ) {
        continue;
    }
    $urls_array[] = array(
        "name"  => "baidu",
        "url"  => "http://***.***.com/seckill/shopping/query",
        'user_id' => $v['id'],
    );
}
insertList( $urls_array, './query.log' );