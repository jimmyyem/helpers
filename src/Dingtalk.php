<?php
/**
 * 给钉钉发送报警信息
 *
 * @author yanhuaguo
 * @date 2022-09-08 16:38:34
 **/

namespace Helpers;

use Throwable;
use GuzzleHttp\Client;

/**
 * 钉钉推送报警消息
 */
class Dingtalk
{
    /**
     * 钉钉提醒的webhook
     *
     * @var string
     */
    private $url;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param $token
     */
    public function __construct($token)
    {
        $this->url = 'https://oapi.dingtalk.com/robot/send?access_token='.$token;
        $this->client = new Client();
    }

    /**
     * 给钉钉发报警消息，类型：text
     *
     * @param  \Throwable  $exception
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function pushText(Throwable $exception)
    {
        $content = self::formatMessage($exception);

        $data = [
            'at' => [],
            'text' => [
                'content' => $content,
            ],
            'msgtype' => 'text',
        ];

        return $this->client->post($this->url, $data);
    }

    /**
     * 给钉钉发报警消息，类型：string
     *
     * @param  string  $errMsg
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function pushPlain(string $errMsg)
    {
        $data = [
            'at' => [],
            'text' => [
                'content' => $errMsg,
            ],
            'msgtype' => 'text',
        ];

        return $this->client->post($this->url, $data);
    }

    /**
     * 给钉钉发报警消息，类型：markdown
     *
     * @param $exception
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function pushMarkdown($exception)
    {
        $title = $exception->getMessage();
        $text = $exception->getMessage();// self::formatMessage($exception, '-');

        $data = [
            'at' => [],
            'markdown' => [
                'title' => $title,
                'text' => $text,
            ],
            'msgtype' => 'markdown',
        ];

        return $this->client->post($this->url, $data);
    }

    /**
     * 格式化消息
     *
     * @param  Throwable  $exception
     * @param  string  $delimiter
     * @param  int  $traceNum
     * @return string
     */
    public static function formatMessage(Throwable $exception, string $delimiter = '', int $traceNum = 5)
    {
        $trace = $exception->getTrace();
        $num = 1;
        $msg = [];
        $msg[] = sprintf('%s [Main] : Error: %s ：time:%s', $delimiter, $exception->getMessage(), date('Y-m-d H:i:s'));

        while ($num <= $traceNum && isset($trace[$num])) {
            $msg[] = sprintf(
                "%s [Stacktrace]: File:%s, Line:%s, Function:%s ",
                $delimiter,
                $trace[$num]['file'] ?? '',
                $trace[$num]['line'] ?? '',
                $trace[$num]['function'] ?? ''
            );
            $num++;
        }

        return implode("\r\n", $msg);
    }
}
