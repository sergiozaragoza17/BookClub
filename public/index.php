<?php
error_reporting(E_ALL ^ E_DEPRECATED);
@ini_set('assert.warning', 0);
//error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
