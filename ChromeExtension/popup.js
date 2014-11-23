var form = document.querySelector(".pb-table"),
    btn  = document.querySelector(".pb-submit");

var enc = encodeURIComponent;

if ( ! localStorage.getItem("pinboard-token") ) {
    console.log("Please set token");
    showTokenInput();
}

btn.addEventListener("click", handleSubmit);

function handleSubmit(evt) {
    evt.preventDefault();

    var nodes    = form.querySelectorAll("input[type=text], textarea"),
        postData = [];

    [].forEach.call(nodes, function(node) {
        postData.push(enc(node.name) + "=" + enc(node.value));
    });

    sendPinData(postData.join("&"));
}

function sendPinData(postData) {
    var xhr = new XMLHttpRequest();

    xhr.onload = function() {
        console.log(xhr.responseText);
    };
    xhr.onerror = function() {
        console.log(xhr.responseText);
    };

    xhr.open("POST", "http://localhost:8888/add", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xhr.send(postData);
}

function showTokenInput() {
    var layer = document.querySelector(".overlay"),
        input = layer.querySelector(".pb-tokeninput"),
        save  = layer.querySelector(".pb-tokensave");

    layer.classList.remove("hidden");

    save.addEventListener("click", function() {
        var token = input.value;

        localStorage.setItem("pinboard-token", token);
        layer.classList.add("hidden");

    });
}


