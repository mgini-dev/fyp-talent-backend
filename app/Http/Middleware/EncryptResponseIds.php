<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\IdHasher;
use Illuminate\Http\JsonResponse;

class EncryptResponseIds
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            if (is_array($data)) {
                $this->encryptArray($data);
                $response->setData($data);
            }
        }

        return $response;
    }

    private function encryptArray(&$array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $this->encryptArray($value);
            } else {
                if ($key === 'id' || str_ends_with($key, '_id')) {
                    if (is_numeric($value)) {
                        $value = IdHasher::encode($value);
                    }
                }
            }
        }
    }
}
