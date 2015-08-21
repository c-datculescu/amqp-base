# AMQP Base
v0.3.28
    * Changed heartbeat to be defaulted to 10 seconds. Set to 0 seconds if you need it disabled
    * Implemented heartbeat
    * Implemented support for deleting bindings
    * Implemented consumer tag
    * Changed s2 dependencies to 2.\* to allow older versions 
    * Messages are now durable by default

v0.3.16 - 2015-02-22

    * Fixed bug with rpc consumer
    * Added require-dev
    * Fixed the examples to run on localhost with default users/passwords
    * Changed examples adding better defaults

v0.3.15 - 2015-02-22

    * Added support for empty queue names

v0.3.14 - 2015-02-22
    
    * Replace libevent with localized time counter for rpc publisher
    * Normalized RPC publisher to accept only one processor
    * Updated dependencies for pecl-amqp from 1.0.0 to 1.4.0 minimum
    * Added non-blocking (pull-based) consumer
