# Don't reprocess a message that caused consumer to die

This example showcases how we can handle and treat a particular set of messages,
the ones that are causing the consumers to die (exceptions, fatal errors, etc).

In the absence of an ack/nack from the consumer (assuming no auto_ack), generally
the message will get back in the queue.

In most cases it is safe to assume that this is a reproducible event, which will
cause the message to be requeued and reprocessed infinitely, leading to stuck
consumers.

The listener properties that we use are:

* skip_if_redelivered - this behavior, if enabled, will catch any messages that
are marked as redelivered and it will not pass them to the processor next time
the message is encountered

# How does it work

This pattern is based on the RabbitMQ provided property "isRedelivery" present
upon receiving the envelope. This allows us to detect messages that have triggered or
encountered an exceptional error condition in the consumer, leading to conenction/channel
being closed.

The flow is explained below:
* Assume __main_exchange__ as being the initial exchange where the message gets published
* Assume __main_queue__ as being the main processing queue
* Assume __dlx_exchange__ as being the __main_queue__ dead letter exchange
* Assume __dlx_queue__ as being the final dead-letter queue

```
1: message gets published on main_exchange
2: message ends up in main_queue
3: uncaucght exception or fatal error happens before ack/nack
4: consumer dies
5: message is back in the queue
6: message gets redelivered to consumer
7: if message is a redelivery, it gets nack-ed immediately and not passed to processor
```

##### Note: the operation of closing the channel/connection is the only operation marking the message as redelivered. No other operation (like nack) has the same behavior!
