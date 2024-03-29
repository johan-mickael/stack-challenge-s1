<?php

namespace App\Service\Request;

use Symfony\Component\HttpFoundation\Request;

class RequestQueryService
{
    private Request $request;

    public function __construct()
    {
        $this->request = Request::createFromGlobals();
    }

    public function all(?string $key): array
    {
        return $this->request->query->all($key);
    }

    public function get(string $key): mixed
    {
        return $this->request->query->get($key);
    }

    public function has(string $key): bool
    {
        return $this->request->query->has($key);
    }
}