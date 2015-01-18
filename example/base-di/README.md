# Usage of the base builder
The following example shows how the base builder can be used in order to expose the basic entities used in AMQP
extension.

The builder can expose connections, channels, exchanges and queues to be used directly in the implementing code.

The examples are using **symfony 2 dependency injection** but this is not a required dependency for the library. You will 
  have to manually include it.
The configuration for the services used is located in the examples/config under services.yml.

The example showcases how to:
    - read the configuration for all the amqp configurations
    - link them together (parsing, etc)
    - retrieve a exchange
    - retrieve a queue