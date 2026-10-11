<?php
// api/create_payment_intent.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/vendor/autoload.php';

// Configuration Stripe (clé secrète)
\Stripe\Stripe::setApiKey('pk_test_51T0QWRKT7cI1xfeHqERivvZmXD9M1frVjobgRjLL83bPU4FG2CqjPQRbcbjgQgDESkG5CqKXR4mOnBz9lsvzJfxy00UhYQ8B6e'); // À CHANGER

$input = json_decode(file_get_contents('php://input'), true);
$paymentMethodId = $input['paymentMethodId'] ?? null;
$amount = $input['amount'] ?? null; // en centimes
$currency = $input['currency'] ?? 'eur';
$customerData = $input['customer'] ?? [];

if (!$paymentMethodId || !$amount) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'paymentMethodId and amount required']);
    exit;
}

try {
    // Créer un PaymentIntent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $amount,
        'currency' => $currency,
        'payment_method' => $paymentMethodId,
        'confirmation_method' => 'manual',
        'confirm' => true,
        'capture_method' => 'automatic', // ou 'manual' si vous voulez capturer plus tard
        'return_url' => 'https://alpha-med-care.com/checkout/success', // nécessaire pour certains modes
        'metadata' => [
            'email' => $customerData['email'] ?? '',
            'name' => $customerData['name'] ?? ''
        ]
    ]);

    echo json_encode([
        'success' => true,
        'paymentIntentId' => $paymentIntent->id,
        'clientSecret' => $paymentIntent->client_secret,
        'status' => $paymentIntent->status
    ]);
} catch (\Stripe\Exception\CardException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getError()->message]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}