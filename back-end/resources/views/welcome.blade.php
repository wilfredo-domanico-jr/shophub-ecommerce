<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShopHub API</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            background: #f9fafb;
            color: #1f2937;
        }
        .card {
            text-align: center;
            padding: 3rem 2.5rem;
            max-width: 32rem;
        }
        .badge {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1.5rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.75rem;
            color: white;
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
        }
        h1 {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.75rem;
            margin: 0 0 .5rem;
        }
        .status {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .875rem;
            font-weight: 500;
            color: #16a34a;
            background: #f0fdf4;
            padding: .35rem .8rem;
            border-radius: 9999px;
            margin-bottom: 1.5rem;
        }
        .status::before {
            content: '';
            width: .5rem;
            height: .5rem;
            border-radius: 9999px;
            background: #22c55e;
        }
        p {
            color: #6b7280;
            font-size: .95rem;
            line-height: 1.6;
            margin: 0 0 1.75rem;
        }
        .links {
            display: flex;
            gap: .75rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .links a {
            font-size: .875rem;
            font-weight: 500;
            text-decoration: none;
            padding: .6rem 1.1rem;
            border-radius: .6rem;
            transition: opacity .15s;
        }
        .links a.primary {
            color: white;
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
        }
        .links a.secondary {
            color: #374151;
            background: #f3f4f6;
        }
        .links a:hover { opacity: .9; }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">S</div>
        <h1>ShopHub API</h1>
        <div class="status">Running</div>
        <p>
            This is the backend API for ShopHub — it serves JSON to the storefront
            and admin panel, it isn't meant to be browsed directly.
        </p>
        <div class="links">
            <a class="primary" href="{{ rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/') }}">Go to Storefront</a>
            <a class="secondary" href="{{ url('/up') }}">Health Check</a>
        </div>
    </div>
</body>
</html>
