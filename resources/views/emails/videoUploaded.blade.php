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
      <img style="width: 40px;height: 40px;border-radius: 100%" src="{{config('app.logo')}}">
      <h3 style="font-weight: 600;color: white;">{{config('app.name')}}</h3>

    </div>
  </nav>
  <br>
  <div style="display: flex;justify-content: left;align-items: center;width: 90vw;margin: auto;font-size: 4vw">
    <h3>{{$channel_name}} uploaded a new video!!</h2>

  </div>

  <br>
  <div  style="margin: auto;width: 80vw;box-shadow: 3px 3px 3px 3px rgba(0,0,0,0.5);background-color: whitesmoke;display: flex;flex-direction: column;padding: 0 5vw;">

    <div style="width:100%;display: flex;flex-direction: row;justify-content: left;align-items: center;font-size: 4vw">
      <div style="gap: 5%;width: 50%;display: flex;flex-direction: row;justify-content: left;align-items: center;">
        <img style="border-radius: 100%;width: 10vw;height: 5vh; border: 1px solid black;object-fit:cover" src="{{$channel_logo_url}}">
        <h3>{{$channel_name}}</h3>
      </div>
      <div style="width: 50%;display: flex;justify-content: right;align-items: center;">
        <a  style="text-decoration: none;padding: 2vw 4vw;background-color: rgba(0,0,0,0.7);color: white;border-radius: 2vw;font-size: 3vw;" href="{{$link}}">Watch now</a>
      </div>
    </div>


    
    

    <div style="width: 100%;display:flex;flex-direction: column;justify-content: center; align-items: center;gap: 5%;overflow-wrap: break-word;">
      <a style="display: flex;align-items: center;justify-content: center;" href="{{$link}}"><img style="width:90%;height:10rem" src="{{$video_thumbnail_url}}" />
      </a>
      <p style="font-weight: 500;width: 80vw;">{{$video_title}}</p>
      <p style="font-weight: 300;width: 80vw;">{{$video_description}}</p>
    </div>
  </div>

  <br>

  <footer style="width: 100vw;height:30px;display:flex;justify-content:center;align-items: center;background-color: rgba(0,0,0,0.7);position: absolute;bottom: 0px;color: white">
    &copy; 2023 {{config('app.name')}} . All Rights Reserved.
  </footer>
</body>

</html>