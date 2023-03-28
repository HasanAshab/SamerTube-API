@extends('emails.layout')
@section('content')
<div style="width: 100vw;display:flex;flex-direction: column;justify-content: center; align-items: center;margin-top: 10px">
<div style="width: 90vw;display: flex;flex-direction: row; padding: 1vw 1vw;gap: 5px;justify-content: center;">
 
  <div style="display: flex; justify-content: center;align-items: center">
    <a href="{{$subscriber_channel_page_link}}"><img style="border-radius: 100%;width: 40px" src="{{$subscriber_logo_url}}"></a>
  </div>
   
  <div style="display: flex;flex-direction: column;gap: 3px">
   <p style="margin: 0;font-size: .8rem"><a style="text-decoration: none" href="{{$subscriber_channel_page_link}}">{{$subscriber_name}}</a> has subscribed you on {{config('app.name')}}</p>
     <span style="text-decoration: none;font-size: 2.5vw;color: dimgray" >{{$subscriber_sub_count}} Subscriber</span>
 </div>

</div>

  <hr style="width: 99%;">
<div style="width: 90%;padding: 0 1vw; color: dimgray">
Channels who subscribe to you will be notified when you upload new videos or respond to others.
</div>
</div>
@endsection