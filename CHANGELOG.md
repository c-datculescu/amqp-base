# AMQP Base

v0.3.15 - 2015-02-22

    * Added support for empty queue names

v0.3.14 - 2015-02-22
    
    * Replace libevent with localized time counter for rpc publisher
    * Normalized RPC publisher to accept only one processor
    * Updated dependencies for pecl-amqp from 1.0.0 to 1.4.0 minimum
    * Added non-blocking (pull-based) consumer
