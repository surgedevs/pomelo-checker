<?php
namespace Controllers;
use HTMLTemplateRenderer;
use PomeloChecker;

class APIV1Controller
{
    public function request(string $name): void
    {
        $htmlRenderer = new HTMLTemplateRenderer();
        $pomeloChecker = new PomeloChecker();

        $request = $pomeloChecker->checkPomelo($name, true);

        $htmlRenderer->renderJson($request);
    }

    public function text(string $name): void
    {
        $pomeloChecker = new PomeloChecker();

        $request = $pomeloChecker->checkPomelo($name, true);

        $body = '✅ Your username is available :3';

        if (isset($request['errors'])) {
            $body = '❌ Your username is invalid/taken :(';
        }

        echo $body;
    }

    public function list(string $code = ''): void
    {
        $htmlRenderer = new HTMLTemplateRenderer();
        $pomeloChecker = new PomeloChecker();

        $data = $pomeloChecker->list($code);

        $htmlRenderer->renderJson($data);
    }

    public function query(): void
    {
        $name = $_GET['name'] ?? '%%';
        $code = $_GET['code'] ?? '%%';

        $htmlRenderer = new HTMLTemplateRenderer();
        $pomeloChecker = new PomeloChecker();

        $data = $pomeloChecker->query($name, $code);

        $htmlRenderer->renderJson($data);
    }
}