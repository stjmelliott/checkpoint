<?php
// /var/www/checkpoint/cron/expire_old_tokens.php

require_once __DIR__ . '/../config/bootstrap.php';

Logger::write('cron.log', 'INFO', 'Token expiry cron started');

try {
    $company_id = 1;

    // Step 1: Expire tokens older than 30 days
    $expireTokens = $pdo->prepare("
        UPDATE track_tokens 
        SET status = 'expired' 
        WHERE company_id = ? 
          AND status = 'active' 
          AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $expireTokens->execute([$company_id]);
    $tokensExpired = $expireTokens->rowCount();

    Logger::write('cron.log', 'INFO', 'Tokens expired', ['count' => $tokensExpired]);

    // Step 2: Cancel orphaned loads (loads with no active tokens)
    // Using correlated subquery for tenant safety
    $cancelLoads = $pdo->prepare("
        UPDATE track_load_snapshots s
        SET status = 'cancelled'
        WHERE s.company_id = ?
          AND s.status = 'active'
          AND NOT EXISTS (
              SELECT 1 FROM track_tokens t 
              WHERE t.load_number = s.load_number 
                AND t.company_id = s.company_id 
                AND t.status = 'active'
          )
    ");
    $cancelLoads->execute([$company_id]);
    $loadsCancelled = $cancelLoads->rowCount();

    Logger::write('cron.log', 'INFO', 'Orphaned loads cancelled', ['count' => $loadsCancelled]);

    Logger::write('cron.log', 'INFO', 'Token expiry cron complete', [
        'tokens_expired' => $tokensExpired,
        'loads_cancelled' => $loadsCancelled
    ]);

} catch (Exception $e) {
    Logger::write('cron.log', 'ERROR', 'Token expiry cron failed', ['error' => $e->getMessage()]);
}