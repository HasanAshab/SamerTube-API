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
  <div style="width: 100vw;display:flex;flex-direction: column;justify-content: center; align-items: center;margin-top: 10px">

    <h3 style="padding: 5px;">{{$commenter_name}} commented on your video</h3>

    <a style="text-decoration: none; color: black" href="{{$video_link}}">
      <div style="display:flex;flex-direction: row;justify-content: center; align-items: center;gap: 10px;padding: 0 2vw;">
      <img style="width:70px;height:40px" src="{{$video_thumbnail_url}}" />
      <p style="padding: 10px;">{{$video_title}}</p>
    </div>
  </a>

  <div style="width: 90vw;border: .1px solid black;display: flex;flex-direction: row; padding: 2vw 2vw;gap: 5px;">

    <a href="{{$commenter_channel_page_link}}">
      <div style="display: flex; justify-content: center;align-items: center">
        <img style="border-radius: 100%;width: 40px" src="{{$commenter_logo_url}}">
      </div>
    </a>


    <div style="display: flex;flex-direction: column;gap: 10px">

      <div style="display: flex;flex-direction: column;gap: 5px">
        <strong>{{$commenter_name}}</strong>
        <span>{{$text}}</span>
      </div>


      <div style="display: flex;justify-content: left;flex-direction: row;gap: 10px">
        <a style="text-decoration: none;padding: 1vw 2vw;background-color: rgba(0,0,0,0.7);color: white;font-size: 2vw;border-radius: 3vw;" href="{{$reply_page_link}}">Reply</a>
        <a style="text-decoration: none;padding: 1vw 2vw;background-color: rgba(0,0,0,0.7);color: white;font-size: 2vw;border-radius: 3vw;" href="{{$manage_comments_page_link}}">Manage all comments</a>
      </div>
    </div>


  </div>

</div>

<footer style="width: 100vw;height:30px;display:flex;justify-content:center;align-items: center;background-color: rgba(0,0,0,0.7);position: absolute;bottom: 0px;color: white">
  &copy; 2023 {{config('app.name')}} . All Rights Reserved.
</footer>


</body>
</html>