<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\IdHasher;

class DecryptIds
{
    public function handle($request, Closure $next)
    {
        // 1. Decrypt Route Parameters (URLs)
        $route = $request->route();
        if ($route) {
            $params = $route->parameters();
            foreach ($params as $key => $value) {
                if (is_string($value) && strlen($value) > 20) {
                    $decoded = IdHasher::decode($value);
                    if ($decoded !== null) {
                        $route->setParameter($key, $decoded);
                    }
                }
            }
        }

        // 2. Decrypt Request Body (Inputs)
        $input = $request->all();
        $this->decryptArray($input);
        $request->replace($input);

        return $next($request);
    }

    private function decryptArray(&$array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $this->decryptArray($value);
            } elseif (is_string($value) && strlen($value) > 20) {
                if (str_ends_with($key, '_id') || $key === 'id') {
                    $decoded = IdHasher::decode($value);
                    if ($decoded !== null) {
                        $value = $decoded;
                    }
                }
            }
        }
    }
}
