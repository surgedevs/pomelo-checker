<?php
namespace Controllers;

use HTMLTemplateRenderer;
use PomeloChecker;
use Response;

class HomepageController
{
    public function index()
    {
        if (isset($_GET['username'])) {
            header('Location: ' . $_SERVER['HTTP_HOST'] . '/' . $_GET['username'], true, 301);
            exit;
        }

        $renderer = new HTMLTemplateRenderer();

        $renderer->addMeta('og:title', 'Pomelo availability checker');
        $renderer->addMeta('og:description', 'Check if your username is available here!');
        $renderer->addMeta('theme-color', '#c444ff');
        $renderer->addMeta('og:url', $_SERVER['HTTP_HOST'] . '/');
        $renderer->addMeta('og:type', 'website');

        $renderer->renderTemplate('index', ['title' => 'Pomelo availabilty checker', 'result' => '']);
    }

    public function checker(string $name)
    {
        $name = urldecode($name);

        $renderer = new HTMLTemplateRenderer();
        $pomeloChecker = new PomeloChecker();

        $result = $pomeloChecker->checkPomelo($name);

        $divContent = '';
        $divColor = '';

        if (isset($result["content"])) {
            $username = htmlspecialchars(string: $name);

            $divContent = <<<HEREDOC
                <h3 style="color: #FF0000">$username is a invalid/taken :(</h3>
                <p>
                    $username is used for subcommands in tags.<br>
                    Embeds of this site will give out special information.<br>
                    It is also not a valid username as it starts with "-", which cannot be used in pomelo usernames.
                </p>
            HEREDOC;

            $renderer->addMeta('og:title', '❓ ' . $username);
            $renderer->addMeta('og:description', $result['content']);
            $renderer->addMeta('theme-color', '#c444ff');
            $divColor = '#FF0000';
        } else {
            if (isset($result["errors"]) && !$result["errors"]["username"]["_errors"][0]['code'] == 'NON_DISCORD_RATELIMITED') {
                $username = htmlspecialchars(string: $name);
                $reason = htmlspecialchars($result["errors"]["username"]["_errors"][0]["message"]);
                $code = htmlspecialchars($result["errors"]["username"]["_errors"][0]["code"]);
    
                $divContent = <<<HEREDOC
                    <h3 style="color: #FF0000">$username is invalid/taken :(</h3>
                    <p>
                        "$reason"<br>
                        <span class="error-code">($code)</span>
                    </p>
                HEREDOC;
    
                $renderer->addMeta('og:title', '❌ ' . $username . ' is invalid/taken :(');
                $renderer->addMeta('og:description', 'Reason: ' . $reason . ' (' . $code . ')');
                $renderer->addMeta('theme-color', '#FF0000');
                $divColor = '#FF0000';
            } else {
                $username = htmlspecialchars($name);
    
                $divContent = <<<HEREDOC
                    <h3 style="color: #00FF00">$username is available :3</h3>
                HEREDOC;
    
                $renderer->addMeta('og:title', '✅ ' . $username . ' is available :3');
                $renderer->addMeta('og:description', ' ');
                $renderer->addMeta('theme-color', '#00FF00');
                $divColor = '#00FF00';
            }
        }

        $resultBox = '<div class="result" style="border-color: ' . $divColor . ' ">' . $divContent . '</div>';

        $renderer->addMeta('og:type', 'website');

        $renderer->renderTemplate('index', [
            'title' => 'Pomelo availabilty checker',
            'result' => $resultBox,
        ]);
    }

    public function checkerStringify(string $name): void
    {
        $name = urldecode($name);

        $pomeloChecker = new PomeloChecker();

        $result = $pomeloChecker->checkPomelo($name);

        $divContent = '';
        $divColor = '';

        if (isset($result["content"])) {
            echo $result['content'];
        } else {
            if (isset($result["errors"]) && !$result["errors"]["username"]["_errors"][0]['code'] == 'NON_DISCORD_RATELIMITED') {
                $username = htmlspecialchars(string: $name);
                $reason = htmlspecialchars($result["errors"]["username"]["_errors"][0]["message"]);
                $code = htmlspecialchars($result["errors"]["username"]["_errors"][0]["code"]);
    
                echo '> ❌ ' . $username . ' is invalid/taken :(' . PHP_EOL;
                echo '> "' . $reason . '"' . PHP_EOL;
                echo '> (' . $code . ')' . PHP_EOL;
                echo '> (https://' . $_SERVER['SERVER_NAME'] . '/' . $username . '?_dc=' . time() . ')' . PHP_EOL;
            } else {
                $username = htmlspecialchars($name);

                echo '> ✅ ' . $username . ' is available :3' . PHP_EOL;
                echo '> (https://' . $_SERVER['SERVER_NAME'] . '/' . $username . '?_dc=' . time() . ')' . PHP_EOL;
            }
        }

        $resultBox = '<div class="result" style="border-color: ' . $divColor . ' ">' . $divContent . '</div>';
    }

    public function funOver(): void
    {
        $renderer = new HTMLTemplateRenderer();

        $renderer->renderTemplate('base', ['body' => '<h1>Sorry... but the fun is over :(<h2> <p>Discord staff issued an announcement not to check pomelo availability using api calls. Image below.<br>Out of risk of getting my server ip banned, i had to shut down this service.</p> <img src="https://media.discordapp.net/attachments/867746422027452426/1103417560759816222/qQu7ZBm.png?width=1581&height=220">', 'meta' => '']);
    }
}