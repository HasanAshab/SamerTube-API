<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Postman</title>
</head>
<body>

  <form action="api/video/upload" method="post" enctype="multipart/form-data">
    @csrf
    <input type="number" placeholder="uploader_id" name="uploader_id">
    <input type="text" placeholder="title" name="title">
    <input type="text" placeholder="description" name="description">
    <input type="text" placeholder="category id" name="category_id">
    <input type="text" placeholder="visibility" name="visibility">
    <input type="file" name="video" accept=".hh">
    <input type="file" name="thumbnail" accept="image/*">
    
     <!--
   
    @method('PUT')
    <input type="text" placeholder="name" name="name">
    <input type="text" placeholder="description" name="description">
    <input type="file" accept="image/*" name="logo">
    -->
    <button type="submit">Submit</button>
  </form>
</body>
</html>