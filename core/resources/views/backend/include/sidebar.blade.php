<aside class="sidebar">
    <button type="button" class="sidebar-close-btn">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        @php
            // dd($companySettings);
            $logoUrl = optional($companySettings)->logo
                ? image($companySettings->logo)
                : asset('theme/admin/assets/images/logo1.png');
        @endphp
        <a href="{{ route('backend.dashboard') }}" class="sidebar-logo">
            <img src="{{ $logoUrl }}" alt="site logo" class="light-logo img-fluid">
            <img src="{{ $logoUrl }}" alt="site logo" class="dark-logo ">
            <img src="{{ $logoUrl }}" alt="site logo" class="logo-icon">
        </a>

    </div>
    <div class="sidebar-menu-area">
        <ul class="sidebar-menu" id="sidebar-menu">
            <li class="dropdown">
                <a href="javascript:void(0)">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="menu-icon"></iconify-icon>
                    <span>Dashboard</span>
                </a>
                <ul class="sidebar-submenu">

                    <li>
                        <a href="{{ route('backend.dashboard') }}">
                            <i class="ri-circle-fill circle-icon text-warning-main w-auto"></i> POS (Admin)
                        </a>
                    </li>


                </ul>
            </li>
            {{-- user management module start --}}

            @permgroup(['usermanage', 'rbac', 'security'])

                <li class="sidebar-menu-group-title">User Management</li>

                {{-- Dropdown parent --}}
                <li
                    class="dropdown {{ Route::is('usermanage.users.*') || Route::is('rbac.*') || Route::is('security.firewall.*') ? 'active' : '' }}">
                    <a href="javascript:void(0)" class="-toggle d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
                            <span>User</span>
                        </div>
                    </a>

                    {{-- Submenu items --}}
                    <ul class="sidebar-submenu">

                        {{-- Users --}}
                        @perm('usermanage.users.index')
                            <li class="{{ Route::is('usermanage.users.index') ? 'active' : '' }}">
                                <a href="{{ route('usermanage.users.index') }}">
                                    <iconify-icon icon="flowbite:users-outline" class="menu-icon"></iconify-icon>
                                    <span>Users</span>
                                </a>
                            </li>
                        @endperm

                        {{-- Roles --}}
                        @perm('rbac.role.list')
                            <li class="{{ Route::is('rbac.role.list') ? 'active' : '' }}">
                                <a href="{{ route('rbac.role.list') }}">
                                    <iconify-icon icon="mdi:shield-account-outline" class="menu-icon"></iconify-icon>
                                    <span>Roles</span>
                                </a>
                            </li>
                        @endperm

                        {{-- Permissions --}}
                        @perm('rbac.permissions.index')
                            <li class="{{ Route::is('rbac.permissions.index') ? 'active' : '' }}">
                                <a href="{{ route('rbac.permissions.index') }}">
                                    <iconify-icon icon="mdi:lock-check-outline" class="menu-icon"></iconify-icon>
                                    <span>Permissions</span>
                                </a>
                            </li>
                        @endperm

                        {{-- Firewall --}}
                        @perm('security.firewall.index')
                            <li class="{{ Route::is('security.firewall.index') ? 'active' : '' }}">
                                <a href="{{ route('security.firewall.index') }}">
                                    <iconify-icon icon="mdi:shield-outline" class="menu-icon"></iconify-icon>
                                    <span>Firewall</span>
                                </a>
                            </li>
                        @endperm

                    </ul>
                </li>
            @endpermgroup


            {{-- branch management module start --}}
            @permgroup(['org.branches.index'])
                <li class="sidebar-menu-group-title">Branch Management</li>
                @permgroup(['org'])
                    @perm('org.branches.index')
                        <li class=" dropdown ">
                            <a href="javascript:void(0)">
                                <i class="ri-store-3-line"></i>
                                <span>Branch</span>
                            </a>

                            <ul class="sidebar-submenu">

                                @perm('org.branches.index')
                                    <li>
                                        <a class="{{ Route::is('org.branches.index') ? 'active' : '' }}"
                                            href="{{ route('org.branches.index') }}">
                                            <iconify-icon icon="mdi:store-outline" class="menu-icon"></iconify-icon>
                                            <span>Branch List</span>
                                        </a>
                                    </li>
                                @endperm
                            </ul>
                        </li>
                    @endperm
                @endpermgroup
            @endpermgroup

            {{-- branch management module end --}}

            {{-- Department module start --}}
            @permgroup(['department.index'])
                <li class="sidebar-menu-group-title">Department Management</li>
                @permgroup(['org'])
                    @perm('department.index')
                        <li class=" dropdown ">
                            <a href="javascript:void(0)">
                                <i class="ri-building-2-line"></i>
                                <span>Department</span>
                            </a>

                            <ul class="sidebar-submenu">

                                @perm('department.index')
                                    <li>
                                        <a class="{{ Route::is('department.index') ? 'active' : '' }}"
                                            href="{{ route('department.index') }}">
                                            <iconify-icon icon="mdi:office-building-outline" class="menu-icon"></iconify-icon>
                                            <span>Department List</span>
                                        </a>
                                    </li>
                                @endperm

                                @perm('class.class.index')
                                    <li class="mb-8">
                                        <a class="{{ Route::is('class.class.index') ? 'active' : '' }}"
                                            href="{{ route('class.class.index') }}">
                                            <iconify-icon icon="ri-book-2-line" class="menu-icon"></iconify-icon>
                                            <span>Class List</span>
                                        </a>
                                    </li>
                                @endperm

                                @perm('institution.institutions.index')
                                    <li class="mb-8">
                                        <a class="{{ Route::is('institution.institutions.index') ? 'active' : '' }}"
                                            href="{{ route('institution.institutions.index') }}">
                                            <iconify-icon icon="ri:building-line" class="menu-icon"></iconify-icon>
                                            <span>Institution</span>
                                        </a>
                                    </li>
                                @endperm

                                @perm('teacher.teachers.index')
                                    <li class="mb-8">
                                        <a class="{{ Route::is('teacher.teachers.index') ? 'active' : '' }}"
                                            href="{{ route('teacher.teachers.index') }}">
                                            <iconify-icon icon="ri:graduation-cap-line" class="menu-icon"></iconify-icon>
                                            <span>Teacher</span>
                                        </a>
                                    </li>
                                @endperm

                                 @perm('institution.institutions.index')
                                    <li>
                                        <a class="{{ Route::is('library.libraries.index') ? 'active' : '' }}"
                                            href="{{ route('library.libraries.index') }}">
                                            <iconify-icon icon="mdi:library-outline" class="menu-icon"></iconify-icon>
                                            <span>Library List</span>
                                        </a>
                                    </li>
                                @endperm

                                 @perm('subject.index')
                                    <li>
                                        <a class="{{ Route::is('subject.index') ? 'active' : '' }}"
                                            href="{{ route('subject.index') }}">
                                            <iconify-icon icon="mdi:book-outline" class="menu-icon"></iconify-icon>
                                            <span>Subject List</span>
                                        </a>
                                    </li>
                                @endperm


                            </ul>
                        </li>
                    @endperm
                @endpermgroup
            @endpermgroup
            {{-- Contacts module start --}}
            {{-- contacts module end --}}

            {{-- Department module start --}}
            @permgroup(['department.index'])
                <li class="sidebar-menu-group-title">Representative Management</li>
                @permgroup(['org'])
                    @perm('marketing-representative.marketing-representatives.index')
                        <li class=" dropdown ">
                            <a href="javascript:void(0)">
                                <i class="ri-building-2-line"></i>
                                <span>Representative</span>
                            </a>

                            <ul class="sidebar-submenu">

                                @perm('marketing-representative.marketing-representatives.index')
                                    <li>
                                        <a class="{{ Route::is('marketing-representative.marketing-representatives.index') ? 'active' : '' }}"
                                            href="{{ route('marketing-representative.marketing-representatives.index') }}">
                                            <iconify-icon icon="mdi:account-tie-outline" class="menu-icon"></iconify-icon>
                                            <span>Representative List</span>
                                        </a>
                                    </li>
                                @endperm
                                @perm('locations.map')
                                    <li>
                                        <a class="{{ Route::is('locations.map') ? 'active' : '' }}"
                                            href="{{ route('locations.map') }}">
                                            <iconify-icon icon="solar:map-point-wave-bold-duotone" class="menu-icon"></iconify-icon>
                                            <span>MR Map List</span>
                                        </a>
                                    </li>
                                @endperm
                                @perm('product-distribution.index')
                                    <li>
                                        <a class="{{ Route::is('product-distribution.index') ? 'active' : '' }}"
                                            href="{{ route('product-distribution.index') }}">
                                            <iconify-icon icon="mdi:package-variant-closed" class="menu-icon"></iconify-icon>
                                            <span>Distribution List</span>
                                        </a>
                                    </li>
                                @endperm
                                @perm('daily-visit.index')
                                    <li>
                                        <a class="{{ Route::is('daily-visit.index') ? 'active' : '' }}"
                                            href="{{ route('daily-visit.index') }}">
                                            <iconify-icon icon="mdi:calendar-check-outline" class="menu-icon"></iconify-icon>
                                            <span>Daily Visit List</span>
                                        </a>
                                    </li>
                                @endperm
                                @perm('assign-specimen.index')
                                    <li>
                                        <a class="{{ Route::is('assign-specimen.index') ? 'active' : '' }}"
                                            href="{{ route('assign-specimen.index') }}">
                                            <iconify-icon icon="mdi:flask-outline" class="menu-icon"></iconify-icon>
                                            <span>Assign Specimen</span>
                                        </a>
                                    </li>
                                @endperm
                            </ul>
                        </li>
                    @endperm
                @endpermgroup
            @endpermgroup

            @permgroup(['fund-request.index', 'fund-distribution.index'])
                <li class="sidebar-menu-group-title">Fund Management</li>
                <li class="dropdown {{ Route::is('fund-request.*') || Route::is('fund-distribution.*') ? 'active' : '' }}">
                    <a href="javascript:void(0)">
                        <iconify-icon icon="mdi:cash-multiple" class="menu-icon"></iconify-icon>
                        <span>Fund Management</span>
                    </a>

                    <ul class="sidebar-submenu">
                        @perm('fund-request.index')
                            <li>
                                <a class="{{ Route::is('fund-request.*') ? 'active' : '' }}"
                                    href="{{ route('fund-request.index') }}">
                                    <iconify-icon icon="mdi:cash-plus" class="menu-icon"></iconify-icon>
                                    <span>Fund Request</span>
                                </a>
                            </li>
                        @endperm
                        @perm('fund-distribution.index')
                            <li>
                                <a class="{{ Route::is('fund-distribution.*') ? 'active' : '' }}"
                                    href="{{ route('fund-distribution.index') }}">
                                    <iconify-icon icon="mdi:bank-transfer" class="menu-icon"></iconify-icon>
                                    <span>Fund Transfer</span>
                                </a>
                            </li>
                        @endperm
                    </ul>
                </li>
            @endpermgroup


