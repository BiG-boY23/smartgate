<!-- Signatory Section -->
<footer id="print-signatories" class="print-only">
    <div class="signatory-row">
        <div class="signatory-block">
            <div class="signature-line">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</div>
            <div class="signatory-role">Prepared by: {{ ucfirst(Auth::user()->role) }}</div>
        </div>
        <div class="signatory-block">
            <div class="signature-line">&nbsp;</div>
            <div class="signatory-role">Approved by: Security Head</div>
        </div>
    </div>
</footer>

<style>
    #print-signatories { display: none; }
    
    @media print {
        #print-signatories { 
            display: block !important; 
            margin-top: 60px !important; 
            width: 100% !important; 
            page-break-inside: avoid !important;
        }
        .signatory-row { 
            display: flex !important; 
            justify-content: space-between !important; 
        }
        .signatory-block { 
            width: 280px !important; 
            text-align: center !important; 
            color: #000 !important;
        }
        .signature-line { 
            border-bottom: 1.5px solid #000 !important; 
            font-weight: bold !important; 
            padding-bottom: 5px !important; 
            margin-bottom: 5px !important; 
            font-size: 11pt !important;
        }
        .signatory-role { 
            font-size: 10pt !important; 
            text-transform: uppercase !important;
        }
    }
</style>
