<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Sistema de gestión médica con inteligencia artificial para evaluación y priorización de solicitudes médicas">
        <meta name="keywords" content="medicina, gestión médica, inteligencia artificial, solicitudes médicas, hospital">
        <meta name="author" content="Vital Red">

        <title inertia>{{ config('app.name', 'Vital Red') }}</title>

        <!-- Favicons -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- Custom CSS for medical theme -->
        <style>
            :root {
                --bs-primary: #0d6efd;
                --bs-secondary: #6c757d;
                --bs-success: #198754;
                --bs-info: #0dcaf0;
                --bs-warning: #ffc107;
                --bs-danger: #dc3545;
                --bs-light: #f8f9fa;
                --bs-dark: #212529;
                --medical-blue: #2c5aa0;
                --medical-green: #28a745;
                --medical-red: #dc3545;
                --medical-orange: #fd7e14;
            }

            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background-color: #f8f9fa;
                color: #212529;
            }

            .navbar-brand {
                font-weight: 600;
                color: var(--medical-blue) !important;
            }

            .btn-primary {
                background-color: var(--medical-blue);
                border-color: var(--medical-blue);
            }

            .btn-primary:hover {
                background-color: #1e3d6f;
                border-color: #1e3d6f;
            }

            .card {
                border: none;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
                border-radius: 0.5rem;
            }

            .card-header {
                background-color: #fff;
                border-bottom: 1px solid #dee2e6;
                font-weight: 600;
            }

            .sidebar {
                background-color: #fff;
                border-right: 1px solid #dee2e6;
                min-height: calc(100vh - 56px);
            }

            .nav-link {
                color: #6c757d;
                font-weight: 500;
            }

            .nav-link:hover,
            .nav-link.active {
                color: var(--medical-blue);
                background-color: rgba(44, 90, 160, 0.1);
            }

            .badge-urgent {
                background-color: var(--medical-red);
            }

            .badge-medium {
                background-color: var(--medical-orange);
            }

            .badge-low {
                background-color: var(--medical-green);
            }

            .table th {
                border-top: none;
                font-weight: 600;
                color: #495057;
            }

            .spinner-border-sm {
                width: 1rem;
                height: 1rem;
            }

            .toast {
                border: none;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }

            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(255, 255, 255, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            }

            .medical-icon {
                color: var(--medical-blue);
            }

            .urgent-case {
                border-left: 4px solid var(--medical-red);
            }

            .medium-case {
                border-left: 4px solid var(--medical-orange);
            }

            .low-case {
                border-left: 4px solid var(--medical-green);
            }

            @media (max-width: 768px) {
                .sidebar {
                    min-height: auto;
                }
            }
        </style>

        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body>
        @inertia

        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

        <!-- jQuery (required for DataTables) -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

        <!-- Moment.js for date formatting -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/es.min.js"></script>

        <!-- Global JavaScript -->
        <script>
            // Set moment locale to Spanish
            moment.locale('es');

            // Global configuration
            window.VitalRed = {
                appName: '{{ config('app.name') }}',
                appUrl: '{{ config('app.url') }}',
                csrfToken: '{{ csrf_token() }}',
                locale: '{{ app()->getLocale() }}',
                user: @json(auth()->user()),
                permissions: @json(auth()->user() ? [
                    'can_manage_users' => auth()->user()->role === 'administrador',
                    'can_evaluate_cases' => in_array(auth()->user()->role, ['medico', 'administrador']),
                    'can_view_reports' => auth()->user()->role === 'administrador',
                    'can_configure_system' => auth()->user()->role === 'administrador',
                ] : [])
            };

            // Global utility functions
            window.formatDate = function(date, format = 'DD/MM/YYYY HH:mm') {
                return moment(date).format(format);
            };

            window.showToast = function(message, type = 'success') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    icon: type,
                    title: message
                });
            };

            window.showConfirm = function(title, text, confirmText = 'Sí, continuar') {
                return Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Cancelar'
                });
            };

            window.showLoading = function(message = 'Procesando...') {
                Swal.fire({
                    title: message,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            };

            window.hideLoading = function() {
                Swal.close();
            };

            // Initialize tooltips
            document.addEventListener('DOMContentLoaded', function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>
    </body>
</html>
