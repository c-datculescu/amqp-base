# amqp-base

Small library that can be used with any AMQP 0.9.1 compatible broker.

Currently the library offers access to low-level components like:

    * connections
    * channels
    * queues
    * exchanges
 
Also the library offers support for higher-level components like consumers and publishers.
 
All the components can be configured via an extensible st of configuration directives, which can also serve as a 
description for an entire set of architectures.

The library currently implements support for most of the RabbitMQ extensions like:

    * dead-lettering
    * ttl
    * length and size of queues
    * alternate-exchanges
    * exchange to exchange bindings
    
The configuration examples can be located in /example directory as well as in the config directory.

The library offers support for dependencies as well as cyclic dependency detection so it is possible to define entire
infrastructures using the dependency system.

For more examples please check the **examples** directory.

## Todo

    * implement support for high availability options
    * implement support for multiple configuration files merge
