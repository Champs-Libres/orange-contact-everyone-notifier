<?php

namespace ChampsLibres\Notifier\Bridge\OrangeContactEveryone;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Notifier\Exception\IncompleteDsnException;
use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OrangeContactEveryoneTransportFactory extends AbstractTransportFactory
{
    public function __construct(private readonly AdapterInterface $cache, ?EventDispatcherInterface $dispatcher = null, ?HttpClientInterface $client = null)
    {
        parent::__construct($dispatcher, $client);
    }

    protected function getSupportedSchemes(): array
    {
        return ['orangeContactEveryone'];
    }

    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();

        if ('orangeContactEveryone' !== $scheme) {
            throw new UnsupportedSchemeException($dsn, 'orangeContactEveryone', $this->getSupportedSchemes());
        }

        $user = $dsn->getUser();
        $password = $dsn->getPassword();
        $idGroup = $dsn->getRequiredOption('idGroup');

        if (null === $user) {
            throw new IncompleteDsnException("Missing parameter: 'user'", $dsn->getOriginalDsn());
        }
        if (null === $password) {
            throw new IncompleteDsnException("Missing parameter: 'password'", $dsn->getOriginalDsn());
        }

        return
            new OrangeContactEveryoneTransporter($user, $password, $idGroup, $this->cache, $this->client, $this->dispatcher)
        ;
    }
}
