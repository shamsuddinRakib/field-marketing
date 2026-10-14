<div class="modal-header py-16 px-24 border-0" data-modal-key="branch-edit">
  <h5 class="modal-title">Fund Request</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-24">
  <form id="branchEditForm" action="{{ route('fund-request.update', $fundRequest->id) }}" method="post" data-ajax="true">
    @csrf
    @method('PUT')
    <div class="row">
      <div class="col-md-6 mb-20">
        <label class="form-label text-sm mb-8">Marketing Representative <span class="text-danger">*</span></label>
       <select class="form-control form-control-sm  js-s2-ajax" name="user_id" id="user"
         data-url="{{ route('marketing-representative.marketing-representatives.select2') }}" data-placeholder="Select Marketing Reprenstative">
         <option value="{{$fundRequest->user_id}}" selected>{{$fundRequest->user->name}}</option>

          </select>
        <div class="invalid-feedback d-block user_id-error" style="display:none"></div>
      </div>



      <div class="col-md-6 mb-20">
        <label class="form-label text-sm mb-8">Amount</label>
        <input type="number" name="amount" min="1" value="{{$fundRequest->amount}}" class="form-control radius-8" placeholder="">
        <div class="invalid-feedback d-block amount-error" style="display:none"></div>
      </div>

      <div class="col-md-6 mb-20">
        <label class="form-label text-sm mb-8">Note</label>
        <input type="text" name="note" value="{{$fundRequest->note}}" class="form-control radius-8" placeholder="note">
        <div class="invalid-feedback d-block note-error" style="display:none"></div>
      </div>


      {{-- <div class="col-12 mb-8">
        <label class="form-label text-sm mb-8">Active?</label>
        <div class="form-switch switch-purple d-flex align-items-center gap-3">
          <input type="hidden" name="is_active" value="0">
          <input class="form-check-input" type="checkbox" name="is_active" value="1" id="branchIsActive" checked>
          <label class="form-check-label" for="branchIsActive">Enable this color</label>
        </div>
        <div class="invalid-feedback d-block is_active-error" style="display:none"></div>
      </div>
    </div>  --}}

    <div class="d-flex align-items-center justify-content-center gap-3 mt-16">
      <button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-primary px-48 py-12 radius-8">Save</button>
    </div>
  </form>
</div>
{{-- @section('script') --}}
<script>

  window.S2 && window.S2.auto && window.S2.auto();
</script>
{{-- @endsection --}}