@extends('layouts.app')

@section('title', 'Issuance & Status')
@section('subtitle', 'Directory of all registered accounts and their tag issuance status.')

@section('content')
<div class="dashboard-grid" style="margin-bottom:1rem;">
    <div class="stat-card">
        <div class="stat-label">Total Records (Filtered)</div>
        <div class="stat-value" id="total-count">{{ $registrations->count() }}</div>
        <i class="ph ph-users-three stat-icon" style="color: #741b1b;"></i>
    </div>
    <div class="stat-card">
        <div class="stat-label">Unpaid (Waiting)</div>
        <div class="stat-value" id="unpaid-count">{{ $unpaidWaiting }}</div>
        <i class="ph-bold ph-warning-circle stat-icon" style="color: #ef4444;"></i>
    </div>
</div>

<div class="filters-container shadow-sm" style="background: white; padding: 1.25rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
    <div style="flex: 1; min-width: 250px;">
        <label style="display:block; font-size:0.7rem; font-weight:800; color:#741b1b; text-transform:uppercase; margin-bottom:5px;">Search Owner</label>
        <div style="position:relative;">
            <i class="ph ph-magnifying-glass" style="position:absolute; left:12px; top:50%; translate: 0 -50%; color:#94a3b8;"></i>
            <input type="text" id="search-input" placeholder="Search name or plate..." style="width:100%; padding: 10px 10px 10px 40px; border-radius:8px; border:1px solid #e2e8f0; font-size:0.9rem;">
        </div>
    </div>

    <div style="width: 200px;">
        <label style="display:block; font-size:0.7rem; font-weight:800; color:#741b1b; text-transform:uppercase; margin-bottom:5px;">Filter by Program</label>
        <select id="filter-program" style="width:100%; padding:10px; border-radius:8px; border:1px solid #e2e8f0; background:white; font-size:0.9rem;">
            <option value="">All Programs</option>
            @foreach($courses as $course)
                <option value="{{ $course->name }}">{{ $course->code ?? $course->name }}</option>
            @endforeach
        </select>
    </div>

    <div style="width: 150px;">
        <label style="display:block; font-size:0.7rem; font-weight:800; color:#741b1b; text-transform:uppercase; margin-bottom:5px;">Filter by Status</label>
        <select id="filter-status" style="width:100%; padding:10px; border-radius:8px; border:1px solid #e2e8f0; background:white; font-size:0.9rem;">
            <option value="">All Status</option>
            <option value="PAID">PAID</option>
            <option value="UNPAID">UNPAID</option>
        </select>
    </div>

    <div style="width: 200px;">
        <label style="display:block; font-size:0.7rem; font-weight:800; color:#741b1b; text-transform:uppercase; margin-bottom:5px;">Filter by Role</label>
        <select id="filter-role" style="width:100%; padding:10px; border-radius:8px; border:1px solid #e2e8f0; background:white; font-size:0.9rem;">
            <option value="">All Roles</option>
            <option value="student">Student Enrollee</option>
            <option value="faculty">Academic Personnel (Teaching)</option>
            <option value="staff">Non-Teaching Staff / Vendor</option>
        </select>
    </div>

    <div style="width: 180px;">
        <label style="display:block; font-size:0.7rem; font-weight:800; color:#741b1b; text-transform:uppercase; margin-bottom:5px;">Sort by</label>
        <select id="sort-by" style="width:100%; padding:10px; border-radius:8px; border:1px solid #e2e8f0; background:white; font-size:0.9rem;">
            <option value="date_desc">Newest First</option>
            <option value="date_asc">Oldest First</option>
            <option value="alphabetical">Alphabetical Name</option>
        </select>
    </div>
</div>

<div class="table-container shadow-sm">
    <table class="table">
        <thead>
            <tr>
                <th>Owner Name</th>
                <th>Role</th>
                <th>Vehicle / Plate</th>
                <th style="text-align: right;">Registration & Issuance Status</th>
            </tr>
        </thead>
        <tbody id="payment-table-body">
            @include('admin.manage.payments.partials._pending_table_rows')
        </tbody>
    </table>
</div>

@endsection

