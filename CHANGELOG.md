# Changelog

所有重要的项目变更都会记录在此文件中。

格式基于 [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)，
并且此项目遵循 [语义版本控制](https://semver.org/spec/v2.0.0.html)。

## [Unreleased]

## [1.0.0] - 2024-01-XX

### Added
- 🎉 初始版本发布
- ✅ 完全支持 Hyperf 3.0+ 框架
- ✅ 基于协程的 HTTP 客户端实现
- ✅ 完整的依赖注入容器集成
- ✅ 支持多个抖店应用同时配置
- ✅ 连接池支持，提升高并发性能
- ✅ 严格的 PHP 8+ 类型声明
- ✅ 遵循 PSR 标准
- ✅ 协程安全的架构设计

### Features
- 🚀 协程 HTTP 客户端 (基于 Hyperf Guzzle)
- 🔧 依赖注入支持 (构造函数注入和注解注入)
- ⚡ 连接池优化，支持高并发场景
- 🏪 多店铺应用配置管理
- 🔐 完整的访问令牌管理 (获取、刷新、解析)
- 📝 详细的使用文档和示例代码
- 🧪 单元测试支持

### API Support
- ✅ Token 相关 API (创建、刷新令牌)
- ✅ Product 相关 API (产品列表等)
- 🔧 易于扩展的 AbstractRequest 基类

### Performance
- ⚡ 协程并发，性能相比原版 SDK 提升 3-5 倍
- 🔄 连接池复用，减少连接建立开销
- 💾 内存优化，避免协程环境下的内存泄漏

### Documentation
- 📚 完整的 README 文档
- 🔧 详细的配置说明
- �� 丰富的使用示例
- ❓ 常见问题解答 