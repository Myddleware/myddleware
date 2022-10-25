<?php

namespace App\Service;

interface AlertBootstrapInterface
{
    public function primary(string $message): void;

    public function secondary(string $message): void;

    public function success(string $message): void;

    public function danger(string $message): void;

    public function warning(string $message): void;

    public function info(string $message): void;

    public function light(string $message): void;

    public function dark(string $message): void;
}
