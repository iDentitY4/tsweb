<?php

namespace App\TsWeb;

use App\Connection as ConnectionModel;
use Closure;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use App\Contracts\TsWeb\Factory as FactoryContract;

class TsManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved queue connections.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The array of resolved queue connectors.
     *
     * @var array
     */
    public $connectors = [];

    /**
     * Create a new queue manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Determine if the driver is connected.
     *
     * @param  ConnectionModel $connectionModel
     * @return bool
     */
    public function connected(ConnectionModel $connectionModel = null)
    {
        $connectionModel = $connectionModel ?: $this->getDefaultConnectionModel();
        $driver = $connectionModel->driver;

        return isset($this->connections[$driver]);
    }

    /**
     * Resolve a queue connection instance.
     *
     * @param  ConnectionModel  $connectionModel
     * @return \App\Contracts\TsWeb\Connection
     */
    public function connection(ConnectionModel $connectionModel = null)
    {
        $connectionModel = $connectionModel ?: $this->getDefaultConnectionModel();

        $driver = $connectionModel->driver;

        // If the connection has not been resolved yet we will resolve it now as all
        // of the connections are resolved when they are actually needed so we do
        // not make any unnecessary connection to the various queue end-points.
        if (! isset($this->connections[$driver])) {
            $this->connections[$driver] = $this->resolve($connectionModel);
        }

        return $this->connections[$driver];
    }

    /**
     * Resolve a queue connection.
     *
     * @param  ConnectionModel  $connectionModel
     * @return \Illuminate\Contracts\Queue\Queue
     */
    protected function resolve(ConnectionModel $connectionModel)
    {
        $config = $connectionModel->getAttributes();
        $config['host'] = $connectionModel->host->address;

        return $this->getConnector($config['driver'])
                        ->connect($config);
    }

    /**
     * Get the connector for a given driver.
     *
     * @param  string  $driver
     * @return \Illuminate\Queue\Connectors\ConnectorInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function getConnector($driver)
    {
        if (! isset($this->connectors[$driver])) {
            throw new InvalidArgumentException("No connector for [$driver]");
        }

        return call_user_func($this->connectors[$driver]);
    }

    /**
     * Add a queue connection resolver.
     *
     * @param  string    $driver
     * @param  \Closure  $resolver
     * @return void
     */
    public function extend($driver, Closure $resolver)
    {
        return $this->addConnector($driver, $resolver);
    }

    /**
     * Add a queue connection resolver.
     *
     * @param  string    $driver
     * @param  \Closure  $resolver
     * @return void
     */
    public function addConnector($driver, Closure $resolver)
    {
        $this->connectors[$driver] = $resolver;
    }

    /**
     * Get the queue connection configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        if (! is_null($name) && $name !== 'null') {
            return $this->app['config']["ts.connections.{$name}"];
        }

        return ['driver' => 'null'];
    }

    /**
     * Get the name of the default queue connection.
     *
     * @return ConnectionModel
     */
    public function getDefaultConnectionModel()
    {
        return ConnectionModel::default();
    }

    /**
     * Get the full name for the given connection.
     *
     * @param  string  $connection
     * @return string
     */
    public function getName($connection = null)
    {
        return $connection ?: $this->getDefaultDriver();
    }

    /**
     * Determine if the application is in maintenance mode.
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return $this->app->isDownForMaintenance();
    }

    /**
     * Dynamically pass calls to the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}
