<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;
use App\Filters\RateLimitFilter;

class Filters extends BaseFilters
{
    /**
     * Aliases dos filtros
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'performance'   => PerformanceMetrics::class,
        'ratelimit'     => RateLimitFilter::class,
    ];

    /**
     * Filtros obrigatórios
     * ⚠️ pagecache REMOVIDO (API nunca deve ser cacheada)
     */
    public array $required = [
        'before' => [
            'forcehttps',
        ],
        'after' => [
            'performance',
            'toolbar',
        ],
    ];

    /**
     * Filtros globais
     */
    public array $globals = [
        'before' => [
            'cors', // necessário para OPTIONS / fetch
        ],
        'after' => [
            'secureheaders', // CSP e headers de segurança
        ],
    ];

    /**
     * Filtros por método HTTP
     */
    public array $methods = [];

    /**
     * Filtros por rota
     */
    public array $filters = [];
}
