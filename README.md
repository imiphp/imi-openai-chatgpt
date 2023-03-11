# 说明

这是一个在 imi 框架接入 OpenAI ChatGPT 的 Demo 项目。

OpenAI 的接口使用了 html5 新加入的 `event-stream` 技术，imi 编写的接口同样采用了该技术，可以实现实时返回 AI 回复的内容。

建议使用 Swoole 最新版本！

imi 框架：<https://www.imiphp.com>

imi 文档：<https://doc.imiphp.com>

OpenAI：<https://platform.openai.com/>

> 如何注册请自行搜索解决。

## 安装

创建项目：`composer create-project imiphp/openai-chatgpt`

## 配置

复制 `.env.tpl` 为 `.env`。

修改 `.env` 配置即可。

## 运行

`vendor/bin/imi-swoole swoole/start`

## 访问测试

<http://127.0.0.1:8080/>

控制器：`Module/Test/ApiController/IndexController.php`

页面模版：`Module/Test/template/index/index.html`

## 生产环境

**关闭热更新：**`config/beans.php` 中 `hotUpdate.status` 设为 `false`

## 代码质量

### 格式化代码

内置 `php-cs-fixer`，统一代码风格。

配置文件 `.php-cs-fixer.php`，可根据自己实际需要进行配置，文档：<https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/doc/config.rst>

**格式化项目：** `./vendor/bin/php-cs-fixer fix`

**格式化指定文件：** `./vendor/bin/php-cs-fixer fix test.php`

### 代码静态分析

内置 `phpstan`，可规范代码，排查出一些隐藏问题。

配置文件 `phpstan.neon`，可根据自己实际需要进行配置，文档：<https://phpstan.org/config-reference>

**分析项目：** `./vendor/bin/phpstan`

**分析指定文件：** `./vendor/bin/phpstan test.php`

### 测试用例

内置 `phpunit`，可以实现自动化测试。

**文档：**<https://phpunit.readthedocs.io/en/9.5/>

**测试用例 demo：**`tests/Module/Test/TestServiceTest.php`

**运行测试用例：**`composer test`
