<?php

use Core\System\Environment;

$env = new Environment();

return [
    "name" => $env->get("APP_NAME", "ouddine"),
    "slogan" => $env->get("APP_SLOGAN", ""),
    "url" => $env->get("APP_URL", ""),
    "key" => $env->get("APP_key"),
    "env" => $env->get("APP_ENV", ""),
];
