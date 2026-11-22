@extends('backend.layouts.master')

@section('meta')
    <title>Edit Product </title>
@endsection



@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">Product Management</h6>
            <p class="fw-semibold mb-0">Edit Product</p>
        </div>

        <ul class="d-flex align-items-center gap-2 mb-0">
            <li class="fw-medium">
                <a href="{{ route('backend.dashboard') }}" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Product</li>
        </ul>

        {{-- Button on a new line (right aligned) --}}
        <div class="text-end w-100 ">
            <a href="{{ route('product.products.index') }}" class="btn btn-secondary bg-dark "><i data-feather="arrow-left"
                    class="me-2"></i>Back to
                Products</a>
        </div>
    </div>

    <form id="product_form" action="{{ route('product.products.update', $product->id) }}" method="POST"
        enctype="multipart/form-data">
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Whoops! Something went wrong.</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class=" ">
            <div class="  ">
                <div class="accordion-card-one accordion mb-6 shadow-sm rounded-circle" id="accordionExample">
                    <div class="accordion-item">
                        <div class="accordion-header" id="headingOne">
                            <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseOne"
                                aria-controls="collapseOne">
                                <div class="addproduct-icon">
                                    <h6><i data-feather="info" class="add-info"></i><span>Basic Information</span></h6>
                                    <a href="javascript:void(0);"><i data-feather="chevron-down"
                                            class="chevron-down-add"></i></a>
                                </div>
                            </div>
                        </div>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">

                                <div class="row">


                                    {{-- Row 1: Identification (3 equal columns) --}}
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Name <span class="star-sign">*</span></label>
                                            <input type="text" class="form-control" id="name" name="name"
                                                value="{{ $product->name }}" placeholder="Enter product name">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Slug <span class="star-sign">*</span></label>
                                            <input type="text" class="form-control" id="slug" name="slug"
                                                value="{{ $product->slug }}" placeholder="Enter product slug">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="mb-3">
                                            <label for="product_code" class="form-label">Product Code <span class="badge bg-secondary-focus text-secondary-main text-xs px-2 py-0.5">Auto</span></label>
                                            <input type="text" name="product_code" class="form-control" placeholder="Auto-generated"
                                                id="product_code" value="{{ $product->product_code }}" readonly>
                                        </div>
                                    </div>

                                    {{-- Row 2: Category, Sub-Category, Brand (3 equal columns) --}}
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Category</label>
                                            <select name="category_id" id="category" class="form-control js-category-select">
                                                <option value="{{ $product->category_id }}" selected>
                                                    {{ $product->category->name }}</option>
                                            </select>
                                            <div class="invalid-feedback d-block category_id-error" style="display:none"></div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Sub-Category</label>
                                            <select name="subcategory_id" id="subcategory"
                                                class="form-control js-category-select">
                                                @if($product->subcategory)
                                                <option value="{{ $product->subcategory_id }}" selected>
                                                    {{ $product->subcategory->name }}</option>
                                                @endif
                                            </select>
                                            <div class="invalid-feedback d-block category_id-error" style="display:none"></div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Brand</label>
                                            <select name="brand_id" id="brand" class="form-control js-product-type-select">
                                                <option value="{{ $product->brand_id }}" selected>{{ $product->brand->name }}</option>
                                            </select>
                                            <div class="invalid-feedback d-block brand_type_id-error" style="display:none"></div>
                                        </div>
                                    </div>

                                    {{-- Row 3: Academic / Publication Details (3 equal columns) --}}
                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="mb-3">
                                            <label for="edition" class="form-label">Edition</label>
                                            <select name="edition" id="edition" class="form-control form-select select2">
                                                <option value="">Select or Type Edition...</option>
                                                @php
                                                    $editions = ['1st Edition', '2nd Edition', '3rd Edition', '4th Edition', '5th Edition', '6th Edition', '7th Edition', '8th Edition', '9th Edition', '10th Edition', 'Revised Edition', 'Special Edition', 'Latest Edition'];
                                                    $selectedEdition = old('edition', $product->edition);
                                                @endphp
                                                @if ($selectedEdition && !in_array($selectedEdition, $editions))
                                                    <option value="{{ $selectedEdition }}" selected>{{ $selectedEdition }}</option>
                                                @endif
                                                @foreach ($editions as $ed)
                                                    <option value="{{ $ed }}" {{ $selectedEdition == $ed ? 'selected' : '' }}>{{ $ed }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="mb-3">
                                            <label for="product_publish_year" class="form-label">Publish Year</label>
                                            <select name="product_publish_year" id="product_publish_year" class="form-control form-select select2">
                                                <option value="">Select Publish Year...</option>
                                                @php
                                                    $currYear = (int) date('Y');
                                                @endphp
                                                @for ($y = $currYear + 2; $y >= $currYear - 15; $y--)
                                                    <option value="{{ $y }}" {{ (old('product_publish_year', $product->product_publish_year) == $y) ? 'selected' : '' }}>{{ $y }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 col-12">
                                        <div class="mb-3">
                                            <label for="product_exam_year" class="form-label">Exam Year</label>
                                            <select name="product_exam_year" id="product_exam_year" class="form-control form-select select2">
                                                <option value="">Select Exam Year...</option>
                                                @php
                                                    $currYear = (int) date('Y');
                                                @endphp
                                                @for ($y = $currYear + 3; $y >= $currYear - 5; $y--)
                                                    <option value="{{ $y }}" {{ (old('product_exam_year', $product->product_exam_year) == $y) ? 'selected' : '' }}>{{ $y }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>

                                    


                                    {{-- Row 4: Pricing Details (4 equal columns) --}}
                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Purchase Price <span class="star-sign">*</span></label>
                                            <input type="number" class="form-control" name="cost_price"
                                                value="{{ $product->cost_price }}" placeholder="0">
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="mb-3">
                                            <label class="form-label">MRP Price <span class="star-sign">*</span></label>
                                            <input type="number" step="0.01" class="form-control" min="0"
                                                name="mrp" id="mrp" value="{{ $product->mrp }}">
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Discount</label>
                                            <div class="input-group">
                                                <select name="discount_type" class="form-select" id="discount_type">
                                                    <option value="0" {{ $product->discount_type == 0 ? 'selected' : '' }}>Percentage(%)</option>
                                                    <option value="1" {{ $product->discount_type == 1 ? 'selected' : '' }}>Flat</option>
                                                </select>
                                                <input type="number" name="discount_value" min="0"
                                                    class="form-control" placeholder="0" id="discount_value"
                                                    value="{{ $product->discount_value }}" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Sale Price <span class="star-sign">*</span></label>
                                            <input type="text" class="form-control" name="price" id="sale_price"
                                                value="{{ $product->price }}" readonly>
                                        </div>
                                    </div>
                  
                                    <div class="col-lg-12 row mt-10">              

                                    {{-- Row 5: Descriptions (2 columns) --}}
                                    <div class="col-lg-6 col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea rows="4" class="form-control" name="description"
                                                placeholder="Enter text here">{{ $product->description }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Short Description</label>
                                            <textarea rows="4" class="form-control" name="short_description"
                                                placeholder="Enter text here">{{ $product->short_description }}</textarea>
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="my-3 fw-bold mt-5">Image Section</div>

                                    <div class="col-12 row  m-auto rounded mb-3 dark-mode p-3 mt-">
                                      
                                        <div class="col-lg-3 col-sm-6 col-12">
                                            <div class="mb-3 ">
                                                <label class="form-label">Thumbnail Image<span
                                                        class="star-sign">*</span><small class="text-muted">(Max:
                                                        2MB)</small></label>
                                                <input type="hidden" name="remove_thumbnail_image"
                                                    id="remove_thumbnail_image" value="0">
                                                <div class="form-group">
                                                    <div class="row" id="thumbnail_image">

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12 ">
                                            <div class="mb-3">
                                                <label class="form-label">Images <small
                                                        class="text-muted">(Max: 2MB)</small></label>
                                                <input type="hidden" name="remove_image" id="remove_image"
                                                    value="0">
                                                <div class="form-group">
                                                    <div class="row" id="image">

                                                    </div>
                                                </div>
                                                <div id="preview"></div>
                                            </div>
                                        </div>

                                 
                                        <div class="row">
                                            <div class="col-12 mb-8">
                                                <label class="form-label text-sm mb-8">Active?</label>
                                                <div class="form-switch switch-purple d-flex align-items-center gap-3">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input class="form-check-input" type="checkbox" name="is_active"
                                                        value="1" id="categoryIsActive"
                                                        {{ $product->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="categoryIsActive">Enable this
                                                        product</label>
                                                </div>
                                                <div class="invalid-feedback d-block is_active-error"
                                                    style="display:none">
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-card-one accordion shadow-sm rounded-circle" id="accordionExample2">
                        <div class="accordion-item ">
                            <div class="accordion-header" id="headingTwo">
                                <div class="accordion-button" data-bs-toggle="collapse" data-bs-target="#collapseTwo"
                                    aria-controls="collapseTwo">
                                    <div class="text-editor add-list">
                                        <div class="addproduct-icon list icon">
                                            <h6><i data-feather="life-buoy" class="add-info"></i><span>Meta Section</span>
                                            </h6>
                                            <a href="javascript:void(0);"><i data-feather="chevron-down"
                                                    class="chevron-down-add"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="collapseTwo" class="accordion-collapse collapse show" aria-labelledby="headingTwo"
                                data-bs-parent="#accordionExample2">
                                <div class="accordion-body">

                                    <div class="tab-content" id="pills-tabContent">
                                        <div class="tab-pane fade show active" id="pills-home" role="tabpanel"
                                            aria-labelledby="pills-home-tab">
                                            <div class="row">


                                                <div class="col-lg-6 col-sm-6 col-12">
                                                    <div class=" ">
                                                        <label class="form-label">Meta Title</label>
                                                        <input type="text" class="form-control" name="meta_title"
                                                            value="{{ $product->meta_title ?? '' }}">
                                                    </div>
                                                </div>

                                                <div class="col-lg-6 col-sm-6 col-12 ">
                                                    <div class="">
                                                        <label class="form-label">Meta Keywords</label>
                                                        <input type="text" class="form-control" name="meta_keywords"
                                                            value="{{ $product->meta_keywords ?? '' }}">
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="row mt-3 justify-content-between">
                                                <div class="col-lg-3 col-sm-6 col-12 mt-8">
                                                    <label class="form-label"> Meta Image
                                                        <small class="text-muted">(Max: 2MB)</small></label>
                                                    <input type="hidden" name="remove_meta_image" id="remove_meta_image"
                                                        value="0">
                                                    <div class="form-group">
                                                        <div class="row" id="meta_image">

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-sm-6 col-12 mt-2">
                                                    <div class=" list ">
                                                        <label class="form-label "> Meta Description</label>
                                                        <textarea rows="8" cols="5" class="form-control h-100" name="meta_description"
                                                            placeholder="Enter text here">{{ $product->meta_description ?? '' }}</textarea>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-lg-12 mt-3">
                <div class="btn-addproduct mb-4">
                    <button type="submit" class="btn btn-primary ">Update All</button>
                </div>
            </div>
        </div>
    </form>
@endsection
@section('script')
    <style>
        .star-sign {
            color: red;
            font-weight: bold;
        }
    </style>
    <script src="{{ asset('theme/admin/assets/plugins/fileupload/spartan-multi-image-picker-min.js') }}"
        type="text/javascript"></script>
    <script>
        $(document).ready(function() {

            const products = @json($products);

            function generateSku() {
                const name = ($('#name').val().slice(0, 3)).toUpperCase();

                $('#sku').val(name ? name + Math.floor(1000000 + Math.random() * 9000000) : '');
            }
            $('#name').on('input', generateSku);

            $('#mrp,#discount_type,#discount_value').on('input change', function() {
                setTimeout(changePrice, 0); // wait one microtask
            });


            function changePrice() {
                $('.price').val((parseFloat($('#sale_price').val())).toFixed(2));
            }

            function setDiscountRange() {
                const type = $('#discount_type').val();
                const mrp = parseInt($('#mrp').val());
                if (type == '1') {
                    $('#discount_value').attr('max', mrp);
                } else {
                    $('#discount_value').attr('max', 100);
                }
            }
            setDiscountRange();
            $('#discount_type,#mrp').on('input', setDiscountRange);

            function calculatePrice() {
                const discountType = $('#discount_type').val(); // string
                const mrp = parseFloat($('#mrp').val()) || 0;
                const discount = parseFloat($('#discount_value').val()) || 0;

                let salePrice = 0;

                if (discountType === '0') { // percentage
                    salePrice = mrp - (mrp * discount / 100);
                } else { // flat amount
                    salePrice = mrp - discount;
                }

                $('#sale_price').val(salePrice.toFixed(2));
            };

            $('#mrp,#discount_type,#discount_value').on('input', calculatePrice);

            $('#category_type').select2({
                placeholder: 'Search category type...',
                allowClear: true,
                width: '100%',
                // important for AJAX search
                ajax: {
                    url: "{{ route('category.types.select2') }}", // Laravel route
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term // search query
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                }
            });

        });
        $('#category').select2({
            placeholder: 'Search category...',
            allowClear: true,
            width: '100%',

            // important for AJAX search
            ajax: {
                url: "{{ route('category.cat.select2') }}", // Laravel route
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        type: $('#category_type').val() // search query
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });

        $('#subcategory').select2({
            placeholder: 'Select Subcategory...',
            allowClear: true,
            width: '100%',

            // important for AJAX search
            ajax: {
                url: "{{ route('subcategory.select2') }}", // Laravel route
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        type: $('#category').val() // search query
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });

        $('#product_type').select2({
            placeholder: 'Select Product Type...',
            allowClear: true,
            width: '100%',

            // important for AJAX search
            ajax: {
                url: "{{ route('product-type.select2') }}", // Laravel route
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term

                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });

        $('#unit').select2({
            placeholder: 'Select Unit...',
            allowClear: true,
            width: '100%',

            // important for AJAX search
            ajax: {
                url: "{{ route('units.select2') }}", // Laravel route
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term

                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });
        $('#brand').select2({
            placeholder: 'Select Brand...',
            allowClear: true,
            width: '100%',

            // important for AJAX search
            ajax: {
                url: "{{ route('brand.select2') }}", // Laravel route
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term

                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });

        $('#color').select2({
            placeholder: 'Select Color...',
            allowClear: true,
            width: '100%',

            // important for AJAX search
            ajax: {
                url: "{{ route('color.select2') }}", // Laravel route
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results

                    };
                },
                cache: true
            }
        });
        $('#size').select2({
            width: '100%',
            placeholder: 'Select Size...',
            allowClear: true,


            // important for AJAX search
            ajax: {
                url: "{{ route('sizes.select2') }}", // Laravel route
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,

                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });
        $('#paper').select2({
            width: '100%',
            placeholder: 'Select Paper Type...',
            allowClear: true,


            // important for AJAX search
            ajax: {
                url: "{{ route('paper_quality.select2') }}", // Laravel route
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term

                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });

        // Custom fields select2
        $('#product_publish_year').select2({
            placeholder: 'Select Publish Year...',
            allowClear: true,
            width: '100%'
        });

        $('#product_exam_year').select2({
            placeholder: 'Select Exam Year...',
            allowClear: true,
            width: '100%'
        });

        $('#edition').select2({
            placeholder: 'Select or Type Edition...',
            allowClear: true,
            tags: true,
            width: '100%'
        });

        // Event listener for name field
        $('#name').on('input', function() {
            var name = $(this).val();
            var slug = name ? slugify(name,'-') : null; // Generate slug only if name is not empty
            $('#slug').val(slug);
        });

        // $('#product_form').submit(function(e) {
        //     e.preventDefault(); // Prevent the form from submitting normally
        //     var formData = new FormData(this);

        //     // Make AJAX request
        //     $.ajax({
        //         url: '{{ route('product.products.update', $product->id) }}',
        //         method: 'PUT',
        //         data: formData,
        //         processData: false,
        //         contentType: false,
        //         success: function(response) {
        //             Swal.fire({
        //                 icon: 'success',
        //                 title: 'Success!',
        //                 text: response.message,
        //             }).then((result) => {
        //                 if (result.isConfirmed) {
        //                     window.location.href =
        //                         '{{ route('product.products.index') }}';
        //                 }
        //             });
        //         },
        //         error: function(xhr, status, error) {
        //             // Parse the JSON response from the server
        //             try {
        //                 var responseObj = JSON.parse(xhr.responseText);
        //                 var errorMessages = responseObj.errors ? Object.values(responseObj
        //                     .errors).flat() : [responseObj.message];
        //                 var errorMessageHTML = '<ul>' + errorMessages.map(errorMessage =>
        //                     '<li>' + errorMessage + '</li>').join('') + '</ul>';

        //                 // Show error messages using SweetAlert
        //                 Swal.fire({
        //                     icon: 'error',
        //                     title: 'Error!',
        //                     html: errorMessageHTML,
        //                 });
        //             } catch (e) {
        //                 console.error('Error parsing JSON response:', e);
        //                 // Show default error message using SweetAlert
        //                 Swal.fire({
        //                     icon: 'error',
        //                     title: 'Error!',
        //                     text: 'An error occurred while processing your request. Please try again later.',
        //                 });
        //             }
        //         }

        //     });
        // });


        @if ($errors->any())
            $(document).ready(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: `{!! implode('<br>', $errors->all()) !!}`
                });
            });
        @endif

        // @if (!empty($product->image))



        //     var previousImageUrl = "{{ image($product->image) }}"; // Blade variable
        //     if (previousImageUrl) {
        //         $("#preview").append(
        //             '<div class="existing-image-wrapper" style="position:relative; display:inline-block; margin:5px;">' +
        //             '<img src="' + previousImageUrl +
        //             '" style="height:200px; width:auto; border:1px solid #ccc; border-radius:5px;">' +
        //             '<button type="button" class="btn-cancel-old bg-red px-1 py-0 " style="position:absolute; top:5px; right:5px;" data-remove="remove_image">✕</button>' +
        //             '</div>'
        //         );
        //     }
        // @endif


        // $("#image").spartanMultiImagePicker({
        //     fieldName: 'image',
        //     maxCount: 1,
        //     rowHeight: '200px',
        //     groupClassName: 'col',
        //     maxFileSize: '',
        //     dropFileLabel: "Drop Here",
        //     onExtensionErr: function(index, file) {
        //         console.log(index, file, 'extension err');
        //         alert('Please only input png or jpg type file')
        //     },
        //     onSizeErr: function(index, file) {
        //         console.log(index, file, 'file size too big');
        //         alert('File size too big max:2MB');
        //     }
        // });

        @if (!empty($product->thumbnail_image))



            var previousImageUrl = "{{ image($product->thumbnail_image) }}"; // Blade variable
            if (previousImageUrl) {
                $("#thumbnail_image").append(
                    '<div class="existing-image-wrapper" style="position:relative; display:inline-block; margin:5px;">' +
                    '<img src="' + previousImageUrl +
                    '" style="height:200px; width:auto; border:1px solid #ccc; border-radius:5px;">' +
                    '<button type="button" class="btn-cancel-old bg-red px-1 py-0 " style="position:absolute; top:5px; right:5px;" data-remove="remove_thumbnail_image">✕</button>' +
                    '</div>'
                );
            }
        @endif

        $("#thumbnail_image").spartanMultiImagePicker({
            fieldName: 'thumbnail_image',
            maxCount: 1,
            rowHeight: '200px',
            groupClassName: 'col',
            maxFileSize: '',
            dropFileLabel: "Drop Here",
            onExtensionErr: function(index, file) {
                console.log(index, file, 'extension err');
                alert('Please only input png or jpg type file')
            },
            onSizeErr: function(index, file) {
                console.log(index, file, 'file size too big');
                alert('File size too big max:2MB');
            }
        });

        $("#image").spartanMultiImagePicker({
            fieldName: 'image[]',
            maxCount: 3,
            rowHeight: '200px',
            groupClassName: 'col',
            maxFileSize: '',
            dropFileLabel: "Drop Here",
            onExtensionErr: function(index, file) {
                console.log(index, file, 'extension err');
                alert('Please only input png or jpg type file')
            },
            onSizeErr: function(index, file) {
                console.log(index, file, 'file size too big');
                alert('File size too big max:2MB');
            }
        });

        @if (!empty($product->images))

            var existingImages = @json($product->images);
            @foreach (json_decode($product->images) as $image)
                $("#preview").append(
                    '<div class="existing-image-wrapper" style="position:relative; display:inline-block; margin:5px;">' +
                    '<input type="hidden" name="existing_images[]" value="{{ $image }}">' +
                    '<img src="{{ image($image) }}" style="height:200px; width:auto; border:1px solid #ccc; border-radius:5px;">' +
                    '<button type="button" class="btn-cancel-old bg-red px-1 py-0 " style="position:absolute; top:5px; right:5px;">✕</button>' +
                    '</div>'
                );
            @endforeach
        @endif



        @if (!empty($product->size_chart_image))



            var previousImageUrl = "{{ image($product->size_chart_image) }}"; // Blade variable
            if (previousImageUrl) {
                $("#size_chart_image").append(
                    '<div class="existing-image-wrapper" style="position:relative; display:inline-block; margin:5px;">' +
                    '<img src="' + previousImageUrl +
                    '" style="height:200px; width:auto; border:1px solid #ccc; border-radius:5px;">' +
                    '<button type="button" class="btn-cancel-old bg-red px-1 py-0 " style="position:absolute; top:5px; right:5px;" data-remove="remove_size_chart_image">✕</button>' +
                    '</div>'
                );
            }
        @endif

        $("#size_chart_image").spartanMultiImagePicker({
            fieldName: 'size_chart_image',
            maxCount: 1,
            rowHeight: '200px',
            groupClassName: 'col',
            maxFileSize: '',
            dropFileLabel: "Drop Here",
            onExtensionErr: function(index, file) {
                console.log(index, file, 'extension err');
                alert('Please only input png or jpg type file')
            },
            onSizeErr: function(index, file) {
                console.log(index, file, 'file size too big');
                alert('File size too big max:2MB');
            }
        });

        @if (!empty($product->meta_image))
            var previousImageUrl = "{{ image($product->meta_image) }}"; // Blade variable
            if (previousImageUrl) {
                $("#meta_image").append(
                    '<div class="existing-image-wrapper" style="position:relative; display:inline-block; margin:5px;">' +
                    '<img src="' + previousImageUrl +
                    '" style="height:200px; width:auto; border:1px solid #ccc; border-radius:5px;">' +
                    '<button type="button" class="btn-cancel-old bg-red px-1 py-0 " style="position:absolute; top:5px; right:5px;" data-remove="remove_meta_image">✕</button>' +
                    '</div>'
                );
            }
        @endif

        $("#meta_image").spartanMultiImagePicker({
            fieldName: 'meta_image',
            maxCount: 1,
            rowHeight: '200px',
            groupClassName: 'col',
            maxFileSize: '',
            dropFileLabel: "Drop Here",
            onExtensionErr: function(index, file) {
                console.log(index, file, 'extension err');
                alert('Please only input png or jpg type file')
            },
            onSizeErr: function(index, file) {
                console.log(index, file, 'file size too big');
                alert('File size too big max:2MB');
            }
        });

        // Universal handler for canceling old images
        $(document).on('click', '.btn-cancel-old', function() {
            var removeField = $(this).data('remove');
            if (removeField) {
                $('#' + removeField).val(1);
            }
            $(this).closest('.existing-image-wrapper').remove();
        });
    </script>
@endsection
