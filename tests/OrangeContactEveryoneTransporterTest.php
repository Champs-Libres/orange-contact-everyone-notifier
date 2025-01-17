<?php

namespace Symfony\Notifier\Bridge\OrangeContactEveryone\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Bridge\OrangeContactEveryone\OrangeContactEveryoneTransporter;

class OrangeContactEveryoneTransporterTest extends TestCase
{
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
}
