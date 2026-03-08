<?php
return [
    'secret_key'     => env('INTASEND_SECRET_KEY'),
    'publishable_key'=> env('INTASEND_PUBLISHABLE_KEY'),
    'test_mode'      => env('INTASEND_TEST', true),
];