# Crawler

一个基于 Swoole 协程的高效爬虫框架，旨在快速实现网络爬虫任务。

## 特性

- **协程支持**：利用 Swoole 的协程特性，提高爬虫效率。
- **灵活配置**：支持自定义线程数、起始页和结束页等参数。
- **数据库集成**：支持 MongoDB 数据库存储，方便数据管理。
- **易于扩展**：提供清晰的钩子函数，方便用户自定义抓取逻辑。

## 安装

请确保您的环境已经安装了 PHP 和 Swoole 扩展。然后，通过 Composer 安装本项目：

```bash
composer require l1n6uyn/crawler
```
### 使用

以下是如何使用本爬虫框架的基本示例：

```php
require_once __DIR__ . '/vendor/autoload.php';

use Crawler\Crawler;

// 设置线程数
$threads = 10;
// 设置起始页和结束页
$startPage = 1;
$endPage = 100;

// MongoDB 数据库连接信息
$mongoDbUri = 'mongodb://localhost:27017/';
$mongoDbName = 'blog';
$mongoDbCollection = 'article';

// 创建 Crawler 实例
$crawler = new Crawler($mongoDbUri, $mongoDbName, $mongoDbCollection);

// 抓取列表
$crawler->list($threads, function ($page) {
    // 在这里实现抓取列表的逻辑...
    // 例如，访问一个 URL 并解析页面，然后返回数据数组
    return [
        'title' => '页面标题', // 页面标题
        'link' => '页面链接', // 页面链接
        '_id' => md5('页面链接'), // 使用链接的 MD5 值作为唯一标识
    ];
}, $endPage, $startPage);

// 抓取详情
$crawler->detail($threads, function ($doc) {
    // 在这里实现抓取详情的逻辑...
    // 例如，访问详情页并解析内容，然后返回数据数组
    return [
        'content' => '页面内容', // 页面内容
    ];
});

// 查询爬虫状态
$status = $crawler->status();
```

### 许可证
本项目采用 MIT 许可证。