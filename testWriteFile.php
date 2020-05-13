<?php

declare(strict_types=1);
date_default_timezone_set('Asia/Shanghai');

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once "./vendor/autoload.php";

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
        fwrite($fp, self::getJSON('fwrite'));
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
        ];
        return json_encode($json, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    public static function getDateTime()
    {
        $time = microtime(true);
        $second = (int)$time;
        $uscond = (int)(($time - (float)$second) * 1000000);
        return sprintf("%s.%06d", date('Y-m-d H:i:s', (int)$time), $uscond);
    }

    public static function testLog()
    {
        $logger = new Logger('json');
        $logger->pushHandler(new StreamHandler(self::FILE_PATH, Logger::WARNING, true, null, true));
        $logger->warning(self::getJSON('log'));
    }
}

function forkTest($type)
{
    $count = 150;
    for ($i = 0; $i < $count; $i++) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            //错误处理：创建子进程失败时返回-1.
            die('could not fork');
        } else if ($pid) {
            //父进程会得到子进程号，所以这里是父进程执行的逻辑
            pcntl_wait($status); //等待子进程中断，防止子进程成为僵尸进程。
        } else {
            //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
            $time = microtime(true);
            switch ($type) {
                case 'put':
                    WriteTest::putFile();
                    break;
                case 'log':
                    WriteTest::testLog();
                    break;
                default:
                    WriteTest::writeFile();
                    break;
            }
            echo sprintf("cost %f ms\n", (microtime(true) - $time) * 1000.0);
            exit(0);
        }
    }
}

if ($argv[1] == 'put') {
    WriteTest::putFile();
} elseif ($argv[1] == 'log') {
    WriteTest::testLog();
} elseif ($argv[1] == 'fork') {
    //$time = microtime(true);
    forkTest($argv[2]);
    //echo sprintf("cost %f s", microtime(true) - $time);
} else {
    WriteTest::writeFile();
}
