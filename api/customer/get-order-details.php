<?php

// When returning order details, force payment_method to 'cod' and payment_status to only be 'pending', 'partial', or 'paid'.
if (isset($order['payment_method'])) $order['payment_method'] = 'cod';
if (!in_array($order['payment_status'], ['pending','partial','paid'])) $order['payment_status'] = 'pending'; 