<?php

namespace AppBundle\Adapter\Mailer\Mandrill;

use AppBundle\Adapter\Mailer as Mailer;
use GuzzleHttp\Client;

class MandrillMailer implements Mailer\MailerInterface
{
    protected $key;
    protected $client;
    protected $disableDelivery;
    protected $logger;
    // Arbitrary options specific to this adapter.
    protected $options = [];
    const RECIPIENT_CHUNK_SIZE = 1000;

    public function __construct($logger, $key, $disableDelivery = false, Client $client = null)
    {
        $this->logger = $logger;
        $this->key = $key;
        $this->client = $client;
        $this->disableDelivery = $disableDelivery;
        if ($client == null) {
            $this->client = new Client([
                'base_uri' => 'https://mandrillapp.com/api/1.0/',
                'timeout'  => 5,
            ]);
        }
    }

    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }

    public function getOption($key)
    {
        return $this->options[$key];
    }

    public function executeSend(Mailer\Message $message)
    {
        $payload = $this->createSendPayload($message);

        $delivery = new Mailer\Delivery();
        $delivery->sender = $message->from->address;
        $delivery->bulkId = $message->bulkId;
        $messageIds = [];

        if (!$this->disableDelivery) {

            $payloads = $this->splitRecipients($payload, self::RECIPIENT_CHUNK_SIZE);
            $numPayloads = count($payloads);

            foreach ($payloads as $i => $payload) {
                $requestBody = json_encode($payload);
                $num = $i+1;
                $this->logger->info("GET https://mandrillapp.com/api/1.0/messages/send.json (chunk #{$num}/{$numPayloads})");
                $this->logger->info("Request body: ".$requestBody);
                
                $response = $this->client->post('messages/send.json', [
                    'body' => $requestBody,
                    'http_errors' => false,
                ]);
                $body = json_decode($response->getBody());
                $this->logger->info("HTTP {$response->getStatusCode()}");
                $this->logger->info("Reponse body: {$response->getBody()}");

                if ($response->getStatusCode() != 200 || (isset($body->status) && $body->status == 'error')) {
                    $this->logger->error("Mandrill API error. See details above ^");
                } else {
                    foreach ($body as $sentMessage) {
                        $messageIds[] = $sentMessage->_id;
                    }
                }
            }
        }
        $delivery->messageIds = $messageIds;
        return $delivery;
    }

    public function executeCancel(Mailer\Delivery $delivery)
    {
        throw new Exception('Cancellation has been disabled.');
    }

    public function executeStats(Mailer\Delivery $delivery)
    {
        $payload = $this->createStatsPayload($delivery);

        if (!$this->disableDelivery) {
            $requestBody = json_encode($payload);
            $this->logger->info("GET https://mandrillapp.com/api/1.0/messages/search-time-series.json");
            $this->logger->info("Request body: ".$requestBody);
            $response = $this->client->post('messages/search-time-series.json', [
                'body' => $requestBody,
                'http_errors' => false,
            ]);

            $body = json_decode($response->getBody());
            $this->logger->info("HTTP {$response->getStatusCode()}");
            $this->logger->info("Reponse body: {$response->getBody()}");

            if ($response->getStatusCode() != 200 || (isset($body->status) && $body->status == 'error')) {
                $this->logger->error("Mandrill API error. See details above ^");
            } else {
                $this->setDeliveryStats($delivery, $body);
            }        
        }

        return $delivery;
    }

    public function executeBlacklist()
    {
        $payload = $this->createBlacklistPayload();

        if (!$this->disableDelivery) {
            $requestBody = json_encode($payload);
            $this->logger->info("GET https://mandrillapp.com/api/1.0/rejects/list.json");
            $this->logger->info("Request body: ".$requestBody);
            $response = $this->client->post('rejects/list.json', [
                'body' => $requestBody,
                'http_errors' => false,
            ]);

            $body = json_decode($response->getBody());
            $this->logger->info("HTTP {$response->getStatusCode()}");
            $this->logger->info("Reponse body: {$response->getBody()}");

            if ($response->getStatusCode() != 200 || (isset($body->status) && $body->status == 'error')) {
                $this->logger->error("Mandrill API error. See details above ^");
            } else {
                $events = [];
                foreach ($body as $entry) {
                    $event = new Mailer\Event();
                    if (in_array($entry->reason, ['hard-bounce', 'soft-bounce'])) {
                        $entry->reason = 'bounce';
                    }
                    if (in_array($entry->reason, ['unsub'])) {
                        $entry->reason = 'unsubscribe';
                    }
                    $event->type = $entry->reason;
                    $event->address = $entry->email;
                    $event->createdDate = new \DateTime($entry->created_at);
                    $event->data = $entry;
                    $events[$event->address] = $event;
                }
                return $events;
            }
        }
    }

    /**
     * Documentation: https://mandrillapp.com/api/docs/messages.JSON.html#method=send
     * Recipients limitation: https://mandrill.zendesk.com/hc/en-us/articles/205582557-Can-I-send-to-more-than-one-recipient-at-a-time-
     */
    public function createSendPayload(Mailer\Message $message)
    {
        $attachments = [];
        foreach ($message->attachments as $file) {
            $attachments[] = [
                'type' => $file->mimeType,
                'name' => $file->name,
                'content' => base64_encode($file->content),
            ];
        }

        $to = [];
        foreach ($message->to as $email) {
            $to[] = [
                'email' => $email->address,
                'name' => $email->name,
                'type' => $email->type,
            ];
        }

        $payload = [
            'key' => $this->key,
            'message' => [
                'subaccount' => $this->getOption('subaccount'),
                'html' => $message->html,
                'subject' => $message->subject,
                'from_email' => $message->from->address,
                'from_name' => $message->from->name,
                'to' => $to,
                'track_opens' => $message->options->trackOpens,
                'attachments' => $attachments,
                'metadata' => [
                    'id' => $message->bulkId
                ],
                'preserve_recipients' => false,
            ],
            'async' => true,
            // 'ip_pool' => '',
        ];
        // Scheduling costs money. Don't schedule if there is no need for it.
        // if ($message->sendDate != null) {
        //     $payload['send_at'] = $message->sendDate->format('Y-m-d H:i:s');
        // }
        return $payload;
    }

    public function createCancelPayload(Mailer\Delivery $delivery)
    {
        throw new Exception('Cancellation has been disabled.');
    }

    public function createStatsPayload(Mailer\Delivery $delivery)
    {
        $payload = [
            'key' => $this->key,
            'query' => 'u_id:' . $delivery->bulkId,
        ];
        return $payload;
    }

    public function createBlacklistPayload()
    {
        $payload = [
            'key' => $this->key,
            'subaccount' => $this->getOption('subaccount'),
            'include_expired' => false,
        ];
        return $payload;
    }

    public function setDeliveryStats(Mailer\Delivery $delivery, $timeSeries)
    {
        if (!is_array($timeSeries)) {
            throw new \Exception('Supplied time series must be an array.');
        }
        $delivery->stats = $timeSeries;
        foreach ($timeSeries as $hourlyStats) {
            $delivery->numSent += (int)$hourlyStats->sent;
            $delivery->numBounced += (int)$hourlyStats->hard_bounces + (int)$hourlyStats->soft_bounces;
            $delivery->numUnsubscribed += (int)$hourlyStats->unsubs;
            $delivery->numOpened += (int)$hourlyStats->opens;
            $delivery->numClicked += (int)$hourlyStats->clicks;
        }
    }

    public function splitRecipients($payload, $chunkSize)
    {
        $splitPayloads = [];
        $recipientChunks = array_chunk($payload['message']['to'], $chunkSize);
        foreach ($recipientChunks as $chunk) {
            $splitPayload = $payload;
            $splitPayload['message']['to'] = $chunk;
            $splitPayloads[] = $splitPayload;
        }
        return $splitPayloads;
    }
}