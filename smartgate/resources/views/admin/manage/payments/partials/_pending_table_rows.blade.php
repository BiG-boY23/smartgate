@forelse($registrations as $reg)
<tr>
    <td><strong>{{ $reg->full_name }}</strong></td>
    <td><span class="badge" style="background: rgba(116, 27, 27, 0.1); color: #741b1b; padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 0.75rem;">{{ strtoupper($reg->role) }}</span></td>
    <td>{{ $reg->make_brand }} — <strong>{{ $reg->plate_number }}</strong></td>
    <td style="text-align: right;">
        {{-- Registration Flow Status --}}
        @php
            $sBadge = 'badge-warning';
            if($reg->status === 'verified') $sBadge = 'badge-info';
            if($reg->status === 'ACTIVE') $sBadge = 'badge-success';
        @endphp
        <div style="display: inline-flex; flex-direction: column; align-items: flex-end; gap: 6px;">
            <span class="badge {{ $sBadge }}" style="padding: 2px 8px; font-size: 0.65rem;">{{ strtoupper($reg->status) }}</span>

            @if($reg->rfid_tag_id)
                <span class="badge" style="background: #16a34a; color: white; border: none; font-size: 0.75rem;">
                    <i class="ph ph-check-circle"></i> PAID & ISSUED ({{ $reg->rfid_tag_id }})
                </span>
            @else
                <button class="btn btn-primary btn-process-payment" 
                        data-id="{{ $reg->id }}" 
                        data-name="{{ $reg->full_name }}"
                        data-fee="{{ $rfid_fee }}"
                        style="padding: 6px 12px; font-size: 0.75rem; background: #741b1b; border: none; font-weight: 800; border-radius: 8px;">
                    <i class="ph ph-credit-card"></i> Process Payment & Issue
                </button>
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="4" style="text-align: center; padding: 4rem; color: #94a3b8;">
        <i class="ph ph-users-four" style="font-size: 3rem; opacity: 0.3;"></i>
        <p style="margin-top: 1rem;">No matching accounts found in the system.</p>
    </td>
</tr>
@endforelse
