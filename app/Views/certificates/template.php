<style>
    .cert-page { font-family: Georgia, "Times New Roman", serif; color: #162033; line-height: 1.5; }
    .cert-title { text-align: center; font-size: 30px; margin: 24px 0; }
    .cert-subtitle { text-align: center; font-size: 18px; margin-bottom: 32px; }
    .cert-box { border: 2px solid #162033; padding: 28px; margin: 18px 0; }
    .cert-row { margin: 10px 0; }
    .cert-label { font-weight: bold; display: inline-block; min-width: 170px; }
    .cert-footer { font-size: 10px; margin-top: 38px; border-top: 1px solid #999; padding-top: 12px; }
</style>
<div class="cert-page">
    <div style="text-align:center">
        <div style="font-size:22px;font-weight:bold">Castor Audit & Advisory</div>
        <div>A division of Castor Management Australia Pty Ltd</div>
    </div>
    <h1 class="cert-title">Certificate of Certification</h1>
    <div class="cert-subtitle">This certificate confirms that the organisation named below has met the stated certification requirements.</div>
    <div class="cert-box">
        <div class="cert-row"><span class="cert-label">Certificate number:</span> <?= e($certificate['certificate_number'] ?? '') ?></div>
        <div class="cert-row"><span class="cert-label">Client name:</span> <?= e($certificate['legal_name'] ?? '') ?></div>
        <div class="cert-row"><span class="cert-label">Client ABN:</span> <?= e($certificate['abn'] ?? '') ?></div>
        <div class="cert-row"><span class="cert-label">Registered office:</span> <?= e($certificate['registered_office_address'] ?? '') ?></div>
        <div class="cert-row"><span class="cert-label">Certification:</span> <?= e($certificate['certification'] ?? '') ?></div>
        <div class="cert-row"><span class="cert-label">Audit scope:</span> <?= e($certificate['audit_scope'] ?? '') ?></div>
        <div class="cert-row"><span class="cert-label">Issue date:</span> <?= e($certificate['issue_date'] ?? '') ?></div>
        <div class="cert-row"><span class="cert-label">Expiry date:</span> <?= e($certificate['expiry_date'] ?? '') ?></div>
        <div class="cert-row"><span class="cert-label">Last day to renew:</span> <?= e($certificate['last_day_to_renew'] ?? '') ?></div>
        <div class="cert-row"><span class="cert-label">Issue number:</span> <?= e($certificate['issue_number'] ?? '') ?></div>
    </div>
    <div style="margin-top:42px">
        <strong><?= e($castor['signatory_name'] ?? 'Haydon Bawden') ?></strong><br>
        <?= e($castor['signatory_title'] ?? 'Lead Auditor') ?><br>
        <?= e($castor['entity'] ?? 'Castor Audit & Advisory') ?><br>
        <?= e($castor['division'] ?? '') ?><br>
        ABN <?= e($castor['abn'] ?? '') ?><br>
        <?= e($castor['phone'] ?? '') ?> · <?= e($castor['email'] ?? '') ?> · <?= e($castor['website'] ?? '') ?>
    </div>
    <div class="cert-footer">
        This certificate has been electronically issued. Validation may be requested by contacting Castor Audit & Advisory and quoting the certificate number.
        This certificate is issued subject to relevant terms and conditions. Unauthorised alteration, forgery or falsification of this certificate is prohibited.
    </div>
</div>
