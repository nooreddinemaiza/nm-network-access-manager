<?php

use Core\System\Environment;

$env = new Environment();
return [
    "default" => $env->get("DB_DRIVER", "mysql"),
    "connections"  =>  [
        "mysql"  => [
            'driver' =>  $env->get('DB_DRIVER', 'mysql'),
            'host' =>  $env->get('DB_HOST'),
            'database' =>  $env->get('DB_DATABASE'),
            'username' =>  $env->get('DB_USERNAME'),
            'password' =>  $env->get('DB_PASSWORD'),
            'port'  =>  $env->get('DB_PORT'),
            'charset'   => $env->get('DB_CHARSET'),
        ],
        // "pgsql" => [
        //     'driver' => $env->get('PGSQL_DRIVER'),
        //     'host' => $env->get('PGSQL_HOST'),
        //     'database' => $env->get('PGSQL_DATABASE'),
        //     'username' => $env->get('PGSQL_USERNAME'),
        //     'password' => $env->get('PGSQL_PASSWORD'),
        //     'port' => $env->get('PGSQL_PORT'),
        //     'charset' => $env->get('PGSQL_CHARSET'),
        //     'schema' => $env->get('PGSQL_SCHEMA'),
        // ],
        // "sqlite" => [
        //     'driver' => $env->get('SQLITE_DRIVER'),
        //     'database' => $env->get('SQLITE_DATABASE'),
        // ],
        // "sqlsrv" => [
        //     'driver' => $env->get('SQLSRV_DRIVER'),
        //     'host' => $env->get('SQLSRV_HOST'),
        //     'database' => $env->get('SQLSRV_DATABASE'),
        //     'username' => $env->get('SQLSRV_USERNAME'),
        //     'password' => $env->get('SQLSRV_PASSWORD'),
        //     'port' => $env->get('SQLSRV_PORT'),
        //     'charset' => $env->get('SQLSRV_CHARSET'),
        // ],
    ]
];
