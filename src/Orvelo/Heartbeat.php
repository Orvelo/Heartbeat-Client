<?php
namespace Orvelo;

/**
 * Heartbeat client class
 * Handles submission of data to the heartbeat server
 * @author  andrew <andrew@orvelo.com>
 */
class Heartbeat
{
    /**
     * Location of the Heartbeat server
     * @var string
     */
    const HEARTBEAT_SERVER = 'http://heartbeat.orvelo.com/beat';

    /**
     * State of tasks for this environment
     * @var int
     */
    const STATE_NORMAL = 0;

    /**
     * State of tasks for this environment
     * @var int
     */
    const STATE_PAUSE = 1;

    /**
     * State of tasks for this environment
     * @var int
     */
    const STATE_RESUME = 2;

    /**
     * Entrypoint to kick things off
     * @param  string $task_name name of the task that we're dealing with
     * @return self
     */
    public static function task($task_name)
    {
        return new self($task_name);
    }

    /**
     * Set your access token
     * @param  string $token
     * @return Heartbeat
     */
    public function token($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * This task should check-in every X period
     * Defined as a string (1d / 15m / 1d 3m)
     * @param  string $every period we're expecting
     * @return Heartbeat
     */
    public function every($every)
    {
        $this->every = $every;

        return $this;
    }

    /**
     * The task may take an extra few minutes to complete - define them here in the same format
     * @param  string $slop
     * @return Heartbeat
     */
    public function slop($slop)
    {
        $this->slop = $slop;

        return $this;
    }

    /**
     * Send the alerts to these email addresses
     * @param  mix $email either a single email or an array of emails
     * @return Heartbeat
     */
    public function email($email)
    {
        // either add an array of emails or just add one one to the end
        if (is_array($email)) {
            $this->email = $email;
        } else {
            $this->email[] = $email;
        }

        return $this;
    }

    /**
     * Cancel the healthcheck for this source & task combination
     * @return Heartbeat
     */
    public function cancel()
    {
        $this->cancel = true;

        return $this;
    }

    /**
     * Something has gone seriously wrong and this alert needs to go out immediately
     * @return Heartbeat
     */
    public function ohDear()
    {
        $this->ohDear = true;

        return $this;
    }

    /**
     * Set the source - could be the hostname of your server or something to denote that
     * it's a cron / web / db server, etc
     * @param  string $source
     * @return Heartbeat
     */
    public function source($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Pause all jobs running on this environment
     * @return Heartbeat
     */
    public function pause()
    {
        $this->state = self::STATE_PAUSE;
    }

    /**
     * Resume all jobs running on this environment
     * @return Heartbeat
     */
    public function resume()
    {
        $this->state = self::STATE_RESUME;
    }

    /**
     * Protected construct as this should only be invoked by ::task()
     */
    protected function __construct($task_name)
    {
        $this->task = $task_name;
    }

    /**
     * On destruct we send the actual request off
     */
    public function __destruct()
    {
        $this->dispatch();
    }

    /**
     * Send the request off
     * @return void
     */
    protected function dispatch()
    {
        // build the payload
        $payload = array(
            'token' => $this->token,
            'task'  => $this->task,
            'host'  => $this->source,
            'email' => $this->email,
            'every' => $this->every,
            'slop'  => $this->slop,
            'state' => $this->state,
        );

        // add in cancel / oh no as necessary
        if ($this->cancel) {
            $payload['cancel'] = true;
        }

        if ($this->ohDear) {
            $payload['oh_dear'] = true;
        }

        // server expects just "payload" with the rest JSON encoded
        $params = array(
            'payload' => json_encode($payload)
        );

        // curl would be nice, but no biggie if it's not there
        if (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt_array(
                $curl,
                array(
                    CURLOPT_ENCODING       => '',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_URL            => self::HEARTBEAT_SERVER,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => http_build_query($params),
                )
            );

            $raw_response = curl_exec($curl);
            curl_close($curl);
        } else {
            // contexts are a thing and help with post!
            $raw_response = @file_get_contents(
                self::HEARTBEAT_SERVER,
                false,
                stream_context_create(array(
                    'http' => array(
                        'method'  => 'POST',
                        'header'  => 'Content-type: application/x-www-form-urlencoded',
                        'content' => http_build_query($params)
                    ),
                ))
            );
        }

        if ($raw_response === false) {
            error_log('Could not communicate with the Heartbeat server');
            return;
        }

        // attempt to turn it back so we can figure out what we have
        $response = json_decode(trim($raw_response), 1);

        // couldn't decode the message, whoops
        if ($response === false) {
            error_log('Invalid response from Heartbeat server');
            return;
        }

        // check for success and a message - otherwise we just go full generic
        if (isset($response['success']) && $response['success'] === true) {
            // whoop
            return;
        } elseif (isset($response['message'])) {
            error_log('Heartbeat server returned an error: '. $response['message']);
            return;
        } else {
            error_log('General Heartbeat server error, sorry!  Please contact us :|');
            return;
        }
    }

    /**
     * Your client token
     * @var string
     */
    protected $token = '';

    /**
     * Every X period of time (1h / 1m / 1h 2m / 1d)
     * @var string
     */
    protected $every = '';

    /**
     * Slop factor - same format as every
     * @var string
     */
    protected $slop = '';

    /**
     * Array of emails to send the alert to
     * @var array
     */
    protected $email = [];

    /**
     * Whether we're cancelling an existing task or not
     * @var boolean
     */
    protected $cancel = false;

    /**
     * Whether this is an "oh dear" message
     * @var boolean
     */
    protected $ohDear = false;

    /**
     * State of the task - pause / resume / normal
     * @var string
     */
    protected $state = self::STATE_NORMAL;

    /**
     * The source of the alert
     * @var string
     */
    protected $source = '';

    /**
     * Task name
     * @var string
     */
    protected $task = '';
}
