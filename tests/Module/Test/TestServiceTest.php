<?php

declare(strict_types=1);

namespace ImiApp\Test\Module\Test;

use Imi\App;
use ImiApp\Module\Test\Service\TestService;
use PHPUnit\Framework\TestCase;

class TestServiceTest extends TestCase
{
    public function testGetImi(): void
    {
        /** @var TestService $testService */
        $testService = App::getBean(TestService::class);
        $this->assertEquals('imi', $testService->getImi());
    }
}
