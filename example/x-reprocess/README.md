# Reprocess a message x times (where x is configurable)

This example showcases how to implement automatically reprocessing of a message n times and after republishing
the message on the final exchange where other patterns can be used for analysing the messages.

The purpose of this option is to use the default RabbitMQ control mechanisms for dealing with such messages and 
to allow the avoidance of infinite loops over timeout queues.

The listener properties used are:

* reprocess_counter - the number of times a message can be reprocessed
* reject_target_exchange - the exchange where if the message fails for n times is finally dispatched
* reject_target_routingKey - the routing key which the message gets dispatched on the last exchange

# How does it work

This pattern is based on the ability of RabbitMQ to implement some crucial additions:

* dead letter exchanges - read more [here](https://www.rabbitmq.com/dlx.html)
* timeout queues - read more [here](https://www.rabbitmq.com/ttl.html)
* custom headers when messages get dead lettered. For every dead-lettering a counter is increased in the x-death header.
This allows us to count how many times a message got into a particular queue and implement this pattern.

The flow is explained below in a couple of steps:
* Assume __main_exchange__ as being the initial exchange where the message gets published
* Assume __main_queue__ as being the main processing queue
* Assume __dlx_exchange__ as being the __main_queue__ dead letter exchange
* Assume __time_queue__ as being the timeout queue
* Assume __graveyard_exchange__ as being the final exchange to publish after the reprocessing number has been exceeded
* Assume __graveyard_queue__ as being the final queue after the reprocessing counter has been exceeded

```
1: message gets published on main_exchange
2: message ends up in main_queue
3: message gets rejected and dead-lettered on dlx_exchange
4: message gets in the time_queue
5: message times out, gets dead-lettered in main_exchange

repeat steps 1 - 5 n times.

6: message exceeds n number of reprocessing
7: message gets published on graveyard_exchange
8: message ends up in graveyard_queue

end
```