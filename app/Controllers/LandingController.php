<?php
declare(strict_types=1);

namespace Sentinel\Controllers;

use Sentinel\Core\Request;
use Sentinel\Core\Response;

class LandingController
{
    public function index(Request $request, Response $response): void
    {
        ob_start();
        require __DIR__ . '/../Views/landing.php';
        $content = ob_get_clean();
        
        $response->html($content);
    }
}
