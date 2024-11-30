<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>RapidCents API Transactions</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

        <!-- Styles -->
        <style>
            :root {
                --primary-color: #0077b6;
                --secondary-color: #00bfff;
                --text-color: #333;
                --bg-color: #f5f5f5;
                --card-bg: #fff;
                --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            body {
                font-family: 'Poppins', sans-serif;
                color: var(--text-color);
                background-color: var(--bg-color);
                margin: 0;
                padding: 0;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem;
            }

            .card {
                background-color: var(--card-bg);
                border-radius: 8px;
                box-shadow: var(--card-shadow);
                padding: 2rem;
                margin-bottom: 2rem;
            }

            .card h2 {
                color: var(--primary-color);
                margin-top: 0;
            }

            .card p {
                line-height: 1.6;
            }

            .cta-button {
                display: inline-block;
                background-color: var(--primary-color);
                color: #fff;
                text-decoration: none;
                padding: 1rem 2rem;
                border-radius: 4px;
                transition: background-color 0.3s ease;
            }

            .cta-button:hover {
                background-color: var(--secondary-color);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <header>
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <img src="logo.svg" alt="RapidCents" class="h-8 mr-4">
                        <h1 class="text-2xl font-bold">RapidCents</h1>
                    </div>
                    @if (Route::has('login'))
                        <div>
                            @auth
                                <a href="{{ url('/dashboard') }}" class="text-gray-600 hover:text-gray-900 font-medium">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 font-medium mr-4">Log in</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="text-gray-600 hover:text-gray-900 font-medium">Register</a>
                                @endif
                            @endauth
                        </div>
                    @endif
                </div>
            </header>

            <main>
                <div class="card">
                    <h2>API Documentation</h2>
                    <p>Access our comprehensive API documentation to integrate RapidCents payment solutions into your applications.</p>
                    <a href="#" class="cta-button">Explore API</a>
                </div>

                <div class="card">
                    <h2>Transaction Dashboard</h2>
                    <p>Monitor and manage your transactions in real-time through our intuitive dashboard interface.</p>
                    <a href="#" class="cta-button">View Dashboard</a>
                </div>
            </main>

            <footer>
                <div class="text-center text-gray-500 text-sm">
                    RapidCents API Transactions v1.0
                </div>
            </footer>
        </div>
    </body>
</html>
