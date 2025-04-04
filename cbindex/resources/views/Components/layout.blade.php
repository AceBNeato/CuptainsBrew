<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cuptain's Brew</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>

    <header class="header">
    <img src="images/LOGO.png" id="logo" alt="cuptainsbrewlogo">

    <!-- "nav-menu" is for mobile
    <button id="menu-toggle" class="hamburger-menu"> ☰  </button> -->

    <nav class="button-container" id="nav-menu">
        <a href="{{ url('/') }}" class="nav-button {{ request()->is('/') ? 'active' : '' }}">Home</a>
        <a href="{{ url('/menu') }}" class="nav-button {{ request()->is('menu') ? 'active' : '' }}">Menu</a>
        <a href="{{ url('/career') }}" class="nav-button {{ request()->is('career') ? 'active' : '' }}">Career</a>
        <a href="{{ url('/about') }}" class="nav-button {{ request()->is('about') ? 'active' : '' }}">About Us</a>
    </nav>
    </header>

     <body>
        @yield('content')
    </body>


</body>
</html>
