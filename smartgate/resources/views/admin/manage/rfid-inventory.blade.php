@extends('layouts.app')

@section('title', 'RFID Tag Inventory')
@section('subtitle', 'Track available vs. assigned tags to manage physical hardware issuance.')

@section('content')
<div class="table-container">
    <div class="section-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="ph ph-tag" style="font-size: 1.5rem; color: #741b1b;"></i>
            <h3>Hardware Inventory (RFID)</h3>
        </div>
        <button class="btn btn-primary" onclick="showAddTagModal()">
            <i class="ph ph-plus"></i> Register Bulk/New Tag
        </button>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: #f8fafc; padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0;">
            <label style="font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Total Tags</label>
            <h2 style="margin: 0; font-size: 1.75rem; font-weight: 800; color: #1e293b;">{{ $tags->count() }}</h2>
        </div>
        <div style="background: #f0fdf4; padding: 1.5rem; border-radius: 16px; border: 1px solid #dcfce7;">
            <label style="font-size: 0.7rem; font-weight: 800; color: #166534; text-transform: uppercase;">Available</label>
            <h2 style="margin: 0; font-size: 1.75rem; font-weight: 800; color: #166534;">{{ $tags->where('status', 'available')->count() }}</h2>
        </div>
        <div style="background: #fef2f2; padding: 1.5rem; border-radius: 16px; border: 1px solid #fee2e2;">
            <label style="font-size: 0.7rem; font-weight: 800; color: #991b1b; text-transform: uppercase;">Assigned/In-Use</label>
            <h2 style="margin: 0; font-size: 1.75rem; font-weight: 800; color: #991b1b;">{{ $tags->where('status', 'assigned')->count() }}</h2>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Tag ID (Hardware)</th>
                    <th>Status</th>
                    <th>Registered At</th>
                    <th style="text-align: right;">Purge Item</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tags as $tag)
                <tr>
                    <td style="font-family: 'JetBrains Mono', monospace; font-weight: 800; color: #741b1b;">{{ $tag->tag_id }}</td>
                    <td>
                        @if($tag->status === 'available')
                            <span class="badge" style="background: #dcfce7; color: #166534;">AVAILABLE</span>
                        @elseif($tag->status === 'assigned')
                            <span class="badge" style="background: #eff6ff; color: #1e40af;">ASSIGNED</span>
                        @else
                            <span class="badge" style="background: #f1f5f9; color: #94a3b8;">DEACTIVATED</span>
                        @endif
                    </td>
                    <td>{{ $tag->created_at->format('M d, Y h:i A') }}</td>
                    <td style="text-align: right;">
                        <button type="button" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; color: #dc2626; border-color: #fee2e2;"
                                onclick="confirmDeleteTag({{ $tag->id }})">
                            <i class="ph ph-trash"></i>
                        </button>
                        <form id="delete-tag-{{ $tag->id }}" action="{{ route('admin.manage.rfid-inventory.destroy', $tag->id) }}" method="POST" style="display: none;">
                            @csrf
                            @method('DELETE')
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align: center; color: #94a3b8;">No physical tags in inventory.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function showAddTagModal() {
        Swal.fire({
            title: 'Register New Tag',
            html: `
                <form id="addTagForm" action="{{ route('admin.manage.rfid-inventory.store') }}" method="POST" style="text-align: left;">
                    @csrf
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">RFID Tag Hex/ID</label>
                        <input type="text" name="tag_id" id="bulk_tag_id" class="swal2-input custom-swal-input" placeholder="e.g. E280119120002167098F" required autocomplete="off">
                    </div>
                    <p style="font-size: 0.75rem; color: #64748b;">(Focus this input and scan with a desktop reader for rapid entry.)</p>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Register Tag',
            confirmButtonColor: '#741b1b',
            didOpen: () => {
                document.getElementById('bulk_tag_id').focus();
            },
            preConfirm: () => {
                const form = document.getElementById('addTagForm');
                if (!form.checkValidity()) { form.reportValidity(); return false; }
                form.submit();
            }
        });
    }

    function confirmDeleteTag(id) {
        Swal.fire({
            title: 'Remove Tag?',
            text: "This tag will be permanently deleted from the hardware inventory list.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#741b1b',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, remove it'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-tag-' + id).submit();
            }
        });
    }
</script>

<style>
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .section-header h3 { margin: 0; font-size: 1.1rem; font-weight: 800; color: #1e293b; }
    .badge { padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.05em; }
    .custom-swal-input { width: 100% !important; margin: 0 !important; height: 38px !important; font-size: 0.9rem !important; }
    .form-label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.3rem; color: #475569; }
</style>
@endsection
