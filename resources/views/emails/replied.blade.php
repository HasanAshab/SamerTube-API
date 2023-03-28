@extends('emails.layout')
@section('content')
  @if($type === 'video')
  <div style="width: 100vw;display:flex;flex-direction: column;justify-content: center; align-items: center;margin-top: 10px">

    <h3 style="padding: 5px;font-size: 5vw">{{$replier_name}} replied to your comment</h3>

    <a style="text-decoration: none; color: black" href="{{$video_link}}">
      <div style="display:flex;flex-direction: row;justify-content: center; align-items: center;gap: 10px;padding: 0 2vw;">
        <img style="width:70px;height:40px" src="{{$video_thumbnail_url}}" />
      <p style="padding: 10px;">
        {{$video_title}}
      </p>
    </div>
  </a>



  <div style="width: 90vw;border: .1px solid black;display: flex;flex-direction: row; padding: 2vw 2vw;gap: 5px;">
    <div style="display: flex; justify-content: center;align-items: center">
      <img style="border-radius: 100%;width: 40px" src="{{$replier_logo_url}}">
    </div>


    <div style="display: flex;flex-direction: column;gap: 10px">

      <div style="display: flex;flex-direction: column;gap: 5px">
        <strong>{{$replier_name}}</strong>
        <span>{{$text}}</span>
      </div>


      <div style="display: flex;justify-content: left;flex-direction: row;gap: 10px">
        <a style="text-decoration: none;padding: 1vw 2vw;background-color: rgba(0,0,0,0.7);color: white;font-size: 2vw;border-radius: 3vw;" href="{{$reply_page_link}}">Reply</a>
      </div>
    </div>
  </div>
</div>
@endif
@endsection