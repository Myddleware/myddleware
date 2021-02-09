<?php

namespace App\Service;

interface AlertBootstrapInterface
{
    /**
     * Primary alert bootstrap.
     */
    public function primary(string $message): void;

    /**
     * Secondary alert bootstrap.
     */
    public function secondary(string $message): void;

    /**
     * Success alert bootstrap.
     */
    public function success(string $message): void;

    /**
     * Danger alert bootstrap.
     */
    public function danger(string $message): void;

    /**
     * Warning alert bootstrap.
     */
    public function warning(string $message): void;

    /**
     * Info alert bootstrap.
     */
    public function info(string $message): void;

    /**
     * Light alert bootstrap.
     */
    public function light(string $message): void;

    /**
     * Dark alert bootstrap.
     */
    public function dark(string $message): void;
}
