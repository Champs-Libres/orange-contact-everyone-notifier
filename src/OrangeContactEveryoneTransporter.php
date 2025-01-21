<?php

namespace ChampsLibres\Notifier\Bridge\OrangeContactEveryone;

use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Message\SentMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OrangeContactEveryoneTransporter extends AbstractTransport
{
    protected const HOST = 'contact-everyone.orange-business.com';

    public function __construct(private readonly string $username, private readonly string $password, private readonly string $idGroup, private AdapterInterface $cache, ?HttpClientInterface $client = null, ?EventDispatcherInterface $dispatcher = null)
    {
        parent::__construct($client, $dispatcher);
    }

    private function getToken(): string
    {
        return $this->cache->get('orange-contact-everyone-token', function (ItemInterface $item) {
            $response = $this->client->request(
                'POST',
                \sprintf('https://%s/api/v1.2/oauth/token', $this->getEndpoint()),
                [
                    'headers' => ['Accept' => 'application/json'],
                    'body' => ['username' => $this->username, 'password' => $this->password],
                ]
            );

            try {
                $statusCode = $response->getStatusCode();
            } catch (TransportExceptionInterface $e) {
                throw new TransportException('Could not reach the remote orange-contact-everyone server.', $response, 0, $e);
            }

            if (200 !== $statusCode) {
                throw new TransportException(\sprintf('Unable to get the authentication token: %s.', $statusCode), $response);
            }

            $success = $response->toArray(false);

            $item->set($success['access_token']);
            $item->expiresAfter($success['ttl'] - 1);

            return $item->get();
        });
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        $body = [
            'msisdns' => [$message->getRecipientId()],
            'smsParam' => [
                'encoding' => 'GSM7',
                'body' => $message->getSubject(),
            ],
        ];

        $response = $this->client->request(
            'POST',
            \sprintf('https://%s/api/v1.2/groups/%s/diffusion-requests', $this->getEndpoint(), $this->idGroup),
            [
                'headers' => ['Accept' => 'application/json', 'Authorization' => 'Bearer '.$this->getToken()],
                'json' => $body,
            ]
        );

        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach the remote orange-contact-everyone server.', $response, 0, $e);
        }

        if (201 !== $statusCode) {
            throw new TransportException(\sprintf('Unable to send the SMS: statusCode: %s, errors: %s', $statusCode, implode(', ', array_map(function ($error) {
                $str = '';
                foreach ($error as $k => $v) {
                    $str .= \sprintf(' %s: %s', $k, $v);
                }

                return $str;
            }, $response->toArray(false)))), $response);
        }

        $success = $response->toArray(false);

        if (!isset($success['id'])) {
            throw new TransportException('Unable to get the id', $response);
        }

        $sentMessage = new SentMessage($message, (string) $this);
        $sentMessage->setMessageId($success['id']);

        return $sentMessage;
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage;
    }

    public function __toString(): string
    {
        return \sprintf('orangeContactEveryone://%s@%s?idGroup=%s', $this->username, $this->getEndpoint(), $this->idGroup);
    }
}
