<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $ip = $request->getIPAddress();
        $key = 'rl_ip_' . md5($ip);

        $cache = cache();
        $count = $cache->get($key) ?? 0;

        if ($count >= 10) {
            return service('response')
                ->setStatusCode(429)
                ->setJSON([
                    'erro' => 'Muitas requisições. Tente novamente mais tarde.'
                ]);
        }

        $cache->save($key, $count + 1, 60); // 60 segundos
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
