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
        if (is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $opts = json_decode($content, true);
        }
        else
        {
            $opts = null;
        }
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
        var_dump($opts);
        $openAI->chat($opts, function ($curl_info, $data) use ($response, $id, &$replyContent) {
            var_dump($data);
            $datas = explode('data: ', $data);
            var_dump($datas);
            foreach ($datas as $dataStr)
            {
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
        file_put_contents($fileName, json_encode($opts, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));
        $response->end();
    }
}
