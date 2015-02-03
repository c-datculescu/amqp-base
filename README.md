# amqp-base

A small php library that allows fast and easy development of applications interacting with AMQP based brokers.

This library is tested mainly with RabbitMQ, but should work fine with other AMQP brokers like ActiveMQ.

Currently it supports all the extensions over the AMQP protocol provided by RabbitMQ:
 - dead-lettering
 - alternate-exchanges
 - queue/message ttl
 - queue max size

Examples are provided using symfony DI for basic listeners, basic publishers and rpc. Any complex topologies can be implemented on top of the current library without problems.
