<?php
$client = $client ?? null;
$contacts = $contacts ?? [];
$audits = $audits ?? [];
$certificates = $certificates ?? [];
$evidence = $evidence ?? [];
$payments = $payments ?? [];
$emailLogs = $emailLogs ?? [];
$activities = $activities ?? [];
$summary = $summary ?? [];

$formatDate = static function ($value): string {
    if (!$value) {
        return 'Not set';
    }
    $timestamp = strtotime((string) $value);
    return $timestamp ? date('d M Y', $timestamp) : (string) $value;
};

$formatMoney = static fn ($cents, $currency = 'AUD'): string => strtoupper((string) $currency) . ' ' . number_format(((int) $cents) / 100, 2);
$primaryContact = $contacts[0] ?? null;
$currentCertificates = array_values(array_filter($certificates, static fn (array $certificate): bool => ($certificate['computed_status'] ?? '') === 'current' && ($certificate['status'] ?? '') === 'issued'));
$atRiskCertificates = array_values(array_filter($certificates, static fn (array $certificate): bool => in_array($certificate['computed_status'] ?? '', ['expiring_soon', 'expired'], true)));
$latestAudit = $audits[0] ?? null;
$renewalRisk = ((int) ($summary['expired_certificates'] ?? 0) > 0 || (int) ($summary['expiring_certificates'] ?? 0) > 0) ? 'Attention required' : 'Low';
$renewalRiskClass = $renewalRisk === 'Low' ? 'bg-success-lt' : 'bg-warning-lt';
?>

<?php if (!$client): ?>
    <form class="card detail-shell" method="post" action="/admin/clients/new">
        <div class="card-header">
            <h3 class="card-title">Client details</h3>
        </div>
        <div class="card-body">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Client number</label><input class="form-control" name="client_number" value=""></div>
                <div class="col-md-4"><label class="form-label">ABN</label><input class="form-control" name="abn" value=""></div>
                <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option selected>active</option><option>inactive</option><option>archived</option></select></div>
                <div class="col-md-6"><label class="form-label">Legal name</label><input class="form-control" name="legal_name" value="" required></div>
                <div class="col-md-6"><label class="form-label">Trading name</label><input class="form-control" name="trading_name" value=""></div>
                <div class="col-md-6"><label class="form-label">Billing email</label><input class="form-control" type="email" name="billing_email" value=""></div>
                <div class="col-12"><label class="form-label">Registered office</label><textarea class="form-control" name="registered_office_address" rows="3"></textarea></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="4"></textarea></div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save client</button>
        </div>
    </form>
