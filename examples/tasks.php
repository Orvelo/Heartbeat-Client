<?php
require_once(dirname(__FILE__) .'/../vendor/autoload.php');

// register a new task / send a heartbeat for an existing one
\Orvelo\Heartbeat::task('Super-important task')
    ->token('TOKEN_GOES_HERE') // get your token from Orvelo
    ->source('live-server')
    ->every('15m')
    ->slop('2m')
    ->email('person@example.com'); // or an array of email addresses!

// cancel an existing task
\Orvelo\Heartbeat::task('Super-important task')
    ->token('TOKEN_GOES_HERE') // get your token from Orvelo
    ->source('live-server')
    ->cancel();

// in the event of a major exception you can get an alert
\Orvelo\Heartbeat::task('This should never happen!')
    ->token('TOKEN_GOES_HERE') // get your token from Orvelo
    ->source('live-server')
    ->email('person@example.com') // or an array of email addresses!
    ->ohDear();
