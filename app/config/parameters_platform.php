<?php

/**
 * @file
 * Set parameters from Platform.sh environment variables.
 */

// Configure the database.
if (isset($_ENV['PLATFORM_RELATIONSHIPS'])) {
    $dbRelationshipName = 'database';
    $relationships = json_decode(base64_decode($_ENV['PLATFORM_RELATIONSHIPS']), true);
    foreach ($relationships[$dbRelationshipName] as $endpoint) {
        if (!empty($endpoint['query']['is_master'])) {
            $container->setParameter('database_driver', 'pdo_'.$endpoint['scheme']);
            $container->setParameter('database_host', $endpoint['host']);
            $container->setParameter('database_port', $endpoint['port']);
            $container->setParameter('database_name', $endpoint['path']);
            $container->setParameter('database_user', $endpoint['username']);
            $container->setParameter('database_password', $endpoint['password']);
            $container->setParameter('database_path', '');
            break;
        }
    }
}

// Set a default unique secret, based on a project-specific entropy value.
if (isset($_ENV['PLATFORM_PROJECT_ENTROPY'])) {
    $container->setParameter('kernel.secret', $_ENV['PLATFORM_PROJECT_ENTROPY']);
}
