<?php
// webhook.php

require_once __DIR__ . '/../../config/config.php';

$basePath = dirname(__DIR__, 2);

require_once $basePath . '/libraries/ModelAdmin.php';
require_once $basePath . '/libraries/Database.php';
require_once $basePath . '/libraries/Model.php';
require_once $basePath . '/app/models/Project.php';
require_once $basePath . '/app/models/Donor.php';
require_once $basePath . '/app/models/Messaging.php';

class PayfortWebhook
{
    private $SHAResponsePhrase = '18rBnypfYP/04yelRkftp.$!';

    private $projectModel;
    private $donorModel;
    private $messagingModel;

    public function __construct()
    {
        $this->projectModel = new Project();
        $this->donorModel = new Donor();
        $this->messagingModel = new Messaging();
    }

    public function handle()
    {
        try {
            $fortParams = $_POST;

            $this->log("Webhook Received: " . json_encode($fortParams));

            if (empty($fortParams)) {
                $this->log("Empty webhook data");
                http_response_code(200);
                echo "OK";
                exit;
            }

            if (!$this->verifySignature($fortParams)) {
                $this->log("Invalid signature");
                http_response_code(200);
                echo "OK";
                exit;
            }

            $this->processPayment($fortParams);
        } catch (Exception $e) {
            $this->log("HANDLE ERROR: " . $e->getMessage());
        } catch (Error $e) {
            $this->log("HANDLE FATAL: " . $e->getMessage());
        }

        http_response_code(200);
        echo "OK";
    }

    private function verifySignature($params)
    {
        $responseSignature = $params['signature'] ?? '';
        unset($params['signature']);

        $excludeParams = ['r', 'url', 'integration_type'];
        foreach ($excludeParams as $param) {
            unset($params[$param]);
        }

        ksort($params);
        $shaString = '';
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null) continue;
            $shaString .= "$k=$v";
        }
        $shaString = $this->SHAResponsePhrase . $shaString . $this->SHAResponsePhrase;
        $calculatedSignature = hash('sha256', $shaString);

        $this->log("Expected Sig: $calculatedSignature | Received Sig: $responseSignature");

        return $responseSignature === $calculatedSignature;
    }

    private function processPayment($fortParams)
    {
        $merchantReference = $fortParams['merchant_reference'];
        $status = $fortParams['status'];
        $isSuccess = ($status == 14);

        $this->log("Processing: $merchantReference | Status: $status");

        $order = $this->projectModel->getSingle('*', ['order_identifier' => $merchantReference], 'orders');

        if (!$order) {
            $this->log("Order not found: $merchantReference");
            return;
        }

        if ($order->webhook_processed) {
            $this->log("Order already processed by webhook: $merchantReference");
            return;
        }

        $meta = json_encode($fortParams);
        $newStatus = $isSuccess ? 1 : 0;

        $data = [
            'meta' => $meta,
            'hash' => $order->hash,
            'status' => $newStatus,
        ];

        $this->projectModel->updateOrderMeta($data);
        $this->projectModel->updateDonationStatus($order->order_id, $newStatus);
        $this->updateBadalOrderStatus($order->order_id, $newStatus);

        $this->markWebhookProcessed($order->order_id);

        if ($isSuccess && !$order->notified) {
            $this->sendNotifications($order);
        }

        $this->log("Order processed: $merchantReference | Success: " . ($isSuccess ? 'YES' : 'NO'));
    }
    private function updateBadalOrderStatus($orderId, $status)
    {
        try {
            $result = $this->projectModel->updateBadalOrderByOrderId($orderId, $status);
            if ($result) {
                $this->log("Updated badalorder status to $status for order_id: $orderId");
            } else {
                $this->log("No badalorder found or update failed for order_id: $orderId");
            }
        } catch (Exception $e) {
            $this->log("Error updating badalorder: " . $e->getMessage());
        }
    }

    private function markWebhookProcessed($orderId)
    {
        try {
            $result = $this->projectModel->markWebhookProcessed($orderId);
            if ($result) {
                $this->log("Marked webhook_processed for order: $orderId");
            } else {
                $this->log("Failed to mark webhook_processed for order: $orderId");
            }
        } catch (Exception $e) {
            $this->log("Error marking webhook_processed: " . $e->getMessage());
        }
    }

    private function sendNotifications($order)
    {
        $donor = $this->projectModel->getSingle('*', ['donor_id' => $order->donor_id], 'donors');

        if (!$donor) {
            $this->log("Donor not found for order: {$order->order_id}");
            return;
        }

        $sendData = [
            'mailto' => $donor->email,
            'mobile' => $donor->mobile,
            'identifier' => $order->order_identifier,
            'order_id' => $order->order_id,
            'total' => $order->total,
            'project' => $order->projects,
            'donor' => $order->donor_name,
        ];

        $this->projectModel->notified($order->order_id);
        $this->messagingModel->sendConfirmation($sendData);
        $this->messagingModel->sendGiftCard($order);

        $this->log("Notifications sent for order: {$order->order_id}");
    }

    private function log($message)
    {
        $file = __DIR__ . '/webhook.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($file, "[$timestamp] $message\n", FILE_APPEND);
    }
}

$webhook = new PayfortWebhook();
$webhook->handle();
