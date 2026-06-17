<div class="row row-cards">
    <?php foreach ($clients as $client): ?>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?= e($client['legal_name']) ?></h3>
                    <div class="card-actions">
                        <span class="badge bg-blue-lt status-badge"><?= e($client['status']) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">ABN</div>
                            <div class="datagrid-content"><?= e($client['abn']) ?></div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Status</div>
                            <div class="datagrid-content"><span class="badge bg-blue-lt status-badge"><?= e($client['status']) ?></span></div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Registered office</div>
                            <div class="datagrid-content"><?= nl2br(e($client['registered_office_address'])) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (!$clients): ?>
        <div class="col-12">
            <div class="empty">
                <div class="empty-icon"><i class="ti ti-building-off"></i></div>
                <p class="empty-title">No organisation profile found</p>
            </div>
        </div>
    <?php endif; ?>
</div>
