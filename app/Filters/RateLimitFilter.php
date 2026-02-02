<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Ignora preflight CORS
        if ($request->getMethod() === 'options') {
            return;
        }

        $ip    = $request->getIPAddress();
        $rota  = $request->getPath();
        $key   = 'rl_ip_' . md5($ip);

        $cache = cache();
        $count = $cache->get($key) ?? 0;

        // 🚨 Abuso pesado (ex: bot)
        if ($count >= 30) {
            log_message(
                'critical',
                "ABUSO DETECTADO | IP={$ip} rota={$rota} tentativas={$count}"
            );

            return service('response')
                ->setStatusCode(429)
                ->setHeader('Retry-After', '300')
                ->setJSON([
                    'erro' => 'Acesso temporariamente bloqueado por abuso.'
                ]);
        }

        // ⚠️ Rate limit normal
        if ($count >= 10) {
            log_message(
                'warning',
                "Rate limit excedido | IP={$ip} rota={$rota} tentativas={$count}"
            );

            return service('response')
                ->setStatusCode(429)
                ->setHeader('Retry-After', '60')
                ->setJSON([
                    'erro' => 'Muitas requisições. Aguarde um minuto.'
                ]);
        }

        // Incrementa contador (janela de 60s)
        $cache->save($key, $count + 1, 60);
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        // Nada a fazer
    }
}
