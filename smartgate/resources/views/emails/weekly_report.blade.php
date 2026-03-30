<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Times New Roman', serif; line-height: 1.6; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { height: 60px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .footer { margin-top: 40px; font-size: 0.9rem; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h2>EASTERN VISAYAS STATE UNIVERSITY</h2>
        <p>Campus Safety & Site Management Office</p>
        <hr>
        <h3>WEEKLY VEHICLE TRAFFIC REPORT</h3>
        <p>For the week ending {{ date('F d, Y') }}</p>
    </div>

    <p>Dear Administrator,</p>
    <p>Please find the summary of the vehicle traffic logs for this week below. This report is automatically generated every Friday at 5:00 PM.</p>

    <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin: 1.5rem 0; border-left: 5px solid #741b1b;">
        <strong>Weekly Statistics Summary:</strong><br>
        • Total Entries: {{ $reportData['total_entries'] }}<br>
        • Total Exits: {{ $reportData['total_exits'] }}<br>
        • Peak Hour: {{ $reportData['peak_hour'] }}<br>
        • Most Frequent Tag: {{ $reportData['top_tag'] }}
    </div>

    <p>For the complete detailed logs, please log in to the <strong>SmartGate Administration Portal</strong> and navigate to the Reports section.</p>

    <div class="footer">
        &copy; 2026 EVSU SmartGate Automated Reporting System.
    </div>
</body>
</html>
