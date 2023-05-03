<?php

class HTMLTemplateRenderer {
    private string $template;
    private array $meta = [];

    public function __construct(string $template = '')
    {
        if (empty($template)) {
            $dirSep = DIRECTORY_SEPARATOR;
            $template =  file_get_contents(__DIR__ . $dirSep . '..' . $dirSep . 'static' . $dirSep . 'html' . $dirSep . 'base.html');
        }

        $this->template = $template;
    }

    public function addMeta(string $property, string $content): void
    {
        $property = htmlspecialchars($property);
        $content = htmlspecialchars($content);

        $this->meta[] = '<meta property="' . $property . '" content="' . $content . '">';
    }

    public function render(string $body, array $replacement, bool $return = false): ?string
    {
        $meta = '';
        foreach ($this->meta as $tag) {
            $meta .= $tag;
        }

        $search = [
            '{{meta}}',
            '{{body}}',
        ];

        $replace = [
            $meta,
            $body
        ];

        foreach ($replacement as $s => $r) {
            $search[] = '{{' . $s . '}}';
            $replace[] = $r;
        }

        $html = str_replace($search, $replace, $this->template);

        echo $html;
        return null;
    }

    public function renderTemplate(string $template, array $replacement, bool $return = false): ?string
    {
        $dirSep = DIRECTORY_SEPARATOR;
        $contents = file_get_contents(
            __DIR__ . $dirSep . '..' . $dirSep . 'static' . $dirSep . 'html' . $dirSep . $template . '.html'
        );

        if ($return) {
            return $contents;
        }

        $this->render($contents, $replacement);

        return null;
    }

    public function renderJson(string | array $content): void
    {
        if (is_array($content)) {
            $content = json_encode($content);
        }

        header('Content-Type: application/json');

        echo $content;
    }
}