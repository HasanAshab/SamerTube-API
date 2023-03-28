<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin: 0; padding: 0;">
  <nav style="width: 100vw;height:50px;display:flex;justify-content:center;align-items: center;background-color: rgba(0,0,0,0.7)">
    <div style="display:flex;flex-direction: row;justify-content: center; align-items: center;gap: 5px">
      <img style="width: 30px;border-radius: 100%" src="{{config('app.logo')}}">
      <h3 style="font-weight: 600;color: white;">{{config('app.name')}}</h3>
    </div>
  </nav>
  @yield('content')
  <div style="display: flex;flex-direction: row;justify-content: left;align-items: center;width:90%;padding: 2vw 1vw;">
    <p style="font-size: 3vw">
      You can change your notification settings from <a style="text-decoration: none" href="#">here.</a>
    </p>
  </div>
  <div style="display: flex;flex-direction: row;justify-content: left;align-items: center;width:90%;padding: 2vw 1vw;">
    <p style="font-size: 2.5vw;">
      if you no longer wish to recive emails about new subscriber, you can <a style="text-decoration: none" href="#">Unsubscribe</a>
    </p>
  </div>
  <footer style="width: 100vw;height:30px;display:flex;justify-content:center;align-items: center;background-color: rgba(0,0,0,0.7);position: absolute;bottom: 0px;color: white">
    &copy; 2023 {{config('app.name')}} . All Rights Reserved.
  </footer>
</body>
</html>