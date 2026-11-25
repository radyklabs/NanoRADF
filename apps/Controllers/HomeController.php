<?php declare (strict_types=1);

/**
 * Landing detector — demonstrates a routed SSR page.
 *
 * @since 0.0.1
 */

namespace App\Controllers;

use System\Controllers\PublicController;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__,2).DIRECTORY_SEPARATOR.'.errorss');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

final class HomeController extends PublicController
{
    /**
     * `/` — proves the route table reaches a real action.
     *
     * @since  0.0.1
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function index (): array
    {
        return $this->respond ($this->page ('home'));
    }

    /**
     * `/hello/{name}` — proves path params arrive positionally.
     *
     * @since  0.0.1
     * @param  string $name Captured segment, URL-decoded by the router.
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function hello (string $name): array
    {
        // Output is escaped here — never trust a captured segment.
        $safe = htmlspecialchars ($name, ENT_QUOTES, 'UTF-8');

        return $this->respond ($this->page ("hello — name={$safe}"));
    }

    /**
     * Minimal page shell shared by the landing actions.
     *
     * @since  0.0.1
     * @param  string $line Body line.
     * @return string HTML document.
     */
    private function page (string $line): string
    {
        $method = htmlspecialchars ((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), ENT_QUOTES);
        $path   = htmlspecialchars ((string) gserver ('request.uri', '/'), ENT_QUOTES);

        return "<!doctype html><html lang=\"en\"><head><meta charset=\"utf-8\">"
            ."<title>NanoRADF — {$line}</title></head>"
            ."<body><h1>NanoRADF</h1><p>{$line}</p>"
            ."<p><code>{$method}</code> <code>{$path}</code></p></body></html>";
    }
}
