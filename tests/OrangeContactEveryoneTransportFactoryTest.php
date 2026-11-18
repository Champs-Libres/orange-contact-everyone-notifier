<?php

namespace Tests\ChampsLibres\Notifier\Bridge\OrangeContacEveryone;

use ChampsLibres\Notifier\Bridge\OrangeContactEveryone\OrangeContactEveryoneTransportFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Notifier\Exception\IncompleteDsnException;
use Symfony\Component\Notifier\Exception\MissingRequiredOptionException;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/*
 * This test does not extend Symfony's TransportFactoryTestCase: it was removed
 * in Symfony 8, and its replacement does not exist in Symfony 6.4.
 */
#[CoversClass(OrangeContactEveryoneTransportFactory::class)]
class OrangeContactEveryoneTransportFactoryTest extends TestCase
{
    private function createFactory(): OrangeContactEveryoneTransportFactory
    {
        $eventDispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event, ?string $eventName = null): object
            {
                return $event;
            }
        };

        return new OrangeContactEveryoneTransportFactory(new ArrayAdapter(), $eventDispatcher, HttpClient::create());
    }

    #[DataProvider('supportsProvider')]
    public function testSupports(bool $expected, string $dsn): void
    {
        self::assertSame($expected, $this->createFactory()->supports(new Dsn($dsn)));
    }

    public static function supportsProvider(): iterable
    {
        yield [true, 'orangeContactEveryone://myUser:password@default?idGroup=someGroup'];
        yield [false, 'somethingElse://myUser:password@default?idGroup=someGroup'];
    }

    #[DataProvider('createProvider')]
    public function testCreate(string $expected, string $dsn): void
    {
        $transport = $this->createFactory()->create(new Dsn($dsn));

        self::assertSame($expected, (string) $transport);
    }

    public static function createProvider(): iterable
    {
        yield [
            'orangeContactEveryone://myUser@contact-everyone.orange-business.com?idGroup=someGroup',
            'orangeContactEveryone://myUser:password@default?idGroup=someGroup',
        ];
    }

    #[DataProvider('unsupportedSchemeProvider')]
    public function testUnsupportedSchemeException(string $dsn): void
    {
        $this->expectException(UnsupportedSchemeException::class);

        $this->createFactory()->create(new Dsn($dsn));
    }

    public static function unsupportedSchemeProvider(): iterable
    {
        yield ['somethingElse://myUser:password@default?idGroup=someGroup'];
    }

    #[DataProvider('incompleteDsnProvider')]
    public function testIncompleteDsnException(string $dsn): void
    {
        $this->expectException(IncompleteDsnException::class);

        $this->createFactory()->create(new Dsn($dsn));
    }

    public static function incompleteDsnProvider(): iterable
    {
        yield ['orangeContactEveryone://myUser@default?idGroup=someGroup'];
        yield ['orangeContactEveryone://default?idGroup=someGroup'];
    }

    #[DataProvider('missingRequiredOptionProvider')]
    public function testMissingRequiredOptionException(string $dsn): void
    {
        $this->expectException(MissingRequiredOptionException::class);

        $this->createFactory()->create(new Dsn($dsn));
    }

    public static function missingRequiredOptionProvider(): iterable
    {
        yield ['orangeContactEveryone://myUser:password@default'];
    }
}
