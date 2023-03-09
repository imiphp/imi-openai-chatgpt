<?php

declare(strict_types=1);

use function Imi\env;

return [
    // api 密钥
    'api_key' => env('OPENAI_API_KEY', ''),
    // 代理地址，境外服务器也可以留空不设置
    'proxy'   => env('OPENAI_PROXY', 'http://127.0.0.1:10809'),
];
