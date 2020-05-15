<?php

declare(strict_types=1);
date_default_timezone_set('Asia/Shanghai');
class WriteTest
{
    const FILE_PATH = './test.log';

    /**
     * 使用内置函数file_put_content写入json文件
     */
    public static function putFile()
    {
        file_put_contents(self::FILE_PATH, self::getJSON('file_put_contents'), FILE_APPEND | LOCK_EX);
    }

    /**
     * 使用fwrite写入json文件
     */
    public static function writeFile()
    {
        if (!$fp = @fopen(self::FILE_PATH, 'ab')) {
            return FALSE;
        }
        flock($fp, LOCK_EX);
        $msg = '';
        for ($i = 0; $i < 100; $i++) {
            $msg .= self::getMsg();
        }
        fwrite($fp, $msg);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    public static function getJSON($type)
    {
        $time = self::getDateTime();
        $json = [
            'time' => $time,
            'type' => $type,
            'rand' => rand(1, 1000000),
            'end' => 'yes'
        ];
        return json_encode($json, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    public static function getMsg()
    {
        $msg = "";
        for ($i = 0; $i < 1024 * 16; $i++) {
            $msg .= sprintf("%d", $i % 10);
        }
        return $msg . "\n";
    }

    public static function getDateTime()
    {
        $time = microtime(true);
        $second = (int)$time;
        $uscond = (int)(($time - (float)$second) * 10000);
        return sprintf("%s.%06d", date('Y-m-d H:i:s', (int)$time), $uscond);
    }
}

function forkTest($type)
{
    $count = 20;
    $pids = [];
    for ($i = 0; $i < $count; $i++) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            //错误处理：创建子进程失败时返回-1.
            die('could not fork');
        } else if ($pid) {
            //父进程会得到子进程号，所以这里是父进程执行的逻辑
            //pcntl_wait($status); //等待子进程中断，防止子进程成为僵尸进程。
            $pids[] = $pid;
            //echo "{$pid} start\n";
        } else {
            //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
            $time = microtime(true);
            switch ($type) {
                case 'put':
                    WriteTest::putFile();
                    break;
                default:
                    WriteTest::writeFile();
                    break;
            }
            echo sprintf("[%8d] php test, cost %f ms\n", getmypid(), (microtime(true) - $time) * 1000.0);
            exit(0);
        }
    }

    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        //echo "{$pid} exit with {$status}\n";
    }
}

$start = microtime(true);
if ($argv[1] == 'put') {
    WriteTest::putFile();
} elseif ($argv[1] == 'fork') {
    if (file_exists('./test.log')) {
        unlink('./test.log');
    }
    forkTest($argv[2]);
} else {
    WriteTest::writeFile();
}
$cost = (microtime(true) - $start) * 1000.0;
echo sprintf("end test. cost %f ms", $cost);
