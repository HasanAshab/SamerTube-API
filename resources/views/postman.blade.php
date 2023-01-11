<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Postman -Hasan Ashab</title>
  <style>
    #cont{
      background-color: #e8ebeb;
      overflow-wrap: break-word;
      overflow-y: scroll;
      height: 50vh;
    }
  </style>
</head>
<body>
  <input type="url" placeholder="Link" id="url" value="http://127.0.0.1:8000/api/" required><br>
<textarea type="text" placeholder='{"key":"value"}' id="body" rows="6"></textarea>
<select id="method">
  <option value="GET">Get</option>
  <option value="POST">Post</option>
  <option value="DELETE">Delete</option>
  <option value="PUT">Put</option>
  <option value="PATCH">Patch</option>
  <option value="OPTIONS">Options</option>
</select><br>
<label for="auth">Authorization</label>
<input type="checkbox" id="auth"><br>
<label for="saveToken">Save Token</label>
<input type="checkbox" id="saveToken"><br>

<label for="customToken">Custom Token: </label>
<input type="text" id="customToken"><button id="customTokenBtn">Use</button>
<br><br>

<button id="sent">Sent Request</button>
<h3>Response:</h3>
<label for="status">Status: </label> <i><small id="status">NONE</small></i><br>
<label for="cont">Body:</label>
<div id="cont">Empty....</div>

</body>
<script>
const btn = document.getElementById("sent")
btn.addEventListener("click", ()=>{
  let token = localStorage.getItem('token');
  const cont = document.getElementById('cont');
  const statusBar = document.getElementById('status');
  const url = document.getElementById('url').value;
  const body = document.getElementById('body').value;
  const method = document.getElementById('method').value;
  const auth = document.getElementById('auth');
  const saveToken = document.getElementById('saveToken');
  let myInit = {};
  let headers = {
    'Content-Type': 'application/json',
    'Accept' :'application/json'
  }
  if (auth.checked){
    headers = {
      'Content-Type': 'application/json',
      'Accept' :'application/json',
      'Authorization': `Bearer ${token}`}
  }
  if(body === ''){
    myInit = {
    method: method,
    headers: headers
  };
  }
  else{
  myInit = {
    method: method,
    headers: headers,
    body: JSON.stringify(JSON.parse(body))
  };
  }

  fetch(url, myInit).then((response) => {
    statusBar.innerText = response.status;
    statusBar.style.color = (response.status >= 200 && response.status < 300)?'green':'red';
    return response.json()
  }).then((data) =>{
      cont.innerText = JSON.stringify(data);
      if (saveToken.checked){
        localStorage.setItem('token', data.access_token)
      }
    });
});

const customTokenBtn = document.getElementById('customTokenBtn');
customTokenBtn.addEventListener("click", ()=>{
  localStorage.setItem('token', document.getElementById('customToken').value)
  document.getElementById('customToken').value = '';
})
</script>
</html>
