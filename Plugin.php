<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 卡片式链接展示插件
 * 
 * @package CardLink
 * @author Gemini
 * @version 1.0.0
 * @link https://example.com
 */
class CardLink_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->content = array('CardLink_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Archive')->header = array('CardLink_Plugin', 'header');
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('CardLink_Plugin', 'footer');
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('CardLink_Plugin', 'footer');
    }

    /**
     * 禁用插件
     */
    public static function deactivate(){}

    /**
     * 配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $categoryColors = new Typecho_Widget_Helper_Form_Element_Textarea('categoryColors', NULL,
        "插件:#667eea:🔌\n主题:#ff6b6b:🎨\n工具:#48bb78:🛠️",
        _t('分类颜色配置'), _t('每行一个分类，格式：分类名:颜色值:emoji<br/>例如：插件:#667eea:🔌<br/>emoji 可选，将显示在卡片右下角<br/><a href="https://emojipedia.org/" target="_blank">🔍 搜索 Emoji</a>'));
        $form->addInput($categoryColors);
    }

    /**
     * 个人配置
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 前端 CSS
     */
    public static function header()
    {
        $cssUrl = Helper::options()->pluginUrl . '/CardLink/style.css';
        echo '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '" />';
    }

    /**
     * 后台 JS
     */
    public static function footer()
    {
        echo <<<'EOF'
<script type="text/javascript">
(function($) {
    function initCardButton() {
        if ($('#wmd-card-button').length > 0) return true;

        var toolbar = document.getElementById('wmd-button-row');
        if (toolbar) {
            var btn = document.createElement('li');
            btn.className = 'wmd-button';
            btn.id = 'wmd-card-button';
            btn.title = '插入卡片';

            var span = document.createElement('span');
            span.innerHTML = '卡';
            span.style.cssText = 'font-size:12px;font-weight:bold;color:#467b96;display:block;text-align:center;line-height:20px;';
            btn.appendChild(span);

            var imageButton = document.getElementById('wmd-image-button');
            var spacer2 = document.getElementById('wmd-spacer2');

            if (imageButton && spacer2) {
                toolbar.insertBefore(btn, spacer2);
            } else {
                toolbar.appendChild(btn);
            }

            btn.onclick = function() {
                var code = '[card name="名称" link="链接" category="分类" date="2024-01-01"]描述内容[/card]';

                var textarea = document.getElementById('text');
                if (textarea) {
                    if (document.selection) {
                        textarea.focus();
                        document.selection.createRange().text = code;
                    } else if (textarea.selectionStart || textarea.selectionStart == '0') {
                        var start = textarea.selectionStart;
                        var end = textarea.selectionEnd;
                        textarea.value = textarea.value.substring(0, start) + code + textarea.value.substring(end);
                        textarea.selectionStart = textarea.selectionEnd = start + code.length;
                    } else {
                        textarea.value += code;
                    }
                    $(textarea).trigger('input');
                    textarea.focus();
                }
            };

            return true;
        }
        return false;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if (!initCardButton()) {
                var timer = setInterval(function() {
                    if (initCardButton()) clearInterval(timer);
                }, 500);
            }
        });
    } else {
        if (!initCardButton()) {
            var timer = setInterval(function() {
                if (initCardButton()) clearInterval(timer);
            }, 500);
        }
    }
})(jQuery);
</script>
EOF;
    }

    /**
     * 解析短代码
     */
    public static function parse($text, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $text : $lastResult;

        // 先调用 Markdown 解析
        if (class_exists('Parsedown')) {
            $parsedown = new Parsedown();
            $text = $parsedown->text($text);
        } elseif (class_exists('HyperDown')) {
            $parser = new HyperDown();
            $text = $parser->makeHtml($text);
        }

        // 收集所有卡片
        $pattern = '/\[card\s+(.*?)\](.*?)\[\/card\]/is';
        $cards = array();

        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        if (!empty($matches)) {
            // 生成所有卡片 HTML
            foreach ($matches as $match) {
                $cards[] = CardLink_Plugin::parseCallback($match);
            }

            // 用容器包裹所有卡片
            $cardsHtml = '<div class="card-link-container">' . implode('', $cards) . '</div>';

            // 替换所有短代码为容器
            $text = preg_replace($pattern, '', $text, 1);
            $text = preg_replace($pattern, '', $text);
            $text = preg_replace('/<p>\s*<\/p>/', '', $text);

            // 在第一个短代码位置插入容器
            $firstPos = strpos($text, '</p>');
            if ($firstPos !== false) {
                $text = substr_replace($text, '</p>' . $cardsHtml, $firstPos, 4);
            } else {
                $text .= $cardsHtml;
            }
        }

        return $text;
    }

    /**
     * 正则回调
     */
    public static function parseCallback($matches)
    {
        $params_str = $matches[1];
        $desc = trim($matches[2]);

        $atts = array(
            'name'  => '未命名',
            'link'  => '#',
            'category' => '',
            'date' => ''
        );

        $pattern = '/(\w+)=\"(.*?)\"/';
        preg_match_all($pattern, $params_str, $attributes);

        if (isset($attributes[1])) {
            foreach ($attributes[1] as $key => $attr) {
                if (isset($atts[$attr])) {
                    $atts[$attr] = $attributes[2][$key];
                }
            }
        }

        $categoryHtml = '';
        $emojiHtml = '';

        if (!empty($atts['category'])) {
            $options = Helper::options();
            $plugin = $options->plugin('CardLink');

            $colorMap = array();
            $emojiMap = array();

            if (!empty($plugin->categoryColors)) {
                $lines = explode("\n", $plugin->categoryColors);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (strpos($line, ':') !== false) {
                        $parts = explode(':', $line);
                        $cat = trim($parts[0]);
                        $col = isset($parts[1]) ? trim($parts[1]) : '#667eea';
                        $emoji = isset($parts[2]) ? trim($parts[2]) : '';

                        $colorMap[$cat] = $col;
                        if (!empty($emoji)) {
                            $emojiMap[$cat] = $emoji;
                        }
                    }
                }
            }

            $color = isset($colorMap[$atts['category']]) ? $colorMap[$atts['category']] : '#667eea';
            $rgb = self::hexToRgb($color);
            $bgColor = 'rgba(' . $rgb[0] . ',' . $rgb[1] . ',' . $rgb[2] . ',0.1)';

            $categoryHtml = '<span class="card-link-category" style="color:' . $color . ';background:' . $bgColor . '">' . $atts['category'] . '</span>';

            // 添加 emoji
            if (isset($emojiMap[$atts['category']])) {
                $emojiHtml = '<span class="card-link-emoji">' . $emojiMap[$atts['category']] . '</span>';
            }
        }

        // 处理日期
        $dateHtml = '';
        if (!empty($atts['date'])) {
            $dateHtml = '<span class="card-link-date">📅 ' . htmlspecialchars($atts['date']) . '</span>';
        }

        return '<div class="card-link-item">
            ' . $categoryHtml . '
            ' . $emojiHtml . '
            <a href="' . $atts['link'] . '" target="_blank" class="card-link-wrap">
                <div class="card-link-body">
                    <h3 class="card-link-title">' . $atts['name'] . '</h3>
                    <div class="card-link-desc">' . $desc . '</div>
                </div>
            </a>
            ' . $dateHtml . '
        </div>';
    }

    private static function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return array(
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        );
    }
}