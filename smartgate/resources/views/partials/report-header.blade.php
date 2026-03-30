<!-- 1. Official University Letterhead (Header) -->
<header id="print-header" class="print-only">
    <div class="header-logo left">
        <img src="{{ asset('images/evsu-logo.png') }}" alt="Univ Logo">
    </div>
    <div class="header-text">
        <div class="gov-text">Republic of the Philippines</div>
        <div class="univ-name">EASTERN VISAYAS STATE UNIVERSITY - ORMOC CAMPUS</div>
        <div class="dept-name">{{ $deptName ?? 'Campus Safety & Site Management Office' }}</div>
        <div class="address-text">Ormoc City, Leyte, Philippines | Tel: (053) 832-2345</div>
    </div>
    <div class="header-logo right">
        <img src="{{ asset('images/bagong-pilipinas.jpg') }}" onerror="this.src='https://via.placeholder.com/200?text=Seal'" alt="Seal">
    </div>
</header>

<style>
    /* Print Header Styles */
    #print-header { 
        display: none; 
        justify-content: space-between; 
        align-items: center; 
        width: 100%; 
        border-bottom: 2px solid #000; 
        padding-bottom: 20px; 
        margin-bottom: 30px; 
    }

    @media print {
        #print-header { display: flex !important; }
        .header-logo { width: 100px !important; flex-shrink: 0 !important; }
        .header-logo img { height: 85px !important; width: auto !important; }
        .header-text { flex: 1 !important; text-align: center !important; }
        .gov-text { font-size: 11pt !important; text-transform: uppercase !important; color: #000; }
        .univ-name { font-size: 16pt !important; font-weight: bold !important; color: #000; }
        .dept-name { font-size: 12pt !important; font-weight: bold !important; color: #000; }
        .address-text { font-size: 9pt !important; font-style: italic !important; color: #000; }
    }
</style>
