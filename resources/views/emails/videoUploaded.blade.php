<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <style>
    * {
      margin: 0vw;
    }
    #video-email {
      position: absolute;
      top: 20vw;
      width: 100vw;
      height: max-content;
      min-height: 100vw;
      overflow: hidden;
    }

    #video-email-header {
      width: 100vw;
      height: 20vw;
      background-color: darkmagenta;
      position: absolute;
      top: 0vw;
    }
    #video-email-header p {
      color: white;
      font-weight: 600;
      font-size: 5vw;
      padding: 1vw;
      position: absolute;
      top: 6vw;
      left: 34vw;
    }
    #pPic {
      width: 20vw;
      height: 20vw;
      border: .10vw solid darkmagenta;
      border-radius: 100%;
      position: absolute;
      top: 25vw;
      left: 10vw;
    }
    #channel-Name {
      width: 90vw;
      position: absolute;
      left: 3vw;
      top: 45vw;
      font-weight: bold;
    }
    #thumbnail {
      width: 60vw;
      height: 40vw;
      position: absolute;
      left: 20vw;
      top: 55vw;
    }
    #title {
      font-size: 4vw;
      font-weight: 600;
      position: relative;
      left: 5vw;
      top: 100vw;
      width: 90vw;
      display: inline-block;
      overflow-x: hidden;
      overflow-wrap: break-word;
    }
    #description {
      font-size: 3vw;
      font-weight: 400;
      position: relative;
      left: 5vw;
      top: 105vw;
      width: 90vw;
      display: inline-block;
      overflow-x: hidden;
      overflow-wrap: break-word;
    }
    #video-email-footer {
      width: 100vw;
      height: 13vw;
      position: absolute;
      bottom: 0vw;
      background-color: darkmagenta;
    }
    #video-email-footer p {
      font-size: 3vw;
      color: white;
      padding: 1vw;
      position: absolute;
      left: 20vw;
      top: 3vw;
    }
    #watchBtn {
      position: absolute;
      font-weight: 500;
      top: 32vw;
      right: 10vw;
      text-decoration: none;
      padding: 1vw 2vw 1vw 2vw;
      background-color: magenta;
      color: white;
      border-radius: 2vw;
    }
    #watchBtn:active {
      background-color: darkmagenta;
    }
    h5 {
      position: absolute;
      bottom: 20vw;
      left: 8vw;
    }
</style>
  </head>
  <body>
  <div id="video-email-header">
    <p>
      SamerTube
    </p>
  </div>
  <a id="watchBtn" href="{{$data['link']}}">Watch</a>
  <img id="pPic" src="{{$data['channel_logo_url']}}">
  <p id="channel-Name">
    {{$data['channel_name']}}
  </p>
  <img id="thumbnail" src="{{$data['thumbnail_url']}}">
  <h3 id="title">{{$data['title']}}</h3>
  <small id="description">{{$data['description']}}</small>
  <h5>Thanks for using our aplication!</h5>
  <div id="video-email-footer">
    <p>&#169; 2023 SamerTube. all rights reserved</p>
  </div>
  <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
  </body>
</html>