<?php else: ?>
    <div class="client-360 detail-shell">
        <div class="card client-360-header mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-lg">
                        <div class="d-flex align-items-center gap-3">
                            <span class="avatar bg-primary-lt text-primary"><?= e(strtoupper(substr((string) $client['legal_name'], 0, 1))) ?></span>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h2 class="h3 mb-0"><?= e($client['legal_name']) ?></h2>
                                    <?php $value = $client['status']; include app('root') . '/app/Views/components/status-badge.php'; ?>
                                </div>
                                <div class="text-secondary small">
                                    <?= e($client['trading_name'] ?: 'No trading name') ?> · Client <?= e($client['client_number'] ?: (string) $client['id']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-auto">
                        <div class="btn-list">
                            <a class="btn" href="/admin/audits/new"><i class="ti ti-clipboard-plus me-1"></i>New audit</a>
                            <a class="btn" href="/admin/contacts/new"><i class="ti ti-user-plus me-1"></i>New contact</a>
                            <button class="btn btn-primary" type="submit" form="client-overview-form"><i class="ti ti-device-floppy me-1"></i>Save</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border-top py-2">
                <div class="row g-0 record-highlights">
                    <div class="col-6 col-md-3 col-xl">
                        <div class="record-highlight">
                            <div class="text-secondary">ABN</div>
                            <div class="fw-medium"><?= e($client['abn'] ?: 'Not recorded') ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl">
                        <div class="record-highlight">
                            <div class="text-secondary">Primary contact</div>
                            <div class="fw-medium"><?= e($primaryContact['display_name'] ?? 'Not assigned') ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl">
                        <div class="record-highlight">
                            <div class="text-secondary">Active audits</div>
                            <div class="fw-medium"><?= e((string) ($summary['active_audits'] ?? 0)) ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl">
                        <div class="record-highlight">
                            <div class="text-secondary">Current certificates</div>
                            <div class="fw-medium"><?= e((string) ($summary['current_certificates'] ?? 0)) ?></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl">
                        <div class="record-highlight">
                            <div class="text-secondary">Renewal risk</div>
                            <div class="fw-medium"><span class="badge <?= e($renewalRiskClass) ?>"><?= e($renewalRisk) ?></span></div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-xl">
                        <div class="record-highlight">
                            <div class="text-secondary">Billing email</div>
                            <div class="fw-medium text-truncate"><?= e($client['billing_email'] ?: 'Not recorded') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                    <li class="nav-item"><a href="#client-overview" class="nav-link active" data-bs-toggle="tab">Overview</a></li>
                    <li class="nav-item"><a href="#client-contacts" class="nav-link" data-bs-toggle="tab">Contacts</a></li>
                    <li class="nav-item"><a href="#client-audits" class="nav-link" data-bs-toggle="tab">Audits</a></li>
                    <li class="nav-item"><a href="#client-certificates" class="nav-link" data-bs-toggle="tab">Certificates</a></li>
                    <li class="nav-item"><a href="#client-evidence" class="nav-link" data-bs-toggle="tab">Evidence</a></li>
                    <li class="nav-item"><a href="#client-payments" class="nav-link" data-bs-toggle="tab">Payments</a></li>
                    <li class="nav-item"><a href="#client-emails" class="nav-link" data-bs-toggle="tab">Emails</a></li>
                    <li class="nav-item"><a href="#client-activity" class="nav-link" data-bs-toggle="tab">Activity</a></li>
                    <li class="nav-item"><a href="#client-notes" class="nav-link" data-bs-toggle="tab">Notes</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active show" id="client-overview">
                        <div class="row row-cards">
                            <div class="col-xl-7">
                                <form id="client-overview-form" class="card" method="post" action="/admin/clients/<?= e($client['id']) ?>">
                                    <div class="card-header"><h3 class="card-title">Organisation details</h3></div>
                                    <div class="card-body">
                                        <?= csrf_field() ?>
                                        <div class="row g-3">
                                            <div class="col-md-4"><label class="form-label">Client number</label><input class="form-control" name="client_number" value="<?= e($client['client_number'] ?? '') ?>"></div>
                                            <div class="col-md-4"><label class="form-label">ABN</label><input class="form-control" name="abn" value="<?= e($client['abn'] ?? '') ?>"></div>
                                            <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option<?= selected($client['status'] ?? 'active', 'active') ?>>active</option><option<?= selected($client['status'] ?? '', 'inactive') ?>>inactive</option><option<?= selected($client['status'] ?? '', 'archived') ?>>archived</option></select></div>
                                            <div class="col-md-6"><label class="form-label">Legal name</label><input class="form-control" name="legal_name" value="<?= e($client['legal_name'] ?? '') ?>" required></div>
                                            <div class="col-md-6"><label class="form-label">Trading name</label><input class="form-control" name="trading_name" value="<?= e($client['trading_name'] ?? '') ?>"></div>
                                            <div class="col-md-6"><label class="form-label">Billing email</label><input class="form-control" type="email" name="billing_email" value="<?= e($client['billing_email'] ?? '') ?>"></div>
                                            <div class="col-12"><label class="form-label">Registered office</label><textarea class="form-control" name="registered_office_address" rows="3"><?= e($client['registered_office_address'] ?? '') ?></textarea></div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="col-xl-5">
                                <div class="row row-cards">
                                    <div class="col-md-6 col-xl-12">
                                        <div class="card">
                                            <div class="card-header"><h3 class="card-title">Renewal risk</h3></div>
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div>
                                                        <div class="text-secondary">Current risk</div>
                                                        <div class="h3 mb-0"><?= e($renewalRisk) ?></div>
                                                    </div>
                                                    <span class="badge <?= e($renewalRiskClass) ?>"><?= e((string) ($summary['expiring_certificates'] ?? 0)) ?> expiring</span>
                                                </div>
                                                <div class="datagrid client-risk-grid">
                                                    <div class="datagrid-item"><div class="datagrid-title">Expired certificates</div><div class="datagrid-content"><?= e((string) ($summary['expired_certificates'] ?? 0)) ?></div></div>
                                                    <div class="datagrid-item"><div class="datagrid-title">Outstanding evidence</div><div class="datagrid-content"><?= e((string) ($summary['outstanding_evidence'] ?? 0)) ?></div></div>
                                                    <div class="datagrid-item"><div class="datagrid-title">Latest audit</div><div class="datagrid-content"><?= e($latestAudit['audit_number'] ?? 'None') ?></div></div>
                                                    <div class="datagrid-item"><div class="datagrid-title">Due date</div><div class="datagrid-content"><?= e($formatDate($latestAudit['due_date'] ?? null)) ?></div></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-12">
                                        <div class="card">
                                            <div class="card-header"><h3 class="card-title">Current certifications</h3></div>
                                            <div class="list-group list-group-flush">
                                                <?php foreach (array_slice($currentCertificates, 0, 5) as $certificate): ?>
                                                    <a class="list-group-item list-group-item-action py-2" href="/admin/certificates/<?= e($certificate['id']) ?>">
                                                        <div class="d-flex align-items-center justify-content-between gap-3">
                                                            <div>
                                                                <div class="fw-medium"><?= e($certificate['certification']) ?></div>
                                                                <div class="text-secondary small"><?= e($certificate['certificate_number']) ?> · expires <?= e($formatDate($certificate['expiry_date'])) ?></div>
                                                            </div>
                                                            <?php $value = $certificate['status']; include app('root') . '/app/Views/components/status-badge.php'; ?>
                                                        </div>
                                                    </a>
                                                <?php endforeach; ?>
                                                <?php if (!$currentCertificates): ?><div class="list-group-item text-secondary">No current certificates.</div><?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header"><h3 class="card-title">Recent activity</h3></div>
                                            <div class="list-group list-group-flush">
                                                <?php foreach (array_slice($activities, 0, 4) as $activity): ?>
                                                    <div class="list-group-item py-2">
                                                        <div class="small text-secondary"><?= e($formatDate($activity['created_at'] ?? null)) ?></div>
                                                        <div class="fw-medium"><?= e($activity['actor'] ?? 'System') ?> <?= e(str_replace('_', ' ', $activity['action'] ?? 'updated')) ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (!$activities): ?><div class="list-group-item text-secondary">No recent activity.</div><?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="client-contacts">
                        <div class="related-list-header">
                            <div><h3 class="card-title mb-0">Contacts</h3><div class="text-secondary"><?= e((string) count($contacts)) ?> records</div></div>
                            <a class="btn btn-primary" href="/admin/contacts/new"><i class="ti ti-user-plus me-1"></i>New contact</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Position</th><th>Status</th><th>Notifications</th></tr></thead>
                                <tbody>
                                <?php foreach ($contacts as $contact): ?>
                                    <tr class="table-row-link" data-href="/admin/contacts/<?= e($contact['id']) ?>">
                                        <td class="fw-medium"><?= e($contact['display_name']) ?></td>
                                        <td><?= e($contact['email']) ?></td>
                                        <td><?= e($contact['phone'] ?: '') ?></td>
                                        <td><?= e($contact['position_title'] ?: '') ?></td>
                                        <td><?php $value = ((int) $contact['is_active'] === 1 ? 'active' : 'inactive'); include app('root') . '/app/Views/components/status-badge.php'; ?></td>
                                        <td class="text-secondary small"><?= ((int) $contact['receives_reminders'] === 1 ? 'Reminders ' : '') ?><?= ((int) $contact['receives_certificates'] === 1 ? 'Certificates ' : '') ?><?= ((int) $contact['receives_audit_notifications'] === 1 ? 'Audits' : '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$contacts): ?><tr><td colspan="6" class="text-secondary">No contacts recorded for this client.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane" id="client-audits">
                        <div class="related-list-header">
                            <div><h3 class="card-title mb-0">Audits</h3><div class="text-secondary"><?= e((string) count($audits)) ?> records</div></div>
                            <a class="btn btn-primary" href="/admin/audits/new"><i class="ti ti-clipboard-plus me-1"></i>New audit</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>Audit</th><th>Certification</th><th>Status</th><th>Auditor</th><th>Due</th><th>Result</th></tr></thead>
                                <tbody>
                                <?php foreach ($audits as $audit): ?>
                                    <tr class="table-row-link" data-href="/admin/audits/<?= e($audit['id']) ?>">
                                        <td class="fw-medium"><?= e($audit['audit_number']) ?></td>
                                        <td><?= e($audit['certification']) ?></td>
                                        <td><?php $value = $audit['status']; include app('root') . '/app/Views/components/status-badge.php'; ?></td>
                                        <td><?= e($audit['auditor']) ?></td>
                                        <td><?= e($formatDate($audit['due_date'] ?? null)) ?></td>
                                        <td><?= e($audit['result'] ?: 'Pending') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$audits): ?><tr><td colspan="6" class="text-secondary">No audits recorded for this client.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane" id="client-certificates">
                        <div class="related-list-header">
                            <div><h3 class="card-title mb-0">Certificates</h3><div class="text-secondary"><?= e((string) count($certificates)) ?> records</div></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>Certificate</th><th>Certification</th><th>Status</th><th>Issue</th><th>Expiry</th><th>Renew by</th><th>PDF</th></tr></thead>
                                <tbody>
                                <?php foreach ($certificates as $certificate): ?>
                                    <tr class="table-row-link" data-href="/admin/certificates/<?= e($certificate['id']) ?>">
                                        <td class="fw-medium"><?= e($certificate['certificate_number']) ?></td>
                                        <td><?= e($certificate['certification']) ?></td>
                                        <td><?php $value = $certificate['computed_status']; include app('root') . '/app/Views/components/status-badge.php'; ?></td>
                                        <td><?= e($formatDate($certificate['issue_date'] ?? null)) ?></td>
                                        <td><?= e($formatDate($certificate['expiry_date'] ?? null)) ?></td>
                                        <td><?= e($formatDate($certificate['last_day_to_renew'] ?? null)) ?></td>
                                        <td><?= $certificate['pdf_path'] ? '<a href="/admin/certificates/' . e($certificate['id']) . '/download">Download</a>' : '<span class="text-secondary">Not generated</span>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$certificates): ?><tr><td colspan="7" class="text-secondary">No certificates recorded for this client.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane" id="client-evidence">
                        <div class="related-list-header">
                            <div><h3 class="card-title mb-0">Evidence</h3><div class="text-secondary"><?= e((string) count($evidence)) ?> files</div></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>File</th><th>Audit</th><th>Criterion</th><th>Type</th><th>Size</th><th>Uploaded</th></tr></thead>
                                <tbody>
                                <?php foreach ($evidence as $file): ?>
                                    <tr class="table-row-link" data-href="/admin/audits/<?= e($file['audit_id']) ?>">
                                        <td class="fw-medium"><?= e($file['original_filename']) ?></td>
                                        <td><?= e($file['audit_number']) ?></td>
                                        <td><?= e(trim((string) ($file['section'] ?? '') . ' ' . (string) ($file['criterion'] ?? ''))) ?></td>
                                        <td><?= e($file['mime_type']) ?></td>
                                        <td><?= e(number_format(((int) $file['size_bytes']) / 1024, 1)) ?> KB</td>
                                        <td><?= e($formatDate($file['uploaded_at'] ?? null)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$evidence): ?><tr><td colspan="6" class="text-secondary">No evidence files have been submitted for this client.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane" id="client-payments">
                        <div class="related-list-header">
                            <div><h3 class="card-title mb-0">Payments</h3><div class="text-secondary"><?= e((string) count($payments)) ?> records</div></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>ID</th><th>Amount</th><th>Status</th><th>Audit</th><th>Certificate</th><th>Paid</th><th>Created</th></tr></thead>
                                <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td class="fw-medium">#<?= e($payment['id']) ?></td>
                                        <td><?= e($formatMoney($payment['amount_cents'], $payment['currency'])) ?></td>
                                        <td><?php $value = $payment['status']; include app('root') . '/app/Views/components/status-badge.php'; ?></td>
                                        <td><?= e($payment['audit_id'] ?: '') ?></td>
                                        <td><?= e($payment['certificate_id'] ?: '') ?></td>
                                        <td><?= e($formatDate($payment['paid_at'] ?? null)) ?></td>
                                        <td><?= e($formatDate($payment['created_at'] ?? null)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$payments): ?><tr><td colspan="7" class="text-secondary">No payments recorded for this client.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane" id="client-emails">
                        <div class="related-list-header">
                            <div><h3 class="card-title mb-0">Emails</h3><div class="text-secondary"><?= e((string) count($emailLogs)) ?> records</div></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead><tr><th>Subject</th><th>To</th><th>Status</th><th>Related</th><th>Sent</th><th>Created</th></tr></thead>
                                <tbody>
                                <?php foreach ($emailLogs as $email): ?>
                                    <tr>
                                        <td class="fw-medium"><?= e($email['subject']) ?></td>
                                        <td><?= e($email['to_email']) ?></td>
                                        <td><?php $value = $email['status']; include app('root') . '/app/Views/components/status-badge.php'; ?></td>
                                        <td><?= e(trim((string) ($email['related_type'] ?? '') . ' #' . (string) ($email['related_id'] ?? ''), ' #')) ?></td>
                                        <td><?= e($formatDate($email['sent_at'] ?? null)) ?></td>
                                        <td><?= e($formatDate($email['created_at'] ?? null)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$emailLogs): ?><tr><td colspan="6" class="text-secondary">No emails are linked to this client.</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane" id="client-activity">
                        <div class="row">
                            <div class="col-xl-8">
                                <?php include app('root') . '/app/Views/components/activity-timeline.php'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="client-notes">
                        <form class="card" method="post" action="/admin/clients/<?= e($client['id']) ?>">
                            <div class="card-header"><h3 class="card-title">Internal notes</h3></div>
                            <div class="card-body">
                                <?= csrf_field() ?>
                                <input type="hidden" name="client_number" value="<?= e($client['client_number'] ?? '') ?>">
                                <input type="hidden" name="abn" value="<?= e($client['abn'] ?? '') ?>">
                                <input type="hidden" name="legal_name" value="<?= e($client['legal_name'] ?? '') ?>">
                                <input type="hidden" name="trading_name" value="<?= e($client['trading_name'] ?? '') ?>">
                                <input type="hidden" name="billing_email" value="<?= e($client['billing_email'] ?? '') ?>">
                                <input type="hidden" name="registered_office_address" value="<?= e($client['registered_office_address'] ?? '') ?>">
                                <input type="hidden" name="status" value="<?= e($client['status'] ?? 'active') ?>">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes" rows="8"><?= e($client['notes'] ?? '') ?></textarea>
                            </div>
                            <div class="card-footer text-end">
                                <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save notes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
