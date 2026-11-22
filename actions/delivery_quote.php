<?php
// The checkout map asks here for the distance and both delivery prices for a pin (JSON).
// The prices are shown to the customer; checkout_action.php works them out again before payment.
require_once __DIR__ . '/../core/core.php';
require_once __DIR__ . '/../controllers/DeliveryPricing.php';
require_once __DIR__ . '/../controllers/ProController.php';

if (!is_logged_in()) json_out(['success' => false, 'error' => 'Please log in again.'], 401);

$quote = (new DeliveryPricing())->quote($_GET['lat'] ?? '', $_GET['lng'] ?? '');
$quote = (new ProController())->applyToQuote($quote, (int) $_SESSION['customer_id']);   // Packe Pro: free standard delivery
json_out($quote, $quote['success'] ? 200 : 422);
