<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description">
    <meta name="author" >
    <meta name="robots" content="noindex, nofollow">
    <link rel="shortcut icon" type="image/x-icon" >
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('meta')

    {{-- Core CSS --}}
    <!-- Remix Icon Font CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/remixicon.css') }}">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/bootstrap.min.css') }}">
    <!-- Apex Chart CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/apexcharts.css') }}">
    <!-- Data Table CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/dataTables.min.css') }}">
    <!-- Text Editor CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/editor-katex.min.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/editor.atom-one-dark.min.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/editor.quill.snow.css') }}">
    <!-- Date Picker CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/flatpickr.min.css') }}">
    <!-- Calendar CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/full-calendar.css') }}">
    <!-- Vector Map CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/jquery-jvectormap-2.0.5.css') }}">
    <!-- Popup CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/magnific-popup.css') }}">
    <!-- Slick Slider CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/slick.css') }}">
    <!-- Prism CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/prism.css') }}">
    <!-- File Upload CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/file-upload.css') }}">
    <!-- Audio Player CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/lib/audioplayer.css') }}">
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('theme/admin/assets/css/style.css') }}?id={{rand(121,122233)}}">

    {{-- ajax data table css --}}
    {{-- <link rel="stylesheet" href="{{asset('theme/admin/assets/css/lib/ajaxdatatable/dataTables.bootstrap5.min.css')}}"> --}}

        {{-- select tow js --}}
    <link rel="stylesheet" href="{{asset('theme/admin/assets/js/lib/select2/css/select2.min.css')}}" ></link>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"/>

    {{-- Fix: long select2 names overflowing ajax modals (product distribution / assign specimen / spot sale item tables) --}}
    <style>
        #AjaxModal .select2-container {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            display: block !important;
            height: auto !important;
        }
        #AjaxModal .select2-container .selection {
            display: block !important;
            width: 100% !important;
            height: auto !important;
        }
        #AjaxModal .select2-selection--single .select2-selection__rendered {
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }
        #AjaxModal table.items-table-fixed { table-layout: fixed; width: 100%; }
        #AjaxModal table.items-table-fixed th,
        #AjaxModal table.items-table-fixed td { min-width: 0; overflow: hidden; }
        #AjaxModal table.items-table-fixed .select2-container { width: 100% !important; }

        /* === Modern Select2 Multiple Styling to match Single Select === */
        .select2-container--default .select2-selection--multiple {
            min-height: 44px !important;
            height: auto !important;
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            padding: 4px 48px 4px 10px !important;
            background-color: #ffffff !important;
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            cursor: pointer !important;
            overflow: visible !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
        }
        .select2-container--default .select2-selection--multiple::after {
            content: "";
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 5px solid #6b7280;
            pointer-events: none;
        }
        .select2-container--default.select2-container--open .select2-selection--multiple::after {
            border-top: none;
            border-bottom: 5px solid #6b7280;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__clear {
            position: absolute !important;
            right: 26px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            margin: 0 !important;
            padding: 0 4px !important;
            font-size: 18px !important;
            font-weight: 600 !important;
            line-height: 1 !important;
            color: #9ca3af !important;
            cursor: pointer !important;
            z-index: 2 !important;
            transition: color 0.15s ease !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__clear:hover {
            color: #dc2626 !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple,
        .select2-container--default.select2-container--open .select2-selection--multiple {
            border-color: #8c57ff !important;
            box-shadow: 0 0 0 3px rgba(140, 87, 255, 0.15) !important;
            outline: 0 !important;
        }
        .select2-container--default.select2-container--disabled .select2-selection--multiple {
            background-color: #f3f4f6 !important;
            border-color: #e5e7eb !important;
            cursor: not-allowed !important;
            opacity: 0.85;
        }
        .select2-container--default.select2-container--disabled .select2-selection--multiple::after {
            border-top-color: #9ca3af !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: 4px !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            min-height: 34px !important;
            height: auto !important;
            white-space: normal !important;
            overflow: visible !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #f3e8ff !important;
            border: 1px solid #d8b4fe !important;
            color: #6b21a8 !important;
            border-radius: 6px !important;
            padding: 3px 10px 3px 24px !important;
            margin: 2px 0 !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            position: relative !important;
            display: inline-flex !important;
            align-items: center !important;
            line-height: 1.4 !important;
            max-width: 100% !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            position: absolute !important;
            left: 6px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            border: none !important;
            background: transparent !important;
            color: #9333ea !important;
            font-size: 13px !important;
            font-weight: bold !important;
            cursor: pointer !important;
            padding: 0 !important;
            line-height: 1 !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #7e22ce !important;
            background: transparent !important;
        }
        .select2-container--default .select2-selection--multiple .select2-search--inline {
            display: inline-flex !important;
            align-items: center !important;
            margin: 2px 0 !important;
            flex-grow: 1 !important;
        }
        .select2-container--default .select2-selection--multiple .select2-search__field {
            margin: 0 !important;
            height: 28px !important;
            line-height: 28px !important;
            font-size: 14px !important;
            color: #374151 !important;
            font-family: inherit !important;
            min-width: 80px !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
        }
        .select2-container--default .select2-selection--multiple .select2-search__field::placeholder {
            color: #9ca3af !important;
            font-size: 14px !important;
        }
        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            overflow: hidden !important;
            z-index: 99999 !important;
        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #8c57ff !important;
            color: #ffffff !important;
        }
        .select2-container--default .select2-results__option--selected {
            background-color: #f3e8ff !important;
            color: #6b21a8 !important;
        }
        .select2-results__option {
            padding: 8px 12px !important;
            font-size: 14px !important;
        }
    </style>

</head>
