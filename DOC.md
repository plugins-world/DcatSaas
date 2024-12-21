## tenancy 扩展初始化说明

1. 在 `.env` 中配置数据库
2. 安装扩展包
`composer require stancl/tenancy`

3. 执行初始化 `php artisan tenancy:install`
生成如下文件：
```
config/tenancy.php
routes/tenant.php
TenancyServiceProvider.php
migrations. Remember to run [php artisan migrate]!
database/migrations/tenant folder.
```

4. 生成 `Tenant` 模型，继承默认的 `Tenant` 模型，然后更新 `config/tenancy.php` 的 `tenant_model` 配置
`php artisan make:model Tenant`

5. 修改服务提供者 `app/Providers/TenancyServiceProvider.php` 增加创建租户时初始化数据的功能
`// Jobs\SeedDatabase::class,` => `Jobs\SeedDatabase::class,`

6. 更新 `database/seeders/DatabaseSeeder.php`, 允许增加创建租户时的初始化逻辑
`$this->call(TenantInitSeeder::class);`

7. 安装 `dcat-admin`
8. 执行 `admin:publish` 发布 `config/admin.php` 配置
9. 执行 `admin:install` 初始化
10. 将账号、菜单等相关数据库复制一份给到 `database/migrations/tenant`
`cp database/migrations/admin* `
11. 开启 `admin` 多后台模式
12. 创建 AdminTenant 应用
13. 更新 AdminTenant 应用的访问前缀，通过访问前缀识别应用后台信息
14. 配置 tenant 与 tenant-init 中间件
15. 更新 AdminTenant 应用的中间件，通过中间件禁止从中心域名访问
