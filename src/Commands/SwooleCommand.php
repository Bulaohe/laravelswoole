<?php

namespace Bulaohe\LaravelSwoole\Commands;

use ReflectionClass;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class SwooleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole {action : start | stop | reload | reload_task | restart | quit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start laravel swoole';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        switch ($action = $this->argument('action')) {

            case 'start':
                $this->start();
                break;
            case 'restart':
                $pid = $this->sendSignal(SIGTERM);
                $time = 0;
                while (posix_getpgid($pid) && $time <= 10) {
                    usleep(100000);
                    $time++;
                }
                if ($time > 100) {
                    echo 'timeout' . PHP_EOL;
                    exit(1);
                }
                $this->start();
                break;
            case 'stop':
            case 'quit':
            case 'reload':
            case 'reload_task':

                $map = [
                    'stop' => SIGTERM,
                    'quit' => SIGQUIT,
                    'reload' => SIGUSR1,
                    'reload_task' => SIGUSR2,
                ];
                $this->sendSignal($map[$action]);
                break;

        }
    }

    protected function sendSignal($sig)
    {
        if ($pid = $this->getPid()) {

            posix_kill($pid, $sig);
        } else {

            echo "not running!" . PHP_EOL;
            exit(1);
        }
    }

    protected function start()
    {
        if ($this->getPid()) {
            echo 'already running' . PHP_EOL;
            exit(1);
        }

        $mode = config('swoole.base_config.mode');
        if (!$mode) {
            echo "LaravelSwoole needs Swoole." . PHP_EOL .
                "You can install Swoole by command:" . PHP_EOL .
                " pecl install swoole" . PHP_EOL;
            exit;
        }

        $wrapper = "Bulaohe\\LaravelSwoole\\Wrapper\\SwooleHttpWrapper";
        
        $ref = new ReflectionClass($wrapper);
        $wrapper_file = $ref->getFileName();

        $handler_config = [];
        $params = $wrapper::getParams();
        foreach ($params as $paramName => $default) {
            if (is_int($paramName)) {
                $paramName = $default;
                $default = null;
            }
            $key = $paramName;
            $value = config("swoole.handler_config.{$key}", function () use ($key, $default) {
                return env("SWOOLE_" . strtoupper($key), $default);
            });
            if ($value !== null) {
                if ((is_array($value) || is_object($value)) && is_callable($value)) {
                    $value = $value();
                }
                $handler_config[$paramName] = $value;
            }

        }

        $handler_config['dispatch_mode'] = 2;

        $host = config('swoole.base_config.host');
        
        $port = $this->input->getOption('port') ?? config('swoole.base_config.port');
        $socket = @stream_socket_server("tcp://{$host}:{$port}");
        if(!$socket) {
            throw new \Exception("Address {$host}:{$port} already in use", 1);
        } else {
            fclose($socket);
        }

        $configs = [
            'host' => $host,
            'port' => $port,
            'wrapper_file' => $wrapper_file,
            'wrapper' => $wrapper,
            'pid_file' => config('swoole.base_config.pid_file'),
            'root_dir' => base_path(),
            'callbacks' => config('swoole.base_config.callbacks'),
            // for swoole
            'handler_config' => $handler_config,
            // for wrapper, like http / fastcgi / websocket
            'wrapper_config' => config('swoole.wrapper_config'),
            'base_config' => config('swoole.base_config'),
        ];

        $handle = popen(PHP_BINARY . ' ' . __DIR__ . '/../../src/Entry.php', 'w');
        fwrite($handle, serialize($configs));
        fclose($handle);
    }

    protected function getPid()
    {

        $pid_file = config('swoole.base_config.pid_file');
        if (file_exists($pid_file)) {
            $pid = file_get_contents($pid_file);
            if (posix_getpgid($pid)) {
                return $pid;
            } else {
                unlink($pid_file);
            }
        }
        return false;
    }

}
