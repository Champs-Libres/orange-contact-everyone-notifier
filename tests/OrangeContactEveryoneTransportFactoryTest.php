<?php

namespace Symfony\Component\Notifier\Bridge\Tests\OrangeContactEveryoneTransport;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Notifier\Test\TransportFactoryTestCase;
use Symfony\Component\Notifier\Bridge\OrangeContactEveryone\OrangeContactEveryoneTransportFactory;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(OrangeContactEveryoneTransportFactory::class)]
class OrangeContactEveryoneTransportFactoryTest extends TransportFactoryTestCase
{
    public function createFactory(): \Symfony\Component\Notifier\Transport\TransportFactoryInterface
    {
        $eventDispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event, ?string $eventName = null): object
            {
                return $event;
            }
        };
        return new OrangeContactEveryoneTransportFactory(new ArrayAdapter(), $eventDispatcher, \Symfony\Component\HttpClient\HttpClient::create());
    }

    public static function supportsProvider(): iterable
    {
        yield [
            true,
            'orangeContactEveryone://myUser:password@default?idGroup=someGroup'
        ];

        yield [
            false,
            'somethingElse://myUser:password@default?idGroup=someGroup'
        ];
    }


    public static function createProvider(): iterable
    {
        yield [
            'orangeContactEveryone://myUser@contact-everyone.orange-business.com?idGroup=someGroup',
            'orangeContactEveryone://myUser:password@default?idGroup=someGroup'
        ];
    }

    public static function unsupportedSchemeProvider(): iterable
    {
        yield [
            'somethingElse://myUser:password@default?idGroup=someGroup',
        ];
    }

    public static function incompleteDsnProvider(): iterable
    {
        yield [
            'orangeContactEveryone://myUser@default?idGroup=someGroup',
        ];

        yield [
            'orangeContactEveryone://default?idGroup=someGroup',
        ];
    }

    public static function missingRequiredOptionProvider(): iterable
    {
        yield [
            'orangeContactEveryone://myUser:password@default',
        ];
    }
}