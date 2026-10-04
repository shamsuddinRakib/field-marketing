<div class="modal-header py-16 px-24 border-0" data-modal-key="branch-edit">
  <h5 class="modal-title">Assign Product</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body p-24">
  <form id="branchEditForm" action="{{ route('assign-specimen.update', $assignSpecimen->id) }}" method="post" data-ajax="true">
    @csrf
    @method('PUT')
    <div class="row">
      <div class="col-md-6 mb-20">
        <label class="form-label text-sm mb-8">Marketing Representative <span class="text-danger">*</span></label>
       <select class="form-control form-control-sm  js-s2-ajax" name="user_id" id="user"
         data-url="{{ route('marketing-representative.marketing-representatives.select2') }}" data-placeholder="Select Marketing Reprenstative">
         <option value="{{$assignSpecimen->user_id}}" selected>{{$assignSpecimen->user->name}}</option>

          </select>
        <div class="invalid-feedback d-block user_id-error" style="display:none"></div>
      </div>

     
      <div class="col-md-6 mb-20">
        <label class="form-label text-sm mb-8">Type <span class="text-danger">*</span></label>
         <select class="form-control form-control-sm" name="type"  placeholder="Select Type" id="type">
            <option value="">Select Type</option>
            <option value="teacher" {{$assignSpecimen->teacher_id?'selected':''}}>Teacher</option>
            <option value="library" {{$assignSpecimen->library_id?'selected':''}}>Library</option>
          </select>
        <div class="invalid-feedback d-block product_id-error" style="display:none"></div>
      </div>


       <div class="col-md-6 mb-20 {{$assignSpecimen->teacher_id?'':'d-none'}}" id="teacher_div">
         <label class="form-label text-sm mb-8">Teacher<span class="text-danger">*</span></label>
         <select class="form-control form-control-sm  js-s2-ajax" name="visitable_id" id="teacher"
                                data-url="{{ route('teacher.teachers.institutions.select2') }}" data-placeholder="Select Teacher">
            @if($assignSpecimen->teacher)
                     <option value="{{$assignSpecimen->teacher_id}}" selected>{{$assignSpecimen->teacher->teacher_name}}</option>
        @endif

          </select>
        <div class="invalid-feedback d-block teacher_id-error" style="display:none"></div>
      </div>

       <div class="col-md-6 mb-20 {{$assignSpecimen->library_id?'':'d-none'}}" id="library_div">
        <label class="form-label text-sm mb-8">Library<span class="text-danger">*</span></label>
       <select class="form-control form-control-sm  js-s2-ajax" name="visitable_id" id="library"
                                data-url="{{ route('library.libraries.select2') }}" data-placeholder="Select Library">
          @if($assignSpecimen->library)
          <option value="{{$assignSpecimen->library_id}}" selected>{{$assignSpecimen->library->name}}</option>
        @endif

          </select>
        <div class="invalid-feedback d-block library_id-error" style="display:none"></div>
      </div>

       <div class="col-md-6 mb-20 " >
        <label class="form-label text-sm mb-8">Product<span class="text-danger">*</span></label>
       <select class="form-control form-control-sm  js-s2-ajax" name="product_id" 
           data-url="{{ route('product.select2') }}" data-placeholder="Select Product">
           <option value="{{$assignSpecimen->product_id}}">{{$assignSpecimen->product->name}}</option>

          </select>
        <div class="invalid-feedback d-block product_id-error" style="display:none"></div>
      </div>

            <div class="col-md-6 mb-20">
        <label class="form-label text-sm mb-8">Quantity</label>
        <input type="number" name="quantity" min="1" value="{{$assignSpecimen->quantity}}" class="form-control radius-8" placeholder="">
        <div class="invalid-feedback d-block quantity-error" style="display:none"></div>
      </div>

      <div class="col-md-6 mb-20">
        <label class="form-label text-sm mb-8">Note</label>
        <input type="text" name="note" class="form-control radius-8" placeholder="note"  value="{{$assignSpecimen->note}}">
        <div class="invalid-feedback d-block note-error" style="display:none"></div>
      </div>

    <div class="d-flex align-items-center justify-content-center gap-3 mt-16">
      <button type="button" class="btn border border-danger-600 text-danger-600 px-40 py-11 radius-8" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-primary px-48 py-12 radius-8">Save</button>
    </div>
  </form>
</div>

<script>

  window.S2 && window.S2.auto && window.S2.auto();

   $('#type').on('change', ()=> {
   
    if($('#type').val()=='teacher'){
        $('#teacher_div').removeClass('d-none');
        $('#library_div').addClass('d-none');
           $('#teacher').prop('disabled', false);
        $('#library').prop('disabled', true);
    }else{
      $('#teacher_div').addClass('d-none');
         $('#library_div').removeClass('d-none');
            $('#teacher').prop('disabled', true);
        $('#library').prop('disabled', false);
    }
  })
</script>
