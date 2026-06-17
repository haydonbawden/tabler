<?php
$value = (string) ($value ?? '');
$normalised = strtolower(str_replace(' ', '_', $value));
$badgeClass = $class ?? match ($normalised) {
    'active', 'current', 'issued', 'passed', 'paid', 'sent', 'acceptable' => 'bg-success-lt',
    'draft', 'submitted', 'in_review', 'awaiting_evidence', 'pending', 'queued' => 'bg-blue-lt',
    'expired', 'failed', 'cancelled', 'revoked', 'not_acceptable' => 'bg-danger-lt',
    'expiring_soon', 'changes_requested', 'needs_more_information' => 'bg-warning-lt',
    default => 'bg-secondary-lt',
};
unset($class);
?>
<span class="badge <?= e($badgeClass) ?> status-badge"><?= e(str_replace('_', ' ', $value)) ?></span>
