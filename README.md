```
8 8888        8 8 8888888888            .8.          8 888888888o. 8888888 8888888888 8 888888888o   8 8888888888            .8.    8888888 8888888888
8 8888        8 8 8888                 .888.         8 8888    `88.      8 8888       8 8888    `88. 8 8888                 .888.         8 8888
8 8888        8 8 8888                :88888.        8 8888     `88      8 8888       8 8888     `88 8 8888                :88888.        8 8888
8 8888        8 8 8888               . `88888.       8 8888     ,88      8 8888       8 8888     ,88 8 8888               . `88888.       8 8888
8 8888        8 8 888888888888      .8. `88888.      8 8888.   ,88'      8 8888       8 8888.   ,88' 8 888888888888      .8. `88888.      8 8888
8 8888        8 8 8888             .8`8. `88888.     8 888888888P'       8 8888       8 8888888888   8 8888             .8`8. `88888.     8 8888
8 8888888888888 8 8888            .8' `8. `88888.    8 8888`8b           8 8888       8 8888    `88. 8 8888            .8' `8. `88888.    8 8888
8 8888        8 8 8888           .8'   `8. `88888.   8 8888 `8b.         8 8888       8 8888      88 8 8888           .8'   `8. `88888.   8 8888
8 8888        8 8 8888          .888888888. `88888.  8 8888   `8b.       8 8888       8 8888    ,88' 8 8888          .888888888. `88888.  8 8888
8 8888        8 8 888888888888 .8'       `8. `88888. 8 8888     `88.     8 8888       8 888888888P   8 888888888888 .8'       `8. `88888. 8 8888
```

Heartbeat Client
================

The Heartbeat server gives you peace of mind that your cronhobs are running by listening out for 'beats' provided once your jobs complete successfully.  Beats are communicated to the server via this client (or write your own if you prefer!)

Basic Usage
-----------

Include the Heartbeat Client in your **composer.json**

```
"orvelo/heartbeat-client" : "^1.0"
```

Assuming you have required vendor/autoload.php then this is the minimum required steps to start the server listening:

```
\Orvelo\Heartbeat::task('Super-important task')
    ->token('TOKEN_GOES_HERE')
    ->source('live-server')
    ->every('15m')
    ->slop('2m')
    ->email('person@example.com');
```

Place this call (or one very much like it) in your code once you know for sure that a scheduled task has completed successfully.  The same code is used to register a task and update the server with a heartbeat.

Our example does the following:

1. Names the task "Super-important task" - this is an identifier so you know which script is acting up
2. Sets your access token - you can get one of these off us
3. Sets the source - so you know which server the messages are coming from (or if multiple servers deal with crons - the name of the cluster)
4. Sets the expected repetition time - In this case we're saying that this task will complete successfully every 15 minutes
5. Sets a 2 minute window - because you know your script sometimes takes a little longer
6. Gives an email address to send alerts to

The unique identifiers for tasks are the task name and the source.  You can have tasks with the same name across multiple sources if you have multiple servers performing the same task.

You can amend the task at any time by adjusting any of the functions that are not **task()**, **token()** and **source()**.  The next call to the server will act as a heartbeat received and make the changes.

Available functions
-------------------

**task(string)**
Set the task name. This will be used when identifying the task and in the email notifications that get sent

**token(string)**
Set your access token. Without a valid token the server will not respond

**every(string)**
Set your expected time between updates.  Some examples:
 - 1h 2m 5s
 - 3m
 - 180m
 - ..etc

**slop(string)**
Allow the task to not be so strict with timings - gives an extra <period> of time between warnings.  Uses the same format as **every()**

**email(string|string[])**
Set the email addresses that we're sending to

**source(string)**
Set the source

Cancelling a Heartbeat
----------------------

Send the task name and source along with a call to **cancel()** as so:

```
// cancel an existing task
\Orvelo\Heartbeat::task('Super-important task')
    ->token('TOKEN_GOES_HERE')
    ->source('live-server')
    ->cancel();
```

Sending Immediate Alerts
------------------------

You can also use the Heartbeat server as a notification system for some of the more egregious errors that your application creates.  If the worst happens chain a call to **ohDear()**:

```
\Orvelo\Heartbeat::task('This should never happen!')
    ->token('TOKEN_GOES_HERE')
    ->source('live-server')
    ->email('person@example.com')
    ->ohDear();
```
