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
     * @param  Throwable  $exception
     * @return mixed
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

        return $this->request('post', $this->url, $data);
    }

    /**
     * 给钉钉发报警消息，类型：string
     *
     * @param  string  $errMsg
     * @return array|string
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

        return $this->request('post', $this->url, $data);
    }

    /**
     * 给钉钉发报警消息，类型：markdown
     *
     * @param  Throwable  $exception
     * @return array|string
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

        return $this->request('post', $this->url, $data);
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
     * 发送请求
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $body
     * @param  array  $headers
     * @param  array  $uploads
     * @return array|string
     */
    public function request($method, $url, $body = [], $headers = [], $uploads = [])
    {
        $ch = curl_init();
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => array_merge([
                'Connection: Keep-Alive',
            ], $headers),
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 120,

            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if (strtolower($method) == 'post' && ! empty($body)) {
            $options[CURLOPT_POSTFIELDS] = http_build_query($body);
        }

        curl_setopt_array($ch, $options);
        $output = curl_exec($ch);

        if ($output === false) {
            return "Error Code:".curl_errno($ch).", Error Message:".curl_error($ch);
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header_text = substr($output, 0, $header_size);
            $body = substr($output, $header_size);
            $headers = [];

            foreach (explode("\r\n", $header_text) as $i => $line) {
                if (! empty($line)) {
                    if ($i === 0) {
                        $headers[0] = $line;
                    } else {
                        if (strpos($line, ": ")) {
                            [$key, $value] = explode(': ', $line);
                            $headers[$key] = $value;
                        }
                    }
                }
            }

            $response['headers'] = $headers;
            $response['body'] = json_decode($body, true);
            $response['http_code'] = $httpCode;
        }
        curl_close($ch);

        return $response;
    }
}
