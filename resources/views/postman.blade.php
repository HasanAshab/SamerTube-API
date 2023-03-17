<!DOCTYPE html>

<html lang="en">



<head>

  <meta charset="UTF-8">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <meta http-equiv="X-UA-Compatible" content="ie=edge">

  <title>Postman -Hasan Ashab</title>

  <style>

    * {

      margin: 0;

      padding: 0;

    }



    #cont {

      background-color: #e8ebeb;

      overflow-wrap: break-word;

      overflow-y: scroll;

      height: 50vh;

    }



    #allInputsDiv {

      margin-top: 10vw;

      background-color: whitesmoke;

      display: flex;

      flex-direction: column;



    }



    .key-value-divs {

      display: flex;

      flex-direction: row;

      margin-bottom: 2vw;

      justify-content: space-between

    }



    .key-value-divs input {

      width: 40%

    }



    #telescope {

      width: 100%;

      height: 50vh;

    }

  </style>

</head>



<body>

  <details>

    <summary>Debug Bar</summary>

    <iframe src="{{config('app.url')}}/{{config('telescope.path')}}/requests" frameborder="10" id="telescope"></iframe>

  </details>



  <input type="url" placeholder="Link" id="url" value="{{config('app.url')}}/api/" required>

  <select id="method">

    <option value="GET">Get</option>

    <option value="POST">Post</option>

    <option value="DELETE">Delete</option>

    <option value="PUT">Put</option>

    <option value="PATCH">Patch</option>

    <option value="OPTIONS">Options</option>

  </select><br>

  <label for="user">User: </label>

  <select name="user" id="user" onchange="setTokenOnChange()">

    <option value="0">Choose Account</option>

    @foreach($users as $user)

    <option value="{{$user->id}}">{{$user->email}}</option>

    @endforeach

  </select><br>



  <label for="auth">Authorization</label>

  <input type="checkbox" id="auth"><br>

  <label for="saveToken">Save Token</label>

  <input type="checkbox" id="saveToken"><br>



  <label for="customToken">Custom Token: </label>

  <input type="text" id="customToken"><button id="customTokenBtn">Use</button>

  <br><br>

  <div id="allInputsDiv">

    <div class="key-value-divs">

      <input type="text" class="keyInp" placeholder="key" oninput="addInputBar()">

      <input type="text" class="valueInp" placeholder="value">

      <span class="closeBtn" onclick="removeInputBar(this.parentNode)">&times;</span>

    </div>

  </div>

  <button onclick="addInputBar()">Add</button>

  <button id="sent">Sent Request</button>

  <h3>Response:</h3>

  <label for="status">Status: </label> <i><small id="status">NONE</small></i><br>

  <label for="cont">Body:</label>

  <div id="cont">

    Empty....

  </div>



</body>

<script>

   javascript: (function () {var script = document.createElement('script'); script.src = "//cdn.jsdelivr.net/npm/eruda"; document.body.appendChild(script); script.onload = function () {eruda.init()}})();



  const btn = document.getElementById("sent")

  var allInputsDiv = document.getElementById("allInputsDiv")



  btn.addEventListener("click", () => {

    let token = localStorage.getItem('token');

    const cont = document.getElementById('cont');

    const statusBar = document.getElementById('status');

    let url = document.getElementById('url').value;

    const method = document.getElementById('method').value;

    const auth = document.getElementById('auth');

    const saveToken = document.getElementById('saveToken');

    const keysArr = Array.from(document.getElementsByClassName('keyInp'));

    const valuesArr = Array.from(document.getElementsByClassName('valueInp'));

    let body = {}

    if (method === "GET") {

      if (keysArr[0].value !== '') {

        url += '?';

        for (i = 0; i < keysArr.length; i++) {

          let key = keysArr[i].value;

          if (key !== '') {

            let value = valuesArr[i].value;

            url += `${key}=${value}&`;

          }

        }

        url = url.substr(0, url.length - 1);

      }

    } else {

      for (i = 0; i < keysArr.length; i++) {

        let key = keysArr[i].value;

        if (key !== '') {

          let value = valuesArr[i].value;

          body[key] = value

        }

      }

    }

    let myInit = {};

    let headers = {

      'Content-Type': 'application/json',

      'Accept': 'application/json'

    }

    if (auth.checked) {

      headers = {

        'Content-Type': 'application/json',

        'Accept': 'application/json',

        'Authorization': `Bearer ${token}`

      }

    }

    if (keysArr[0].value === '' || method === 'GET') {

      myInit = {

        method: method,

        headers: headers

      };

    } else {

      myInit = {

        method: method,

        headers: headers,

        body: JSON.stringify(body)

      };

    }



    fetch(url, myInit).then((response) => {

      statusBar.innerText = response.status;

      statusBar.style.color = (response.status >= 200 && response.status < 300) ? 'green': 'red';

      return response.json()

    }).then((data) => {

      cont.innerText = JSON.stringify(data);

      if (saveToken.checked) {

        localStorage.setItem('token', data.access_token)

      }

    });



  });

  document.getElementById('customTokenBtn').addEventListener("click", () => {

    localStorage.setItem('token',

      document.getElementById('customToken').value)

    document.getElementById('customToken').value = '';

  })



  function setTokenOnChange() {

    id = document.getElementById('user').value;

    if (id > 0) {

      const url = `{{env('APP_URL')}}/get-token/${id}`;

      const myInit = {

        method: 'GET',

        headers: {

          'Content-Type': 'application/json',

          'Accept': 'application/json'

        }

      };

      fetch(url, myInit).then(response => response.json()).then((data) => {

        localStorage.setItem('token',

          data.token);

      });

    }

  }



  function addInputBar() {

    let newKeyValueDiv = document.createElement('div');

    newKeyValueDiv.setAttribute('class', 'key-value-divs');



    let newKeyInp = document.createElement('input');

    newKeyInp.setAttribute('type', 'text');

    newKeyInp.setAttribute('class', 'keyInp');

    newKeyInp.setAttribute('placeholder', 'Key');



    let newValueInp = document.createElement('input');

    newValueInp.setAttribute('type', 'text');

    newValueInp.setAttribute('class', 'valueInp');

    newValueInp.setAttribute('placeholder', 'Value');



    let newCloseBtn = document.createElement('span');

    newCloseBtn.setAttribute('class', 'closeBtn');

    newCloseBtn.setAttribute('onclick', 'removeInputBar(this.parentNode)');

    newCloseBtn.innerHTML = '&times;';



    newKeyValueDiv.appendChild(newKeyInp);

    newKeyValueDiv.appendChild(newValueInp);

    newKeyValueDiv.appendChild(newCloseBtn);

    allInputsDiv.appendChild(newKeyValueDiv);

    updatePushIndex();

  }



  function removeInputBar(e) {

    allInputsDiv.removeChild(e)

    let oldKeyInp = document.querySelectorAll('.keyInp')

    newKeyInp = document.querySelectorAll('.keyInp')[oldKeyInp.length-1];

    newKeyInp.setAttribute('oninput', 'addInputBar()');

  }



  function updatePushIndex() {

    let oldKeyInp = document.querySelectorAll('.keyInp')

    Array.from(oldKeyInp).forEach((e, i) => {

      if (e.hasAttribute('oninput')) {

        e.removeAttribute('oninput');

      }

    })

    newKeyInp = document.querySelectorAll('.keyInp')[oldKeyInp.length-1];

    newKeyInp.setAttribute('oninput',
      'addInputBar()');

  }

</script>

</html>