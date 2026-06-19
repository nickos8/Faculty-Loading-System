<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .form-grid3 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr 1fr 1fr 1fr; /* 2 columns on desktop */
    }

    .full-width {
        grid-column: span 2; /* full width fields */
    }
}

<style>
            .form-grid2 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr 1fr 1fr 1fr; /* 2 columns on desktop */
    }

    .full-width {
        grid-column: span 2; /* full width fields */
    }
}


            :root {
                --indigo-dye: #133c55ff;
                --bice-blue: #386fa4ff;
                --picton-blue: #59a5d8ff;
                --pale-azure: #84d2f6ff;
                --non-photo-blue: #91e5f6ff;
            }

            body {
                background: linear-gradient(135deg, var(--indigo-dye), var(--picton-blue), var(--non-photo-blue));
            }

            /* Smooth input highlight */
            input, select, textarea {
                transition: all 0.35s ease-in-out;
            }

            input:hover, select:hover, textarea:hover {
                background-color: var(--pale-azure);
                border-color: var(--bice-blue);
                box-shadow: 0 0 12px rgba(56, 111, 164, 0.35);
            }

            input:focus, select:focus, textarea:focus {
                background-color: white;
                border-color: var(--picton-blue);
                box-shadow: 0 0 15px rgba(89, 165, 216, 0.6);
                transform: scale(1.02);
            }

            .form-container {
                background: white;
                border-radius: 1rem;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                padding: 2rem;
                width: 100%;
                max-width: 450px;
            }

            .form-title {
                color: var(--indigo-dye);
            }

            .form-subtitle {
                color: var(--bice-blue);
            }

            button {
                background: var(--indigo-dye);
                color: white;
                transition: all 0.3s ease;
            }

            button:hover {
                background: var(--bice-blue);
                transform: translateY(-2px);
                box-shadow: 0 6px 15px rgba(19, 60, 85, 0.3);
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex items-center justify-center px-4">

            <!-- Responsive form card -->
            <div class="form-container">

                <!-- Logo + Text -->
                <div class="flex flex-col items-center mb-6 text-center">
                    <img src="{{ asset('images/granbylogo.jpg') }}" alt="Granby Colleges Logo"
                         class="w-20 h-20 rounded-xl shadow-md object-cover mb-3">

                    <p class="text-2xl font-extrabold tracking-tight form-title">
                        Granby Colleges
                    </p>
                    <p class="text-sm font-medium form-subtitle">
                        Faculty Loading & Scheduling System
                    </p>
                </div>

                <!-- Form slot -->
                <div>
                    {{ $slot }}
                </div>
            </div>

        </div>
    </body>
</html>
