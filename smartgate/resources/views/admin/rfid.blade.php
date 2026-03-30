@extends('layouts.app')

@section('title', 'RFID Master Management')
@section('subtitle', 'Oversee all registered vehicle tags, monitor status, and manage security access.')

@section('content')

<!-- RFID Status Summary -->
<div class="dashboard-grid mb-8">
    <div class="stat-card">
        <div class="stat-label">Total Registered Tags</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
        <i class="ph ph-identification-card stat-icon"></i>
    </div>
    
    <div class="stat-card" style="border-left: 4px solid #10b981;">
        <div class="stat-label">Active / Authorized</div>
        <div class="stat-value text-success">{{ $stats['active'] }}</div>
        <i class="ph ph-check-circle stat-icon" style="color: #10b981; opacity: 0.2;"></i>
    </div>
    
    <div class="stat-card" style="border-left: 4px solid #ef4444;">
        <div class="stat-label">Blacklisted / Blocked</div>
        <div class="stat-value text-danger">{{ $stats['blacklisted'] }}</div>
        <i class="ph ph-prohibit stat-icon" style="color: #ef4444; opacity: 0.2;"></i>
    </div>
</div>

<div class="table-container">
    <div class="section-header" style="flex-direction: column; align-items: stretch; gap: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3>Master Tag Directory</h3>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-primary" onclick="showRegistrationModal()"><i class="ph ph-plus"></i> Register New Tag</button>
                <button class="btn btn-outline" onclick="window.location.reload()"><i class="ph ph-arrows-clockwise"></i> Refresh</button>
                <a href="{{ route('admin.reports') }}" class="btn btn-outline"><i class="ph ph-file-text"></i> View Audit Logs</a>
            </div>
        </div>

        <!-- Search & Filter Bar -->
        <form action="{{ route('admin.rfid') }}" method="GET" style="display: flex; gap: 15px; background: #f8fafc; padding: 1rem; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="flex-grow: 1; position: relative;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Owner, Plate, or RFID Tag ID..." 
                    style="width: 100%; padding: 0.7rem 1rem 0.7rem 2.8rem; border: 1px solid #e2e8f0; border-radius: 8px; outline: none;">
            </div>
            <select name="status" style="padding: 0.7rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; background: white; outline: none;">
                <option value="all">All Status</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Active</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Blacklisted</option>
            </select>
            <button type="submit" class="btn btn-primary" style="padding: 0 1.5rem;">Apply Filter</button>
            @if(request()->has('search') || (request()->has('status') && request('status') != 'all'))
                <a href="{{ route('admin.rfid') }}" class="btn btn-outline" style="padding: 0.7rem 1rem; color: #ef4444; border-color: #fee2e2;">Clear</a>
            @endif
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Tag ID</th>
                    <th>Owner / Vehicle</th>
                    <th>Plate Number</th>
                    <th>Registered By</th>
                    <th>Status</th>
                    <th style="text-align: right;">Security Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registrations as $reg)
                <tr id="row-{{ $reg->id }}">
                    <td>
                        <div style="font-family: monospace; font-weight: 600; color: #1e293b; background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px; display: inline-block;">
                            {{ $reg->rfid_tag_id }}
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #1e293b;">{{ $reg->full_name }}</div>
                        <div style="font-size: 0.75rem; color: #64748b;">
                            <span style="font-weight: 700; text-transform: uppercase;">{{ $reg->vehicle_type }}</span> • 
                            {{ $reg->make_brand . ' ' . ($reg->model_name ?? '') }}
                        </div>
                    </td>
                    <td><span class="badge-plate">{{ $reg->plate_number }}</span></td>
                    <td>
                        <div style="font-size: 0.85rem; color: #475569;">{{ $reg->officeUser->name ?? 'System' }}</div>
                        <div style="font-size: 0.7rem; color: #94a3b8;">{{ $reg->created_at->format('M d, Y') }}</div>
                    </td>
                    <td id="status-cell-{{ $reg->id }}">
                        @if($reg->status === 'approved')
                            <span class="badge badge-active"><i class="ph ph-check-circle"></i> Authorized</span>
                        @else
                            <span class="badge badge-blocked"><i class="ph ph-prohibit"></i> Blacklisted</span>
                        @endif
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <button class="btn btn-icon" onclick="viewDetails({{ $reg->id }})" title="View Details">
                                <i class="ph ph-eye"></i>
                            </button>
                            <button id="toggle-btn-{{ $reg->id }}" 
                                    class="btn {{ $reg->status === 'approved' ? 'btn-danger' : 'btn-success' }}" 
                                    style="padding: 0.4rem 0.8rem; font-size: 0.75rem; min-width: 100px;"
                                    onclick="toggleTagStatus({{ $reg->id }}, '{{ $reg->status }}')">
                                <i class="ph {{ $reg->status === 'approved' ? 'ph-lock' : 'ph-lock-open' }}"></i>
                                {{ $reg->status === 'approved' ? 'Blacklist' : 'Activate' }}
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 4rem; color: #94a3b8;">
                        <i class="ph ph-identification-card" style="font-size: 3rem; opacity: 0.2; display: block; margin: 0 auto 1rem;"></i>
                        No RFID registrations matched your search.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $registrations->links() }}
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    async function toggleTagStatus(id, currentStatus) {
        const action = currentStatus === 'approved' ? 'BLACKLIST' : 'ACTIVATE';
        const color = currentStatus === 'approved' ? '#ef4444' : '#10b981';
        
        const result = await Swal.fire({
            title: `${action} Tag?`,
            text: `Are you sure you want to ${action.toLowerCase()} this RFID tag? This will immediately affect campus access.`,
            icon: currentStatus === 'approved' ? 'warning' : 'info',
            showCancelButton: true,
            confirmButtonText: `Yes, ${action}`,
            cancelButtonText: 'Cancel',
            confirmButtonColor: color,
            reverseButtons: true
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(`{{ url('admin/rfid') }}/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: data.message,
                        showConfirmButton: false,
                        timer: 2000
                    });
                    
                    // Update UI dynamically
                    const statusCell = document.getElementById(`status-cell-${id}`);
                    const toggleBtn = document.getElementById(`toggle-btn-${id}`);
                    
                    if (data.new_status === 'approved') {
                        statusCell.innerHTML = '<span class="badge badge-active"><i class="ph ph-check-circle"></i> Authorized</span>';
                        toggleBtn.className = 'btn btn-danger';
                        toggleBtn.innerHTML = '<i class="ph ph-lock"></i> Blacklist';
                        toggleBtn.setAttribute('onclick', `toggleTagStatus(${id}, 'approved')`);
                    } else {
                        statusCell.innerHTML = '<span class="badge badge-blocked"><i class="ph ph-prohibit"></i> Blacklisted</span>';
                        toggleBtn.className = 'btn btn-success';
                        toggleBtn.innerHTML = '<i class="ph ph-lock-open"></i> Activate';
                        toggleBtn.setAttribute('onclick', `toggleTagStatus(${id}, 'rejected')`);
                    }
                }
            } catch (e) {
                Swal.fire('Error', 'Failed to update tag status.', 'error');
            }
        }
    }

    async function viewDetails(id) {
        try {
            const response = await fetch(`{{ url('admin/rfid') }}/${id}`);
            const reg = await response.json();
            
            Swal.fire({
                title: 'RFID Tag Details',
                html: `
                    <div style="text-align: left; font-size: 0.9rem;">
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #e2e8f0;">
                            <div style="margin-bottom: 0.5rem;"><b style="color: #475569;">RFID Tag ID:</b> <span style="font-family: monospace;">${reg.rfid_tag_id}</span></div>
                            <div style="margin-bottom: 0.5rem;"><b style="color: #475569;">Status:</b> ${reg.status === 'approved' ? '<span style="color:#10b981">AUTHORIZED</span>' : '<span style="color:#ef4444">BLACKLISTED</span>'}</div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <h4 style="font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 0.5rem;">Applicant</h4>
                                <div style="font-weight: 600;">${reg.full_name}</div>
                                <div style="font-size: 0.8rem;">${reg.role ? reg.role.toUpperCase() : 'N/A'}</div>
                                <div style="font-size: 0.8rem;">${reg.contact_number || 'N/A'}</div>
                            </div>
                            <div>
                                <h4 style="font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; margin-bottom: 0.5rem;">Vehicle</h4>
                                <div style="font-weight: 600;">${reg.plate_number}</div>
                                <div style="font-size: 0.8rem;">${reg.make_brand} ${reg.model_name || ''}</div>
                                <div style="font-size: 0.8rem; font-weight: 700;">${reg.vehicle_type.toUpperCase()}</div>
                            </div>
                        </div>
                        <hr style="margin: 1rem 0; border: 0; border-top: 1px solid #e2e8f0;">
                        <div style="font-size: 0.75rem; color: #64748b;">
                            Registered by ${reg.office_user ? reg.office_user.name : 'System'} on ${new Date(reg.created_at).toLocaleDateString()}
                        </div>
                    </div>
                `,
                confirmButtonColor: '#1e293b'
            });
        } catch (e) {
            Swal.fire('Error', 'Could not load tag details.', 'error');
        }
    }

    let bridgeSocket = null;
    let isBridgeMode = false;
    let registrationMode = 'auto'; // 'auto' or 'manual'
    let scanStep = 0; // 0: none, 1: first scan, 2: verified
    let firstScanId = '';

    function updateScannerStatus(text, type = 'info') {
        const textEl = document.getElementById('modalStatusText');
        const iconEl = document.getElementById('modalStatusIcon');
        if (!textEl || !iconEl) return;

        textEl.innerText = text;
        if (type === 'success') {
            iconEl.innerHTML = '<i class="ph ph-check-circle" style="color: #10b981"></i>';
            textEl.style.color = '#10b981';
        } else if (type === 'warning') {
            iconEl.innerHTML = '<i class="ph ph-broadcast" style="color: #f59e0b"></i>';
            textEl.style.color = '#f59e0b';
        } else if (type === 'danger') {
            iconEl.innerHTML = '<i class="ph ph-warning-circle" style="color: #ef4444"></i>';
            textEl.style.color = '#ef4444';
        } else {
            iconEl.innerHTML = '<i class="ph ph-circle" style="color: #94a3b8"></i>';
            textEl.style.color = '#64748b';
        }
    }

    function resetModalScanner() {
        scanStep = 0;
        firstScanId = '';
        const rfidInput = document.getElementById('modalRfidInput');
        const badge = document.getElementById('modalScanBadge');
        if (rfidInput) {
            rfidInput.value = '';
            rfidInput.style.background = '#f1f5f9';
            rfidInput.placeholder = "Waiting for scan...";
        }
        if (badge) badge.classList.add('hidden');
        updateScannerStatus('Hardware scanner ready for assignment.', 'info');
    }

    function showRegistrationModal() {
        Swal.fire({
            title: 'Quick Tag Registration',
            width: '600px',
            html: `
                <div style="text-align: left; font-size: 0.9rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1rem;">
                        <div>
                            <label style="display:block; font-weight:600; margin-bottom:4px; color:#475569;">Owner Full Name</label>
                            <input id="regName" class="swal2-input" style="width:100%; margin:0; height:38px; font-size:0.9rem;" placeholder="e.g. Juan Dela Cruz">
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; margin-bottom:4px; color:#475569;">System Role</label>
                            <select id="regRole" class="swal2-select" style="width:100%; margin:0; height:38px; font-size:0.9rem;">
                                <option value="student">Student</option>
                                <option value="faculty">Faculty</option>
                                <option value="staff">Non-Teaching</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1rem;">
                        <div>
                            <label style="display:block; font-weight:600; margin-bottom:4px; color:#475569;">Vehicle Category</label>
                            <select id="regType" class="swal2-select" style="width:100%; margin:0; height:38px; font-size:0.9rem;">
                                <option value="" disabled selected>Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->name }}" data-id="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; margin-bottom:4px; color:#475569;">Make / Brand</label>
                            <select id="regBrand" class="swal2-select" style="width:100%; margin:0; height:38px; font-size:0.9rem;" disabled>
                                <option value="">Select Brand</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display:block; font-weight:600; margin-bottom:4px; color:#475569;">Vehicle Model</label>
                            <select id="regModel" class="swal2-select" style="width:100%; margin:0; height:38px; font-size:0.9rem;" disabled>
                                <option value="">Select Model</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; margin-bottom:4px; color:#475569;">Plate Number</label>
                            <input id="regPlate" class="swal2-input" style="width:100%; margin:0; height:38px; font-size:0.9rem;" placeholder="ABC 1234">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 1rem;">
                        <div>
                            <label style="display:block; font-weight:600; margin-bottom:4px; color:#475569;">Contact Number</label>
                            <input id="regContact" class="swal2-input" style="width:100%; margin:0; height:38px; font-size:0.9rem;" placeholder="09XXXXXXXXX">
                        </div>
                        <div>
                            <!-- Placeholder -->
                        </div>
                    </div>

                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                            <div style="display:flex; background:#f1f5f9; padding:3px; border-radius:6px; gap:2px;">
                                <button id="btnModeAuto" class="btn-mode active" style="padding:0.3rem 0.6rem; font-size:0.7rem;">Automatic</button>
                                <button id="btnModeManual" class="btn-mode" style="padding:0.3rem 0.6rem; font-size:0.7rem;">Manual</button>
                            </div>
                            <button id="btnModalConnect" class="btn btn-outline" style="padding:0.3rem 0.6rem; font-size:0.7rem; gap:4px;">
                                <i class="ph ph-broadcast"></i> Connect
                            </button>
                        </div>

                        <div id="modalAutoBox">
                           <label style="display:block; font-weight:700; font-size:0.75rem; text-transform:uppercase; color:#64748b; margin-bottom:8px;">RFID Tag ID (Requirement: Double Scan)</label>
                           <div style="display:flex; gap:10px; align-items:center;">
                                <div style="flex-grow:1; position:relative;">
                                    <input id="modalRfidInput" readonly style="width:100%; padding:10px; border-radius:6px; border:1px solid #e2e8f0; background:#f1f5f9; font-family:monospace; font-weight:600;" placeholder="Waiting for scan...">
                                    <span id="modalScanBadge" class="hidden" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); font-size:0.65rem; font-weight:800; padding:2px 6px; border-radius:4px; background:#fbbf24; color:#78350f;">STEP 1</span>
                                </div>
                                <button id="btnModalReset" class="btn btn-outline" style="height:40px; width:40px; padding:0; display:flex; align-items:center; justify-content:center;" title="Reset Scan"><i class="ph ph-arrows-clockwise"></i></button>
                           </div>
                           <div id="modalStatusBox" style="margin-top:10px; display:flex; align-items:center; gap:6px; font-size:0.75rem;">
                                <span id="modalStatusIcon"><i class="ph ph-circle"></i></span>
                                <span id="modalStatusText" style="color:#64748b;">Bridge disconnected.</span>
                           </div>
                        </div>

                        <div id="modalManualBox" class="hidden">
                           <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <div>
                                    <label style="display:block; font-weight:600; margin-bottom:4px; color:#475569; font-size:0.75rem;">Enter Tag ID</label>
                                    <input id="manual1" style="width:100%; padding:8px; border-radius:6px; border:1px solid #e2e8f0; font-family:monospace;">
                                </div>
                                <div>
                                    <label style="display:block; font-weight:600; margin-bottom:4px; color:#475569; font-size:0.75rem;">Confirm Tag ID</label>
                                    <input id="manual2" style="width:100%; padding:8px; border-radius:6px; border:1px solid #e2e8f0; font-family:monospace;">
                                </div>
                           </div>
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Register Tag',
            confirmButtonColor: '#1e293b',
            didOpen: () => {
                const autoBtn = document.getElementById('btnModeAuto');
                const manualBtn = document.getElementById('btnModeManual');
                const autoBox = document.getElementById('modalAutoBox');
                const manualBox = document.getElementById('modalManualBox');
                const connectBtn = document.getElementById('btnModalConnect');
                const rfidOut = document.getElementById('modalRfidInput');
                const man1 = document.getElementById('manual1');
                const man2 = document.getElementById('manual2');

                const regType = document.getElementById('regType');
                const regBrand = document.getElementById('regBrand');
                const regModel = document.getElementById('regModel');

                regType.onchange = async () => {
                    const categoryId = regType.options[regType.selectedIndex].getAttribute('data-id');
                    regBrand.innerHTML = '<option value="">Loading...</option>';
                    regBrand.disabled = true;
                    regModel.innerHTML = '<option value="">Select Model</option>';
                    regModel.disabled = true;

                    try {
                        const res = await fetch(`{{ url('api/brands') }}/${categoryId}`);
                        const brands = await res.json();
                        regBrand.innerHTML = '<option value="">Select Brand</option>';
                        if (brands.length === 0) {
                            regBrand.innerHTML = '<option value="" disabled>No brands for this category</option>';
                        } else {
                            brands.forEach(b => regBrand.innerHTML += `<option value="${b.name}" data-id="${b.id}">${b.name}</option>`);
                        }
                        regBrand.innerHTML += '<option value="Other" data-id="">Other / Not Listed</option>';
                        regBrand.disabled = false;
                    } catch (e) { 
                        regBrand.innerHTML = '<option value="Other" data-id="">Other / Manual Entry</option>';
                        regBrand.disabled = false;
                    }
                };

                regBrand.onchange = async () => {
                    const selectedBrand = regBrand.options[regBrand.selectedIndex];
                    const brandId = selectedBrand.getAttribute('data-id');
                    
                    if (!brandId) {
                        regModel.innerHTML = '<option value="Other" selected>Other / Not Listed</option>';
                        regModel.disabled = false;
                        return;
                    }

                    regModel.innerHTML = '<option value="">Loading...</option>';
                    regModel.disabled = true;

                    try {
                        const res = await fetch(`{{ url('api/models') }}/${brandId}`);
                        const models = await res.json();
                        regModel.innerHTML = '<option value="">Select Model</option>';
                        if (models.length === 0) {
                            regModel.innerHTML = '<option value="Other" selected>Other / Not Listed</option>';
                        } else {
                            models.forEach(m => regModel.innerHTML += `<option value="${m.name}">${m.name}</option>`);
                            regModel.innerHTML += '<option value="Other">Other / Not Listed</option>';
                        }
                        regModel.disabled = false;
                    } catch (e) { 
                        regModel.innerHTML = '<option value="Other" selected>Other / Manual Entry</option>';
                        regModel.disabled = false;
                    }
                };

                autoBtn.onclick = () => {
                    registrationMode = 'auto';
                    autoBtn.classList.add('active');
                    manualBtn.classList.remove('active');
                    autoBox.classList.remove('hidden');
                    manualBox.classList.add('hidden');
                    resetModalScanner();
                };

                manualBtn.onclick = () => {
                    registrationMode = 'manual';
                    manualBtn.classList.add('active');
                    autoBtn.classList.remove('active');
                    manualBox.classList.remove('hidden');
                    autoBox.classList.add('hidden');
                    resetModalScanner();
                };

                document.getElementById('btnModalReset').onclick = resetModalScanner;

                function validateManual() {
                    const t1 = man1.value.trim();
                    const t2 = man2.value.trim();
                    if (t1 && t2 && t1 === t2) {
                        rfidOut.value = t1;
                        man1.style.borderColor = '#10b981';
                        man2.style.borderColor = '#10b981';
                    } else {
                        rfidOut.value = '';
                        if (t1 && t2) {
                            man1.style.borderColor = '#ef4444';
                            man2.style.borderColor = '#ef4444';
                        }
                    }
                }
                man1.oninput = validateManual;
                man2.oninput = validateManual;

                if (isBridgeMode) {
                    connectBtn.innerHTML = '<i class="ph ph-plugs-connected"></i> Connected';
                    updateScannerStatus('Hardware scanner active.', 'success');
                }

                connectBtn.onclick = () => {
                   if (bridgeSocket && bridgeSocket.readyState === WebSocket.OPEN) {
                       bridgeSocket.close();
                       return;
                   }
                   connectBtn.innerHTML = '...';
                   bridgeSocket = new WebSocket('ws://127.0.0.1:8080');
                   bridgeSocket.onopen = () => {
                       isBridgeMode = true;
                       connectBtn.innerHTML = '<i class="ph ph-plugs-connected"></i> Connected';
                       updateScannerStatus('Connection established.', 'success');
                   };
                   bridgeSocket.onmessage = async (e) => {
                       if (registrationMode !== 'auto') return;
                       const data = JSON.parse(e.data);
                       if (data.tagId) {
                           const sid = data.tagId;
                           const badge = document.getElementById('modalScanBadge');
                           if (scanStep === 0) {
                               firstScanId = sid;
                               scanStep = 1;
                               rfidOut.placeholder = "VERIFYING...";
                               badge.classList.remove('hidden');
                               badge.innerText = "STEP 1";
                               updateScannerStatus('First scan captured. Scan again.', 'warning');
                           } else if (scanStep === 1) {
                               if (sid === firstScanId) {
                                   // Verify if tag is already taken
                                   const checkRes = await fetch(`{{ url('admin/check-tag') }}?tagId=${sid}`);
                                   const checkData = await checkRes.json();
                                   
                                   if (checkData.exists) {
                                       updateScannerStatus('Conflict: Tag already in use!', 'danger');
                                       Swal.fire({
                                           icon: 'error',
                                           title: 'Tag already registered',
                                           text: checkData.message,
                                           confirmButtonColor: '#ef4444'
                                       });
                                       resetModalScanner();
                                       return;
                                   }

                                   rfidOut.value = sid;
                                   scanStep = 2;
                                   badge.innerText = "VERIFIED";
                                   badge.style.background = '#10b981';
                                   badge.style.color = 'white';
                                   updateScannerStatus('Tag verified and ready.', 'success');
                               } else {
                                   updateScannerStatus('Mismatch! Restarting.', 'danger');
                                   resetModalScanner();
                               }
                           }
                       }
                   };
                   bridgeSocket.onclose = () => {
                       isBridgeMode = false;
                       connectBtn.innerHTML = '<i class="ph ph-broadcast"></i> Connect';
                       updateScannerStatus('Bridge closed.', 'info');
                   };
                };
            },
            preConfirm: async () => {
                const name = document.getElementById('regName').value;
                const role = document.getElementById('regRole').value;
                const type = document.getElementById('regType').value;
                const plate = document.getElementById('regPlate').value;
                const brand = document.getElementById('regBrand').value;
                const model = document.getElementById('regModel').value;
                const contact = document.getElementById('regContact').value;
                const tag = document.getElementById('modalRfidInput').value;

                if (!name || !plate || !brand || !contact) {
                    Swal.showValidationMessage('Please fill all owner/vehicle fields.');
                    return false;
                }

                if (!tag) {
                    Swal.showValidationMessage('RFID verification incomplete.');
                    return false;
                }

                try {
                    const response = await fetch('{{ route("admin.rfid.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            fullName: name,
                            role: role,
                            vehicleType: type,
                            plateNumber: plate,
                            makeBrand: brand,
                            contactNumber: contact,
                            rfidTagId: tag,
                            modelName: model
                        })
                    });
                    const data = await response.json();
                    if (!data.success) {
                        Swal.showValidationMessage(data.message || 'Registration failed.');
                        return false;
                    }
                    return data;
                } catch (e) {
                    Swal.showValidationMessage('Connection error.');
                }
            }
        }).then(res => {
            if (res.isConfirmed) {
                Swal.fire('Success', 'Vehicle & Tag registered.', 'success').then(() => window.location.reload());
            }
        });
    }
</script>

<style>
    .badge-plate { background: #f1f5f9; color: #1e293b; border: 1px solid #e2e8f0; padding: 0.2rem 0.6rem; border-radius: 6px; font-weight: 700; font-family: 'Outfit', sans-serif; font-size: 0.85rem; }
    .badge { padding: 0.35rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
    .badge-active { background: #ecfdf5; color: #059669; }
    .badge-blocked { background: #fef2f2; color: #dc2626; }
    .btn-icon { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
    .btn-icon:hover { background: #f1f5f9; color: #1e293b; }
    .btn-success { background: #10b981; color: white; border: none; border-radius: 8px; transition: opacity 0.2s; }
    .btn-success:hover { opacity: 0.9; }
    .btn-danger { background: #ef4444; color: white; border: none; border-radius: 8px; transition: opacity 0.2s; }
    .btn-danger:hover { opacity: 0.9; }
    
    /* Mode Toggle for Modal */
    .btn-mode {
        background: transparent;
        border: none;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s;
        border-radius: 6px;
        font-weight: 600;
        outline: none;
    }
    .btn-mode.active {
        background: white;
        color: #1e293b;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
</style>

@endsection
