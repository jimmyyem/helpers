<?php
/**
 * 给钉钉发送报警信息
 *
 * @author yanhuaguo
 * @date 2022-09-08 16:38:34
 **/

namespace Helpers;

use Throwable;

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
     * @param $token
     */
    public function __construct($token)
    {
        $this->url = 'https://oapi.dingtalk.com/robot/send?access_token='.$token;
    }

    /**
     * 给钉钉发报警消息，类型：text
     *
     * @param  \Throwable  $exception
     * @return bool|string
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

        return $this->request($this->url, $data);
    }

    /**
     * 给钉钉发报警消息，类型：string
     *
     * @param  string  $errMsg
     * @return bool|string
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

        return $this->request($this->url, $data);
    }

    /**
     * 给钉钉发报警消息，类型：markdown
     *
     * @param $exception
     * @return bool|string
     */
    public function pushMarkdown($exception)
    {
        $title = $exception->getMessage();
        $text = self::formatMessage($exception, '-');

        $data = [
            'at' => [],
            'markdown' => [
                'title' => $title,
                'text' => $text,
            ],
            'msgtype' => 'markdown',
        ];

        return $this->request($this->url, $data);
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

    /**
     * 发送curl请求
     *
     * @param  string  $url
     * @param  array  $postData
     * @param  array  $options
     * @return bool|string
     */
    function request(string $url, array $postData, array $options = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $options['timeout'] ?? 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json;charset=utf-8']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 不用开启curl证书验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
}
