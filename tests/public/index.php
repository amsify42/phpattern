<?php
require_once __DIR__.'/../../vendor/autoload.php';
/**
 * Initiating Application
 */
$init = new \App\Init();
/**
 * Acquiring request and rendering the response
 */
render_response($init->acquireRequest());