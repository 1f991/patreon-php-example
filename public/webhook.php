<?php require_once __DIR__ . '/../bootstrap.php';

use Squid\Patreon\Patreon;

if (! file_exists(DATABASE)) {
    throw new Exception('Database does not exist.');
}

$patreon = new Patreon(getenv('PATREON_ACCESS_TOKEN'));

// Accept the request body and populate `$pledges` with a Collection of the
// Pledges sent by Patreon. The `accept` method verifies that the signature sent
// by Patreon matches the signature provided which we have defined as
// PATREON_WEBHOOK_SECRET in .env. If the signature does not match then a
// SignatureVerificationFailed Exception will be thrown which will stop the
// processing. This prevents any misuse from people who might discover the
// address of your Webhook URL.
try {
    $pledges = $patreon->webhook()->accept(
        file_get_contents('php://input'),
        getenv('PATREON_WEBHOOK_SECRET'),
        $_SERVER['HTTP_X_PATREON_SIGNATURE'] ?? ''
    );
} catch (\Squid\Patreon\Exceptions\SignatureVerificationFailed $e) {
    error_log($e->getMessage() . "\n", 3, LOG);
    die('Error: ' . $e->getMessage());
}

// A webhook can include many pledges at once but we're just interested in one
// so we populate $pledge with the first pledge from $pledges.
$pledge = $pledges->first();

// A webhook request from Patreon has an `HTTP_X_PATREON_EVENT` header which
// can contain `pledges:create`, `pledges:update` or `pledges:delete`. We want
// to update our database with the Pledge's properties when it's either create
// or update
if (in_array($_SERVER['HTTP_X_PATREON_EVENT'], ['pledges:create', 'pledges:update'])) {
    $data = json_decode(file_get_contents(DATABASE), true);

    $data['patrons'][$pledge->patron->id] = [
        'name' => $pledge->patron->full_name,
        'picture' => $pledge->patron->image_url,
        'per_payment' => number_format($pledge->amount_cents / 100, 2),
        'total_amount' => number_format($pledge->total_historical_amount_cents / 100, 2),
        'is_active' => $pledge->isActive(),
        'reward' => $pledge->hasReward() ? $pledge->reward->title : null,
    ];

    file_put_contents(DATABASE, json_encode($data, JSON_PRETTY_PRINT));
}

// If the Webhook request is for a `pledges:delete` event then we want to delete
// that Pledge from our database.
if ($_SERVER['HTTP_X_PATREON_EVENT'] === 'pledges:delete') {
    $data = json_decode(file_get_contents(DATABASE), true);

    // Loop through each of the Patrons in our database. When we find the Patron
    // with the same ID sent to us by Patreon then we delete that Patron from
    // the data.
    foreach ($data['patrons'] as $id => $patron) {
        if ($id === $pledge->patron->id) {
            unset($data['patrons'][$id]);
        }
    }

    file_put_contents(DATABASE, json_encode($data, JSON_PRETTY_PRINT));
}

error_log("A {$_SERVER['HTTP_X_PATREON_EVENT']} event has been processed\n", 3, LOG);

echo 'Webhook received and processed.';
