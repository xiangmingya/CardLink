# CardLink - Typecho 卡片式链接插件

一个美观、实用的 Typecho 卡片式链接展示插件，支持自定义分类颜色、Emoji 图标和日期显示。

## ✨ 特性

- 🎨 **自定义分类颜色** - 为不同分类设置专属颜色
- 😊 **Emoji 图标支持** - 为每个分类添加个性化 Emoji
- 📅 **日期显示** - 可选显示卡片日期信息
- 📱 **响应式设计** - 桌面端双列，移动端单列自适应
- ✏️ **编辑器集成** - 后台编辑器一键插入短代码
- 🎯 **简洁美观** - 现代化卡片设计，悬停动画效果

## 📦 安装

1. 下载插件文件
2. 将 `CardLink` 文件夹上传到 `/usr/plugins/` 目录
3. 在 Typecho 后台「控制台」→「插件」中启用插件

## 🚀 使用方法

### 基础用法

在文章中使用短代码插入卡片：

```
[card name="插件名称" link="https://example.com" category="插件"]
这是插件的描述信息，支持多行文字。
[/card]
```

### 完整参数

```
[card name="BaiduLinkSubmit" link="https://github.com/xiangmingya/BaiduLinkSubmit" category="插件" date="2024-12-26"]
Typecho 后台发布或更新文章时，文章链接将会自动推送到百度收录平台。
[/card]
```

### 参数说明

| 参数 | 必填 | 说明 | 示例 |
|------|------|------|------|
| `name` | 是 | 卡片标题 | `name="插件名称"` |
| `link` | 是 | 跳转链接 | `link="https://example.com"` |
| `category` | 否 | 分类标签 | `category="插件"` |
| `date` | 否 | 日期信息 | `date="2024-12-26"` |

**注意**：
- 如果不填写 `category`，将不显示分类标签和 Emoji
- 如果不填写 `date`，将不显示日期信息

## ⚙️ 插件配置

在 Typecho 后台「控制台」→「插件」→「CardLink」→「设置」中配置。

### 分类颜色配置

格式：`分类名:颜色值:emoji`

每行一个分类，例如：

```
插件:#667eea:🔌
主题:#ff6b6b:🎨
工具:#48bb78:🛠️
教程:#f59e0b:📚
资源:#8b5cf6:💎
```

**说明**：
- 颜色值使用十六进制格式（如 `#667eea`）
- Emoji 是可选的，不填写则不显示
- 背景色会自动生成为半透明浅色版本

### Emoji 搜索

配置页面提供了 [Emojipedia](https://emojipedia.org/) 链接，方便搜索和复制 Emoji。

## 🎨 样式展示

### 桌面端效果
- 一行显示 2 个卡片
- 最大宽度 1200px，居中显示
- 左右边距 20px

### 移动端效果
- 一行显示 1 个卡片
- 自适应屏幕宽度

### 卡片元素
- **左上角**：分类标签（带颜色）
- **标题**：卡片名称
- **描述**：浅灰色背景框
- **右下角**：日期信息（可选）
- **背景**：半透明 Emoji 水印（可选）

## 📝 编辑器使用

1. 在文章编辑页面，工具栏会自动添加「卡」按钮
2. 点击按钮自动插入短代码模板
3. 修改模板中的参数即可

插入的模板：
```
[card name="名称" link="链接" category="分类" date="2024-01-01"]描述内容[/card]
```

## 🔧 技术特性

- **Markdown 兼容** - 自动处理 Markdown 解析
- **多插件兼容** - 正确处理插件钩子链
- **性能优化** - 使用 Flexbox 布局，CSS3 动画
- **安全性** - 参数自动转义，防止 XSS 攻击

## 📂 文件结构

```
CardLink/
├── Plugin.php      # 插件主文件
├── style.css       # 样式文件
└── README.md       # 说明文档
```

## 🌟 示例效果

### 单个卡片
```
[card name="Typecho" link="https://typecho.org" category="工具" date="2024-12-26"]
一款轻量级的开源博客程序，基于 PHP 构建。
[/card]
```

### 多个卡片（自动双列布局）
```
[card name="插件A" link="https://example.com/a" category="插件"]插件A的描述[/card]
[card name="插件B" link="https://example.com/b" category="插件"]插件B的描述[/card]
[card name="插件C" link="https://example.com/c" category="主题"]插件C的描述[/card]
[card name="插件D" link="https://example.com/d" category="主题"]插件D的描述[/card]
```

## 🎯 常见问题

### Q: 短代码没有被解析？
A: 请确保：
1. 插件已正确启用
2. 重新禁用并启用插件
3. 清除浏览器缓存

### Q: 样式显示不正常？
A: 请检查：
1. 主题是否有冲突的 CSS 样式
2. 浏览器是否缓存了旧样式（Ctrl+F5 强制刷新）

### Q: 如何修改卡片宽度？
A: 编辑 `style.css` 文件，修改 `.card-link-container` 的 `max-width` 值。

### Q: 如何自定义颜色？
A: 在插件设置页面配置分类颜色，格式：`分类名:颜色值:emoji`

## 📄 开源协议

本插件基于 MIT 协议开源。

## 👨‍💻 作者

- **作者**: 湘铭呀
- **版本**: 3.0.0

## 🙏 致谢

感谢 Typecho 社区的支持与贡献。

---

如有问题或建议，欢迎提交 Issue 或 Pull Request！