<li class="sidebar-menu-group-title">Book Management</li>

            <li class="dropdown">
                <a href="javascript:void(0)">
                    <i class="ri-book-2-line"></i>
                    <span>Book</span>
                </a>

                <ul class="sidebar-submenu">

                    <li>
                        <a href="http://localhost/field-marketing/book-request/book-requests">
                            <iconify-icon icon="mdi:book-arrow-right-outline" class="menu-icon"></iconify-icon>
                            <span>Book Requests</span>
                        </a>
                    </li>

                     <li class="{{ Route::is('book-return.book-returns.index') ? 'active' : '' }}">
                        <a href="{{ route('book-return.book-returns.index') }}">
                            <iconify-icon icon="mdi:book-arrow-left-outline" class="menu-icon"></iconify-icon>
                            <span>Book Returns</span>
                        </a>
                    </li>


                </ul>
            </li>

        {{-- Payments & Expenses parent --}}
                @permgroup(['expenseCategories', 'expenses'])
                    <li
                        class="dropdown {{ Route::is('expenseCategories.index') || Route::is('expenses.index') ? 'active' : '' }}">
                        <a href="javascript:void(0)">
                            <iconify-icon icon="mdi:credit-card-outline" class="menu-icon"></iconify-icon>
                            <span>Expenses</span>
                        </a>

                        <ul class="sidebar-submenu">
                           

                            @perm('expenseCategories.index')
                                <li>
                                    <a class="{{ Route::is('expenseCategories.index') ? 'active' : '' }}"
                                        href="{{ route('expenseCategories.index') }}">
                                        <iconify-icon icon="mdi:finance" class="menu-icon"></iconify-icon>
                                        <span>Expense Categories</span>
                                    </a>
                                </li>
                            @endperm

                            @perm('expenses.index')
                                <li>
                                    <a class="{{ Route::is('expenses.index') ? 'active' : '' }}"
                                        href="{{ route('expenses.index') }}">
                                        <iconify-icon icon="mdi:cash-minus" class="menu-icon"></iconify-icon>
                                        <span>Expenses</span>
                                    </a>
                                </li>
                            @endperm
                        </ul>
                    </li>
                @endpermgroup

            {{-- sale management module start --}}

            @perm('spot-sales.index')
                <li>
                    <a class="{{ Route::is('spot-sales.index') ? 'active' : '' }}"
                        href="{{ route('spot-sales.index') }}">
                        <iconify-icon icon="mdi:point-of-sale" class="menu-icon"></iconify-icon>
                        <span>Spot Sales</span>
                    </a>
                </li>
            @endperm

            {{-- item managemnt module start --}}


            @permgroup([
                'product',
                'category-type',
                'category',
                'subcategory',
                'brand',
                'color',
                'units',
                'product-type',
                'paper_quality',
                'sizes'
            ])
                <li class="sidebar-menu-group-title">Item Management</li>

                <li class="dropdown {{ Route::is('product.products.index') ? 'active' : '' }}">
                    <a href="javascript:void(0)">
                        <iconify-icon icon="mdi:package-variant-closed" class="menu-icon"></iconify-icon>
                        <span>Product</span>
                    </a>

                    <ul class="sidebar-submenu">

                        @perm('product.products.index')
                            <li class="{{ Route::is('product.products.index') ? 'active' : '' }}">
                                <a href="{{ route('product.products.index') }}">
                                    <i class="ri-shopping-bag-3-line text-xl me-14 d-flex w-auto"></i>
                                    <span>All Products</span>
                                </a>
                            </li>
                        @endperm

                        @permgroup(['category-type', 'category', 'subcategory'])
                            <li class="dropdown">
                                <a href="javascript:void(0)">
                                    <iconify-icon icon="mdi:label-outline" class="menu-icon"></iconify-icon>
                                    <span>Categories</span>
                                </a>
                                <ul class="sidebar-submenu">
                                    {{-- @perm('category-type')
                                        <li class="mb-8">
                                            <a class="{{ Route::is('category-type.category-types.index') ? 'active' : '' }}"
                                                href="{{ route('category-type.category-types.index') }}">

                                                <iconify-icon icon="mdi:label-outline" class="menu-icon"></iconify-icon>
                                                <span>Category Type</span>
                                            </a>
                                        </li>
                                    @endperm --}}
                                    @perm('category')
                                        <li class="mb-8">
                                            <a class="{{ Route::is('category.categories.index') ? 'active' : '' }}"
                                                href="{{ route('category.categories.index') }}">

                                                <iconify-icon icon="mdi:folder-outline" class="menu-icon"></iconify-icon>
                                                <span>Category List</span>
                                            </a>
                                        </li>
                                    @endperm
                                    {{-- @perm('subcategory')
                                        <li class="mb-8">
                                            <a class="{{ Route::is('subcategory.subcategories.index') ? 'active' : '' }}"
                                                href="{{ route('subcategory.subcategories.index') }}">

                                                <iconify-icon icon="mdi:folder-multiple-outline" class="menu-icon"></iconify-icon>
                                                <span>Subcategory List</span>
                                            </a>
                                        </li>
                                    @endperm --}}
                                </ul>
                            </li>
                        @endpermgroup
                        {{-- @perm('color.colors.index')
                            <li class="{{ Route::is('color.colors.index') ? 'active' : '' }}">
                                <a href="{{ route('color.colors.index') }}">
                                    <i class="ri-palette-line"></i>
                                    <span>Color</span>
                                </a>
                            </li>
                        @endperm
                        @perm('product.products.barcode')
                            <li class="{{ Route::is('product.products.barcode') ? 'active' : '' }}">
                                <a href="{{ route('product.products.barcode') }}">
                                    <i class="ri-barcode-line"></i>
                                    <span>Barcode / Label Print</span>
                                </a>
                            </li>
                        @endperm
                        @perm('sizes.index')
                            <li class="{{ Route::is('sizes.index') ? 'active' : '' }}">
                                <a href="{{ route('sizes.index') }}">
                                    <i class="ri-ruler-line text-xl me-14 d-flex w-auto"></i>
                                    <span>Sizes</span>
                                </a>
                            </li>
                        @endperm
                         @perm('paper_quality.index')
                            <li class="{{ Route::is('paper_quality.index') ? 'active' : '' }}">
                                <a href="{{ route('paper_quality.index') }}">
                                    <i class="ri-book-open-line text-xl me-14 d-flex w-auto"></i>
                                    <span>Paper Quality</span>
                                </a>
                            </li>
                        @endperm --}}
                        @perm('brand.brands.index')
                            <li class="mb-8">
                                <a class="{{ Route::is('brand.brands.index') ? 'active' : '' }}"
                                    href="{{ route('brand.brands.index') }}">
                                    <iconify-icon icon="ri-price-tag-3-line" class="menu-icon"></iconify-icon>
                                    <span>Brand List</span>
                                </a>
                            </li>
                        @endperm
                </li>

                {{-- @perm('units.index')
                    <li class="{{ Route::is('units.index') ? 'active' : '' }}">
                        <a href="{{ route('units.index') }}">
                            <i class="ri-weight-line"></i>
                            <span>Unit</span>
                        </a>
                    </li>
                @endperm --}}

                {{-- @perm('product-type.product-types.index')
                    <li class="{{ Route::is('product-type.product-types.index') ? 'active' : '' }}">
                        <a href="{{ route('product-type.product-types.index') }}">
                            <i class="ri-function-line text-xl me-14 d-flex w-auto"></i>
                            <span>Product Type</span>
                        </a>
                    </li>
                @endperm --}}
            </ul>
            </li>

        @endpermgroup
        {{-- item managemnt module end --}}

        {{-- stock management module (old commented duplicate removed) --}}



        {{-- inventory module start --}}



        {{-- Location managemnt module start --}}

        @permgroup(['country', 'division', 'district', 'upazila'])
            <li class="sidebar-menu-group-title">Location Management</li>
            @permgroup(['country', 'division', 'district', 'upazila'])
                <li class=" dropdown ">
                    <a href="javascript:void(0)">
                        <i class="ri-map-pin-line"></i>
                        <span>Location</span>
                    </a>

                    <ul class="sidebar-submenu">

                        @perm('country.countries.index')
                            <li class="mb-8">
                                <a class="{{ Route::is('country.countries.index') ? 'active' : '' }}"
                                    href="{{ route('country.countries.index') }}">
                                    <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
                                    <span>Country List</span>
                                </a>
                            </li>
                        @endperm
                        @perm('division.divisions.index')
                            <li class="mb-8">
                                <a class="{{ Route::is('division.divisions.index') ? 'active' : '' }}"
                                    href="{{ route('division.divisions.index') }}">
                                    <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
                                    <span>Division List</span>
                                </a>
                            </li>
                        @endperm
                        @perm('district.districts.index')
                            <li class="mb-8">
                                <a class="{{ Route::is('district.districts.index') ? 'active' : '' }}"
                                    href="{{ route('district.districts.index') }}">
                                    <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
                                    <span>District List</span>
                                </a>
                            </li>
                        @endperm
                        @perm('upazila.upazilas.index')
                            <li class="mb-8">
                                <a class="{{ Route::is('upazila.upazilas.index') ? 'active' : '' }}"
                                    href="{{ route('upazila.upazilas.index') }}">
                                    <iconify-icon icon="flowbite:users-group-outline" class="menu-icon"></iconify-icon>
                                    <span>Upazila List</span>
                                </a>
                            </li>
                        @endperm

                    </ul>
                </li>
            @endpermgroup
        @endpermgroup

        {{-- location managemnt module end --}}


        {{-- organization management module start --}}
        @permgroup(['org.btypes', 'website-setting'])
            <li class="sidebar-menu-group-title">Organization</li>

            @perm('website-setting.website-settings.index')
                <li>
                    <a class="{{ Route::is('website-setting.website-settings.index') ? 'active' : '' }}"
                        href="{{ route('website-setting.website-settings.index') }}">
                        <iconify-icon icon="mdi:cog" class="menu-icon"></iconify-icon> <span>Website Setting</span>
                    </a>
                </li>
            @endperm
            </li>

        @endpermgroup
        {{-- organization management module end --}}


        </ul>


    </div>
</aside>
