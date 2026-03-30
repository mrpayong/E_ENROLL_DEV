<?php

// Simple test harness to exercise backend/fiscal_year.php from CLI or browser.
// Not for production use.

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'list';

include __DIR__ . '/fiscal_year.php';
