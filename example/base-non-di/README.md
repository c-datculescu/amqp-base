# Usage of the base builder
The following example shows how the base builder can be used in order to expose the basic entities used in AMQP
extension.

The builder can expose connections, channels, exchanges and queues to be used directly in the implementing code.

The examples do not use any kind of dependency injection.

The example showcases how to:
    - read the configuration for all the amqp configurations
    - link them together (parsing, etc)
    - retrieve a connection
    - retrieve a channel
    - retrieve a exchange
    - retrieve a queue
    - dealing with infinitely recursive dependencies