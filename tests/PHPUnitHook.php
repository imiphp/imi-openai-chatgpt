<?php

declare(strict_types=1);

namespace ImiApp\Test;

use Imi\App;
use Imi\Cli\CliApp;
use function Imi\env;
use Imi\Swoole\SwooleApp;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class PHPUnitHook implements BeforeFirstTestHook, AfterLastTestHook
{
    /**
     * @var Channel|null
     */
    private $channel = null;

    public function executeBeforeFirstTest(): void
    {
        switch (env('TEST_MODE'))
        {
            case 'swoole':
                $this->channel = $channel = new Channel(1);
                Coroutine::create(static function () use ($channel) {
                    // 要保证连接池连接被释放，必须在当前协程执行完时推给 executeAfterLastTest() 的 pop()
                    Coroutine::defer(function () use ($channel) {
                        $channel->push(1);
                    });
                    // @phpstan-ignore-next-line
                    App::run('ImiApp', SwooleApp::class, static function () use ($channel) {
                        $channel->push(1);
                        $channel->pop();
                    });
                });
                $channel->pop();
                break;
            default:
                App::run('ImiApp', CliApp::class, static function () {
                });
                break;
        }
    }

    public function executeAfterLastTest(): void
    {
        if (isset($this->channel))
        {
            $this->channel->push(1);
            $this->channel->pop();
        }
    }
}
