@include('qard.partials.style')
<style>
.business-hero{display:flex;justify-content:space-between;gap:20px;align-items:flex-start}.business-steps{display:flex;flex-wrap:wrap;gap:8px;margin:20px 0}.business-step{padding:7px 11px;background:#edf3ef;border-radius:6px;font-size:13px}.business-step.current{background:#194c3d;color:white}.qard-page .bcheck{display:flex;gap:10px;align-items:flex-start;font-weight:400}.qard-page .bcheck input{width:auto;margin-top:5px}.business-budget{border:1px solid #dce6e1;padding:16px;border-radius:9px;margin:12px 0}.qard-page summary{cursor:pointer;font-weight:650;padding:10px 0}.qard-page .qtable td{min-width:100px}.qard-page input[type=checkbox]{width:auto}.business-events{max-height:440px;overflow:auto}.qard-page .qhint{font-size:13px;color:#63766e}.qard-page .status-strip{display:flex;flex-wrap:wrap;gap:9px}.business-metrics .qcard{margin:0}.business-metrics{margin:20px 0}@media(max-width:650px){.business-hero{display:block}.qard-page .qcard{padding:15px}.qard-page h1{font-size:24px}}@media print{.sidebar,.topbar,.business-no-print{display:none!important}.main,.main-content,.content{margin:0!important;padding:0!important}.qard-page .qcard{break-inside:avoid}}
</style>
<style>
@media print {
    @page { size: A4 landscape; margin: 12mm; }
    .main-area { margin-left: 0 !important; width: 100% !important; }
    .qard-page { max-width: none !important; }
    .qard-page .qtable { overflow: visible; break-inside: auto; }
    .qard-page table { width: 100%; table-layout: fixed; font-size: 10pt; }
    .qard-page .qtable td, .qard-page .qtable th { min-width: 0; overflow-wrap: anywhere; padding: 6px; }
    .qard-page tr { break-inside: avoid; }
    .qard-page thead { display: table-header-group; }
}
</style>