@section('scripts')
<script>
    // System-wide RFID bridge monitoring
    let ws;
    const connectBridge = () => {
        ws = new WebSocket('ws://localhost:8080');
        ws.onopen = () => console.log('Bridge Connected');
        ws.onmessage = (e) => {
            try {
                const data = JSON.parse(e.data);
                if (data.tagId) {
                    Swal.fire({ 
                        toast: true, 
                        position: 'top-end', 
                        icon: 'info', 
                        title: `Tag Detected: ${data.tagId}`, 
                        showConfirmButton: false, 
                        timer: 2000 
                    });
                }
            } catch (err) { console.error('WS Error:', err); }
        };
        ws.onclose = () => {
            console.log('Bridge Disconnected. Retrying...');
            setTimeout(connectBridge, 3000);
        };
    };
    connectBridge();

    // AJAX Filtering Logic
    const tableBody = document.getElementById('payment-table-body');
    const totalCountEl = document.getElementById('total-count');
    const unpaidCountEl = document.getElementById('unpaid-count');
    
    const filterInputs = ['filter-program', 'filter-status', 'filter-role', 'sort-by'];
    const searchInput = document.getElementById('search-input');

    const refreshTable = async () => {
        const program = document.getElementById('filter-program').value;
        const status = document.getElementById('filter-status').value;
        const role = document.getElementById('filter-role').value;
        const sort = document.getElementById('sort-by').value;
        const search = searchInput.value;

        // Visual feedback
        tableBody.style.opacity = '0.5';

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('program', program);
            url.searchParams.set('status', status);
            url.searchParams.set('role', role);
            url.searchParams.set('sort', sort);
            if(search) url.searchParams.set('search', search);
            
            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            tableBody.innerHTML = data.html;
            totalCountEl.innerText = data.total_count;
            unpaidCountEl.innerText = data.unpaid_count;
            
            // Re-bind click events for new buttons
            bindPaymentButtons();
        } catch (err) {
            console.error('Filter error:', err);
        } finally {
            tableBody.style.opacity = '1';
        }
    };

    filterInputs.forEach(id => {
        document.getElementById(id).addEventListener('change', refreshTable);
    });

    let searchTimer;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(refreshTable, 500);
    });

    // Wrap payment button logic in a function for re-binding
    const bindPaymentButtons = () => {
        document.querySelectorAll('.btn-process-payment').forEach(btn => {
            btn.onclick = async () => {
                const ownerId = btn.dataset.id;
                const ownerName = btn.dataset.name;
                const fee = btn.dataset.fee;
            const { value: formValues } = await Swal.fire({
                title: 'RFID Issuance & Payment',
                html: `
                    <div style="background: #f8fafc; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; text-align: left; border: 1px solid #e2e8f0;">
                        <div style="font-size: 0.75rem; color: #64748b; font-weight: 800; text-transform: uppercase;">Issuing to:</div>
                        <div style="font-size: 1.1rem; color: #1e293b; font-weight: 800;">${ownerName}</div>
                    </div>
                    
                    <div style="margin-bottom: 1rem; text-align: left;">
                        <label style="display: block; font-size: 0.7rem; font-weight: 800; color: #741b1b; text-transform: uppercase; margin-bottom: 5px;">Official Receipt (OR) Number</label>
                        <input id="swal-or" class="swal2-input" placeholder="Enter OR Number" style="margin: 0; width: 100%; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 1.5rem; text-align: left;">
                        <label style="display: block; font-size: 0.7rem; font-weight: 800; color: #741b1b; text-transform: uppercase; margin-bottom: 5px;">RFID Tag ID</label>
                        <div style="display: flex; gap: 10px;">
                            <input id="swal-tag" class="swal2-input" placeholder="Scan Tag..." style="margin: 0; width: 100%; box-sizing: border-box;">
                        </div>
                        <small style="color: #64748b;">Tag will auto-fill if bridge is connected and tag is scanned.</small>
                    </div>

                    <div style="background: #f0fdf4; border: 1px dashed #16a34a; border-radius: 12px; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-weight: 800; color: #166534;">TOTAL ISSUANCE FEE</div>
                        <div style="font-size: 1.5rem; font-weight: 900; color: #14532d;">₱${parseFloat(fee).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                        <input type="hidden" id="swal-amount" value="${fee}">
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Confirm & Activate',
                confirmButtonColor: '#16a34a',
                didOpen: () => {
                    // Update the global WS logic if necessary, or just rely on the existing one if it can update the swal-tag input
                    // Actually let's bridge it here
                    const tagInput = document.getElementById('swal-tag');
                    const orInput = document.getElementById('swal-or');
                    
                    // We can reuse the existing 'ws' if it's in scope, but we need to update this specific input
                    const originalOnMessage = ws.onmessage;
                    ws.onmessage = (e) => {
                        try {
                            const data = JSON.parse(e.data);
                            if (data.tagId) {
                                tagInput.value = data.tagId;
                                Swal.showValidationMessage(''); // clear errors
                            }
                        } catch (err) {}
                    }
                    
                    // Restore original when closed
                    Swal.getCancelButton().addEventListener('click', () => { ws.onmessage = originalOnMessage; });
                },
                preConfirm: () => {
                    const or = document.getElementById('swal-or').value;
                    const tag = document.getElementById('swal-tag').value;
                    const amount = document.getElementById('swal-amount').value;

                    if (!or) { Swal.showValidationMessage('Official Receipt (OR) is required.'); return false; }
                    if (!tag) { Swal.showValidationMessage('RFID Tag ID is required.'); return false; }
                    
                    return { or_number: or, rfid_tag: tag, amount: amount };
                }
            });

            if (formValues) {
                Swal.fire({
                    title: 'Finalizing Issuance...',
                    text: 'Recording payment and activating RFID license.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    const response = await fetch("{{ route('payments.process') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            vehicle_registration_id: ownerId,
                            or_number: formValues.or_number,
                            amount: formValues.amount,
                            rfid_tag: formValues.rfid_tag
                        })
                    });
                    
                    const result = await response.json().catch(() => ({})); 

                    if (response.ok) {
                        await Swal.fire('Success!', 'RFID Tag issued and activated successfully.', 'success');
                        location.reload();
                    } else {
                        Swal.fire('Error', result.message || 'Payment processing failed. Check OR uniqueness.', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Sever communication failure.', 'error');
                }
            }
            };
        });
    };

    // Initial bind
    bindPaymentButtons();
</script>
@endsection
