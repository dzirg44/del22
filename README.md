## About

FuseForge_Stomp port

What is [Stomp](https://stomp.github.io/)?

## Installing

```
  $ composer config repositories.amq-stomp vcs https://github.com/dzirg44/del22;
  $ composer require dzirg44/del22:^0.2;

```

## Running Examples

Examples are located in `examples` folder. Before running them, be sure you have installed this library properly and you have started ActiveMQ broker (recommended version 5.5.0 or above) with [Stomp connector enabled] (http://activemq.apache.org/stomp.html).

Also, be sure to check comments in the particular examples for some special configuration steps (if needed).

## Tests

The tests at the moment need a running instance of activeMQ listening on the
default STOMP Port 61613.

## Thanks

 * Hiram Chirino <hiram@hiramchirino.com>
 * Dejan Bosanac <dejan@nighttale.net> 
 * Michael Caplan <mcaplan@labnet.net>
