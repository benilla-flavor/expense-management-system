<?php
/**
 * Set a flash message to be displayed on the next page load.
 *
 * @param string $type The type of message (e.g., 'success', 'error').
 * @param string $message The message content.
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // e.g., 'success', 'error'
        'message' => $message
    ];
}

/**
 * Display the flash message if it exists and clear it afterward.
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        echo '<div class="notification ' . htmlspecialchars($flash['type']) . '">';
        echo htmlspecialchars($flash['message']);
        echo '</div>';
        unset($_SESSION['flash']); // Clear the message after displaying
    }
}

/**
 * Format an amount with the user's preferred currency.
 *
 * @param float $amount The amount to format.
 * @param string $currency The user's preferred currency.
 * @return string The formatted amount with currency symbol.
 */
function formatCurrency($amount, $currency) {
    switch ($currency) {
        case 'USD':
            return '$' . number_format($amount, 2); // US Dollar
        case 'PHP':
            return '₱' . number_format($amount, 2); // Philippine Peso
        case 'EUR':
            return '€' . number_format($amount, 2); // Euro
        case 'GBP':
            return '£' . number_format($amount, 2); // British Pound
        default:
            return $currency . ' ' . number_format($amount, 2); // Fallback format
    }
}
?>