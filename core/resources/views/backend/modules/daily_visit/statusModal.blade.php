<div class="modal-header" >
    <p class="modal-title fw-bold text-xl">Change Daily Visit Status</p>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<form action="{{ route('daily-visit.updateStatus', $dailyVisit->id) }}" method="POST" data-ajax="true">
    @csrf
    <div class="modal-body">
        <div class="mb-3">
            <label for="status" class="form-label">Select Status</label>
            <select name="status" id="status" class="form-select">
                <option value="pending" {{ $dailyVisit->status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="delivered" {{ $dailyVisit->status == 'approved' ? 'selected' : '' }}>Approved</option>
                
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-info">Update Status</button>
    </div>
</form>
