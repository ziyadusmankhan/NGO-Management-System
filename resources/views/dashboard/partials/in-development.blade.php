<div class="dashboard-card">
    <div class="card-header-custom">
        <div class="card-header-title">{{ $title }}</div>
        <span style="padding:4px 10px;border-radius:20px;background:var(--legacy-surface-fff4df, #fff4df);color:var(--legacy-text-805b18, #805b18);font-size:13px;white-space:nowrap;">In development</span>
    </div>
    <div class="card-body-custom" style="min-height:160px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:12px;color:var(--legacy-text-6b7670, #6b7670);">
        <i class="bi bi-tools" aria-hidden="true" style="font-size:26px;"></i>
        <p style="margin:0;max-width:360px;">{{ $message }}</p>
    </div>
</div>
