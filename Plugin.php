<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 卡片式链接展示插件
 * 
 * @package CardLink
 * @author 湘铭呀
 * @version 3.1.0
 * @link https://github.com/xiangmingya/CardLink
 */
class CardLink_Plugin implements Typecho_Plugin_Interface
{
    private const DEFAULT_COLOR = '#667eea';
    private const PLACEHOLDER_PREFIX = 'XIANGMING_CARDLINK_TOKEN_';

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
        echo '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') . '" />';
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
        $content = empty($lastResult) ? $text : $lastResult;
        if (stripos($content, '[card') === false) {
            return $content;
        }

        $cardGroups = array();
        $pattern = '/((?:\s*\[card\b[^\]]*\].*?\[\/card\]\s*)+)/is';
        $contentWithPlaceholders = preg_replace_callback(
            $pattern,
            function ($matches) use (&$cardGroups) {
                preg_match_all('/\[card\b([^\]]*)\](.*?)\[\/card\]/is', $matches[1], $cardMatches, PREG_SET_ORDER);
                if (empty($cardMatches)) {
                    return $matches[0];
                }

                $cards = array();
                foreach ($cardMatches as $cardMatch) {
                    $cards[] = self::parseCallback($cardMatch);
                }

                $index = count($cardGroups);
                $cardGroups[] = '<div class="xiangming-card-link-container">' . implode('', $cards) . '</div>';
                return "\n\n" . self::PLACEHOLDER_PREFIX . $index . "\n\n";
            },
            $content
        );

        if ($contentWithPlaceholders === null || empty($cardGroups)) {
            return $content;
        }

        if (self::shouldRenderMarkdown($text, $lastResult)) {
            $contentWithPlaceholders = self::renderMarkdown($contentWithPlaceholders);
        }

        return self::replacePlaceholders($contentWithPlaceholders, $cardGroups);
    }

    /**
     * 正则回调
     */
    public static function parseCallback($matches)
    {
        $params_str = isset($matches[1]) ? trim($matches[1]) : '';
        $desc = trim($matches[2]);

        $atts = array(
            'name'  => '未命名',
            'link'  => '#',
            'category' => '',
            'date' => ''
        );

        $pattern = '/(\w+)\s*=\s*([\'"])(.*?)\2/s';
        preg_match_all($pattern, $params_str, $attributes);

        if (isset($attributes[1])) {
            foreach ($attributes[1] as $key => $attr) {
                if (isset($atts[$attr])) {
                    $atts[$attr] = trim($attributes[3][$key]);
                }
            }
        }

        $categoryHtml = '';
        $emojiHtml = '';

        if (!empty($atts['category'])) {
            list($colorMap, $emojiMap) = self::getCategoryConfig();
            $color = isset($colorMap[$atts['category']]) ? $colorMap[$atts['category']] : self::DEFAULT_COLOR;
            $rgb = self::hexToRgb($color);
            $bgColor = 'rgba(' . $rgb[0] . ',' . $rgb[1] . ',' . $rgb[2] . ',0.1)';
            $categoryLabel = htmlspecialchars($atts['category'], ENT_QUOTES, 'UTF-8');

            $categoryHtml = '<span class="xiangming-card-link-category" style="color:' . $color . ';background:' . $bgColor . '">' . $categoryLabel . '</span>';

            // 添加 emoji
            if (isset($emojiMap[$atts['category']])) {
                $emojiHtml = '<span class="xiangming-card-link-emoji">' . htmlspecialchars($emojiMap[$atts['category']], ENT_QUOTES, 'UTF-8') . '</span>';
            }
        }

        // 处理日期
        $dateHtml = '';
        if (!empty($atts['date'])) {
            $dateHtml = '<span class="xiangming-card-link-date">📅 ' . htmlspecialchars($atts['date'], ENT_QUOTES, 'UTF-8') . '</span>';
        }

        $title = htmlspecialchars($atts['name'], ENT_QUOTES, 'UTF-8');
        $link = self::sanitizeUrl($atts['link']);
        $descHtml = self::renderCardDescription($desc);

        return '<div class="xiangming-card-link-item">
            ' . $categoryHtml . '
            ' . $emojiHtml . '
            <a href="' . $link . '" target="_blank" rel="noopener noreferrer nofollow" class="xiangming-card-link-wrap">
                <div class="xiangming-card-link-body">
                    <h3 class="xiangming-card-link-title">' . $title . '</h3>
                    <div class="xiangming-card-link-desc">' . $descHtml . '</div>
                </div>
            </a>
            ' . $dateHtml . '
        </div>';
    }

    private static function shouldRenderMarkdown($text, $lastResult)
    {
        return empty($lastResult) || $lastResult === $text;
    }

    private static function renderMarkdown($text)
    {
        if (class_exists('Parsedown')) {
            $parsedown = new Parsedown();
            if (method_exists($parsedown, 'setSafeMode')) {
                $parsedown->setSafeMode(true);
            }
            return $parsedown->text($text);
        }

        if (class_exists('HyperDown')) {
            $parser = new HyperDown();
            return $parser->makeHtml($text);
        }

        return $text;
    }

    private static function replacePlaceholders($content, array $cards)
    {
        foreach ($cards as $index => $cardHtml) {
            $token = self::PLACEHOLDER_PREFIX . $index;
            $content = preg_replace('/<p>\s*' . preg_quote($token, '/') . '\s*<\/p>/i', $token, $content);
            $content = preg_replace('/<div>\s*' . preg_quote($token, '/') . '\s*<\/div>/i', $token, $content);
            $content = str_replace($token, $cardHtml, $content);
        }

        return $content;
    }

    private static function renderCardDescription($desc)
    {
        if ($desc === '') {
            return '';
        }

        $safeText = htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
        $html = self::renderMarkdown($safeText);

        if ($html === $safeText) {
            return nl2br($safeText);
        }

        return self::trimParagraphWrapper($html);
    }

    private static function trimParagraphWrapper($html)
    {
        $trimmed = trim($html);
        if (preg_match('/^<p>(.*)<\/p>$/is', $trimmed, $matches) === 1 && stripos($matches[1], '</p>') === false) {
            return $matches[1];
        }

        return $trimmed;
    }

    private static function getCategoryConfig()
    {
        $options = Helper::options();
        $plugin = $options->plugin('CardLink');
        $colorMap = array();
        $emojiMap = array();

        if (!empty($plugin->categoryColors)) {
            $lines = preg_split("/\r\n|\r|\n/", $plugin->categoryColors);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, ':') === false) {
                    continue;
                }

                $parts = explode(':', $line, 3);
                $cat = trim($parts[0]);
                if ($cat === '') {
                    continue;
                }

                $colorMap[$cat] = self::normalizeColor(isset($parts[1]) ? trim($parts[1]) : self::DEFAULT_COLOR);
                if (isset($parts[2]) && trim($parts[2]) !== '') {
                    $emojiMap[$cat] = trim($parts[2]);
                }
            }
        }

        return array($colorMap, $emojiMap);
    }

    private static function normalizeColor($color)
    {
        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color) === 1) {
            return $color;
        }

        return self::DEFAULT_COLOR;
    }

    private static function sanitizeUrl($url)
    {
        $url = trim($url);
        if ($url === '') {
            return '#';
        }

        $validated = filter_var($url, FILTER_VALIDATE_URL);
        if ($validated === false) {
            return '#';
        }

        $scheme = strtolower((string) parse_url($validated, PHP_URL_SCHEME));
        if (!in_array($scheme, array('http', 'https'), true)) {
            return '#';
        }

        return htmlspecialchars($validated, ENT_QUOTES, 'UTF-8');
    }

    private static function hexToRgb($hex)
    {
        $hex = ltrim(self::normalizeColor($hex), '#');
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
