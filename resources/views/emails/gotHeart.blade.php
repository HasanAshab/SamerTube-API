@extends('emails.layout')
@section('content')
@if($type === 'video')
<div style="width: 100vw;display:flex;flex-direction: column;justify-content: center; align-items: center;margin-top: 10px">

  <h4 style="padding: 5px;font-weight: 500">{{$creator_channel_name}} Loves your Comment</h4>

  <a style="text-decoration: none; color: black;width: 90%" href="{{$video_link}}"><div style="display:flex;flex-direction: row;justify-content: center; align-items: center;gap: 10px;padding: 0 2vw;">
    <img style="width:4rem;height: 2rem;" src="{{$video_thumbnail_url}}" />
    <p>
      {{$video_title}}
    </p>
  </div>
</a>



<div style="width: 90vw;display: flex;flex-direction: row; padding: 2vw 2vw;gap: 5px;">

  <div style="display: flex; justify-content: center;align-items: top">
    <a style="display: block" href="{{$commenter_channel_link}}"><img style="border-radius: 100%;width: 40px" src="{{$commenter_logo_url}}"></a>

  </div>


  <div style="display: flex;flex-direction: column;gap: 10px">

    <div style="display: flex;flex-direction: column;gap: 5px">
      <strong>{{$commenter_name}}</strong>
      <span>{{$text}}</span>
    </div>


    <div style="display: flex;justify-content: left;flex-direction: row;gap: 10px">
      <a style="text-decoration: none;padding: 1vw 2vw;background-color: rgba(0,0,0,0.7);color: white;font-size: 2vw;border-radius: 3vw;" href="{{$highlight_page_link}}">VIEW HARTED COMMENT</a>
    </div>
  </div>
  </div>
</div>
@endif
@endsection