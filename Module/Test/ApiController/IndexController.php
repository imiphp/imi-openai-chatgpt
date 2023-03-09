<?php

declare(strict_types=1);

namespace ImiApp\Module\Test\ApiController;

use Imi\Aop\Annotation\Inject;
use Imi\Config;
use Imi\Server\Http\Controller\HttpController;
use Imi\Server\Http\Route\Annotation\Action;
use Imi\Server\Http\Route\Annotation\Controller;
use Imi\Server\Http\Route\Annotation\Route;
use Imi\Server\View\Annotation\HtmlView;
use Imi\Server\View\Annotation\View;
use Imi\Util\Imi;
use ImiApp\Module\Test\Service\TestService;
use Orhanerday\OpenAi\OpenAi;

/**
 * @Controller("/")
 * @HtmlView(baseDir="Test/template/index/")
 */
class IndexController extends HttpController
{
    /**
     * @Inject
     */
    protected TestService $testService;

    /**
     * @Action
     * @Route("/")
     * @View(renderType="html")
     */
    public function index()
    {
    }

    /**
     * @Action
     *
     * @return void
     */
    public function chatGPT(string $id, string $message, string $systemContent = '')
    {
        if ('' === $id)
        {
            $id = md5(uniqid('', true));
        }
        $fileName = Imi::getRuntimePath('openai/' . md5($id) . '.txt');
        $fp = fopen($fileName, 'a+');
        fseek($fp, 0, \SEEK_SET);
        $content = stream_get_contents($fp);
        $opts = json_decode($content, true);
        if (!$opts)
        {
            $opts = [
                'model'             => 'gpt-3.5-turbo',
                'messages'          => [],
                'temperature'       => 1.0,
                'max_tokens'        => 150,
                'frequency_penalty' => 0,
                'presence_penalty'  => 0,
                'stream'            => true,
            ];
            if ('' !== $systemContent)
            {
                $opts['messages'][] = ['role' => 'system', 'content' => $systemContent];
            }
        }
        $opts['messages'][] = [
            'role'    => 'user',
            'content' => $message,
        ];
        fwrite($fp, json_encode($opts, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));

        $openAI = new OpenAi(Config::get('@app.openai.api_key'));
        if ($proxy = Config::get('@app.openai.proxy'))
        {
            $openAI->setProxy($proxy);
        }
        /** @var \Swoole\Http\Response $swooleResponse */
        $response = $this->response->getSwooleResponse();
        $response->header('Content-Type', 'text/event-stream');
        $response->header('Cache-Control', 'no-cache');
        $replyContent = '';
        $openAI->chat($opts, function ($curl_info, $data) use ($response, $id, &$replyContent) {
            var_dump($data);
            $datas = explode(\PHP_EOL, $data);
            foreach ($datas as $tmpData)
            {
                $dataStr = substr($tmpData, 6);
                $arrayData = json_decode($dataStr, true);
                if ($arrayData)
                {
                    if (isset($arrayData['choices'][0]['delta']['content']))
                    {
                        $replyContent .= ($content = $arrayData['choices'][0]['delta']['content'] ?? '');
                        if (!$response->write('data: ' . json_encode([
                            'id'      => $id,
                            'content' => $content,
                        ]) . \PHP_EOL . \PHP_EOL))
                        {
                            return 0;
                        }
                    }
                    elseif (!empty($arrayData['choices'][0]['finish_reason']))
                    {
                        if (!$response->write('data: ' . json_encode([
                            'id'      => $id,
                            'content' => null,
                        ]) . \PHP_EOL . \PHP_EOL))
                        {
                            return 0;
                        }
                    }
                }
            }

            return \strlen($data);
        });
        $opts['messages'][] = [
            'role'    => 'assistant',
            'content' => $replyContent,
        ];
        fwrite($fp, json_encode($opts, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));
        fclose($fp);
        $response->end();
    }
}
