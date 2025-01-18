<?php

namespace Tests\ChampsLibres\Notifier\Bridge\OrangeContacEveryone;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\Notifier\Message\SmsMessage;
use ChampsLibres\Notifier\Bridge\OrangeContactEveryone\OrangeContactEveryoneTransporter;

#[CoversClass(OrangeContactEveryoneTransporter::class)]
class OrangeContactEveryoneTransporterTest extends TestCase
{
    #[Group('integration')]
    public function testSendIntegration(): void
    {
        $cache = new ArrayAdapter();
        $transporter = new OrangeContactEveryoneTransporter(
            $_ENV['ORANGE_CONTACT_EVERYONE_USERNAME'],
            $_ENV['ORANGE_CONTACT_EVERYONE_PASSWORD'],
            $_ENV['ORANGE_CONTACT_EVERYONE_ID_GROUP'],
            $cache,
            HttpClient::create(),
            null,
        );

        $sms = new SmsMessage($_ENV['TEST_DESTINATION_NUMBER'], 'This is a test message');

        $sent = $transporter->send($sms);

        self::assertNotNull($sent->getMessageId());
    }

    public function testSend(): void
    {
        $client = new MockHttpClient([
            function ($method, $url, $options) {
                self::assertEquals('POST', $method);
                self::assertEquals('https://contact-everyone.orange-business.com/api/v1.2/oauth/token', $url);
                self::assertContains('Content-Type: application/x-www-form-urlencoded', $options['headers']);
                self::assertEquals('username=user&password=myPassword', $options['body']);

                return new JsonMockResponse([
                    'access_token' => 'long-access-token',
                    'ttl' => 90,
                ]);
            },
            function ($method, $url, $options) {
                self::assertEquals('POST', $method);
                self::assertEquals('https://contact-everyone.orange-business.com/api/v1.2/groups/my-id-group/diffusion-requests', $url);
                self::assertContains('Authorization: Bearer long-access-token', $options['headers']);
                self::assertContains('Content-Type: application/json', $options['headers']);

                $body = json_decode($options['body'], true, 512, JSON_THROW_ON_ERROR);

                self::assertIsArray($body);
                self::assertArrayHasKey('msisdns', $body);
                self::assertEquals($body['msisdns'], ['+32475123456']);
                self::assertArrayHasKey('smsParam', $body);
                self::assertIsArray($body['smsParam']);
                self::assertArrayHasKey('encoding', $body['smsParam']);
                self::assertEquals('GSM7', $body['smsParam']['encoding']);
                self::assertArrayHasKey('body', $body['smsParam']);
                self::assertEquals('This is a test message', $body['smsParam']['body']);

                return new JsonMockResponse([
                    'id' => '20253a84-d51c-11ef-85d8-33819866ef32',
                ], ['http_code' => 201]);
            },
            function ($method, $url, $options) {
                self::assertEquals('POST', $method);
                self::assertEquals('https://contact-everyone.orange-business.com/api/v1.2/groups/my-id-group/diffusion-requests', $url);

                return new JsonMockResponse([
                    'id' => '41d0386a-d59d-11ef-820e-5b834921d24c'
                ], ['http_code' => 201]);
            }
        ]);

        $transporter = new OrangeContactEveryoneTransporter(
            'user',
            'myPassword',
            'my-id-group',
            $cache = new ArrayAdapter(),
            $client
        );

        $sent = $transporter->send($sms = new SmsMessage('+32475123456', 'This is a test message'));

        self::assertEquals('20253a84-d51c-11ef-85d8-33819866ef32', $sent->getMessageId());
        self::assertSame($sms, $sent->getOriginalMessage());

        // we send a second sms, to ensure that the second message does not call the authentication twice
        $sent = $transporter->send($sms = new SmsMessage('+32475654321', 'This is a another test message'));
        self::assertEquals('41d0386a-d59d-11ef-820e-5b834921d24c', $sent->getMessageId());
    }
}
