<table width="100%" style="border-bottom:1.2mm solid #173f35;font-family:dejavusans;color:#173f35;">
    <tr>
        <td width="12%"><img src="var:foundation-logo" style="height:18mm;width:18mm;"></td>
        <td width="62%" style="padding-left:3mm;">
            <div style="font-size:15pt;font-weight:bold;">{{ config('organization.name') }}</div>
            <div lang="ur" dir="rtl" style="font-size:12pt;text-align:left;">{{ config('organization.name_urdu') }}</div>
        </td>
        <td width="26%" style="text-align:right;font-size:8pt;color:#586b62;">ORGANIZATION RECORDS<br>{{ $report['generated_at']->format('d M Y, h:i A') }} PKT</td>
    </tr>
</table>
