# amqp-base

Small library that intends to abstract common AMQP usages. Also provides integration and interoperability between the
two major amqp implementations available in php:

* [php-amqp extension](https://github.com/pdezwart/php-amqp) - very fast and lightweight, but lacking some of the features
* [php-amqplib library](https://github.com/videlalvaro/php-amqplib) - binary amqp protocol implementation in php
    
The current implementation abstracts the lower level components like queues, exchanges, channels and connections, allowing
access only to a set of useful operations on those components.

* listen - allows blocking listening on a specified queue
* publish - allows publishing on a specific exchange

**What is the point of offering a bridge between the two implementations?**

The reasoning is simple: offer a library that does not necessarily require php-amqp extension or php-amqplib. So the point 
in the end is *flwexibility*. In the mean time, since the php-amqp extension does not offer support for a few features present
in some of the brokers (like [RabbitMQ](http://www.rabbitmq.com)) we wanted to give the option to use something that offers
this support.
    
## Configuration

The configuration needed for the library allows you to define most of your infrastructure in one go, automatically detecting
dependencies between various components. The dependency detection is based on:

* bindings - queue to exchange and exchange to exchange
* alternate-exchange argument - during exchange declaration
* dead-letter-exchange argument - during queue declaration
    
Full set of configuration options are present below:
```yaml
connections:
    main:
        host: 'localhost'
        port: 5672
        vhost: /
        login: guest
        password: guest
        connect_timeout: 0
        read_timeout: 0
        write_timeout: 0
        heartbeat: 10           # option is only available for php-amqplib and php-amqp >=1.6beta3
        keepalive: true         # this option is available only for php-amqplib
        prefetch_count: 3       # available in the connection level although is a channel option

exchange:
    global:
        name: 'global'
        connection: 'main'      # the identifier for the connection to be used along with this exchange
        durable: true
        type: 'topic'
        passive: false
        arguments: [
            'alternate-exchange': 'ae'
        ]
queue:
    global:
        name: 'global'
        passive: false
        durable: true
        exclusive: false
        auto_delete: false
        arguments: [
            'dead-letter': 'dl'
        ]
```

ATTENTION: The arguments do not reflect the reality and they depend on the broker implementation. Please look them up in
your broker specification.

## Configuration default values
### Connection:

* host - localhost
* port - 5672
* vhost - /
* login - guest
* password - guest
* connect_timeout - 0
* read_timeout - 0
* write_timeout - 0
* heartbeat - 10
* keepalive - true
* prefetch_count - 3

### Exchange

* name - no default
* connection - no default
* durable - true
* type - topic
* passive - false
* arguments - []

### Queue

* name - no default
* passive - false
* durable - true
* exclusive - false
* auto_delete - false
* arguments - []

### Publishing properties

* mandatory - false
* immediate - false

### Listening properties

* consumer_tag - no default
* multi_ack - false
* auto_ack - false

## How to use it

The configuration:
```php
$config = [
              'connections' => [
                  'main' => [
                      'host'     => '192.168.56.52',
                      'port'     => 5672,
                      'login'    => 'admin',
                      'password' => 'mort487',
                      'vhost'    => '/'
                  ],
              ],
              'exchanges'   => [
                  'global' => [
                      'name'       => 'global',
                      'connection' => 'main',
                      'flags'      => ['durable'],
                      'type'       => 'topic'
                  ]
              ],
              'queues' => [
                  'debug' => [
                      'name' => 'debug',
                      'flags' => ['durable'],
                      'connection' => 'main',
                      'bindings' => [
                          ['exchange' => 'global', 'routing_key' => '#'],
                          ['exchange' => 'global', 'routing_key' => '']
                      ]
                  ]
              ]
          ];
```

How to use the php-amqplib adapter:

```php
require_once __DIR__ . '/../vendor/autoload.php';

$adapter = new \Amqp\Adapter\AmqplibAdapter($config);

$msg = new \Amqp\Message\Message();
$msg->setPayload('asjghjafhglkag');
$msg->setDeliveryMode(2);
$msg->setHeaders(['x-foo' =>'sfgsd']);

$adapter->publish('global', $msg);
$adapter->listen(
    'debug',
    function ($msg) {
        var_dump($msg);
    }
);
```

How to use the php-amqpext adapter:
```php
use Amqp\Adapter\ExtAdapter;
use Amqp\Consumer;
use Amqp\Message\MessageInterface;
use Amqp\Publisher;

require_once __DIR__ . '/../vendor/autoload.php';

$adapter = new ExtAdapter($config);

$publisher = new Publisher();
$publisher->setAdapter($adapter);
$routing_keys = ['foo', 'bar', 'foo.bar', 'bar.foo', null];

for ($i = 0; $i < 10; $i++) {
    $msg = new Amqp\Message();
    $msg->setPayload('Message ' . $i);
    $publisher->publish('global', $msg, $routing_keys[rand(0, count($routing_keys) - 1)]);
}

$consumer = new Consumer();
$consumer->setAdapter($adapter);

$consumer->listen('debug', function (MessageInterface $msg, Amqp\Message\Result $result) use (&$i) {
    print_r([
        'payload'    => $msg->getPayload(),
    ]);

    $result->ack()->nack()->requeue()->stop();
    echo 'test' . PHP_EOL;
});


echo 'Finished' . PHP_EOL;
```