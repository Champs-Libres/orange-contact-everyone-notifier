Orange Contact Everyone Notifier
================================

Provides [Orange Contact Everyone](https://contact-everyone.orange-business.com/api/docs/guides/index.html#contact-everyone) integration for Symfony Notifier (SMS only).

## DSN Example

```dotenv
ORANGE_CONTACT_EVERYONE_DSN=orangeContactEveryone://username:password@default?idGroup=595d04e6-d83f-11ef-8814-e7f0c28d4ad3"
```

Where

- username: is your username;
- password: is your password;
- idGroup: is the unique identifier of the group.

## Register the transport and configure it

```yaml 
framework:
    notifier:
        texter_transports:
            orange_connect_everyone: '%env(string:ORANGE_CONTACT_EVERYONE_DSN)%'

# You must register the factory manually, in order to be able to use it
services:
    ChampsLibres\Notifier\Bridge\OrangeContactEveryone\OrangeContactEveryoneTransportFactory:
        autoconfigure: true
        autowire: true
```
