var enc = encodeURIComponent,
    layer,
    config;

// constants
var API_SERVER    = "http://api.lap.com/add";
var HELP_PAGE_URL = "http://google.com";


function main() {
    layer  = document.querySelector(".overlay");
    config = document.querySelector(".pb-configuration");

    window.addEventListener("load", function() {
        var pi = new PinboardInput();
        if ( ! localStorage.getItem("pinboard-token") ) {
            console.log("Please set token");
            pi.showConfiguration(true);
        }
        captureOpeningTabInfo(pi);
    });
}

function captureOpeningTabInfo(/*pi*/) {

}

function PinboardInput() {
    this.tagInpuut    = null;
    this.form         = null;
    this.registBtn    = null;
    this.config       = null;

    this.initialize();
}
PinboardInput.prototype.initialize = function() {
    this.form      = document.querySelector(".pb-table");
    this.registBtn = document.querySelector(".pb-submit");
    this.tagInput  = this.form.querySelector("[name=tags]");
    this.config    = document.querySelector(".pb-configuration");

    this.registBtn.addEventListener("click", this);
    this.config.addEventListener("click", this);
    this.tagInput.addEventListener("keyup", this);
};
PinboardInput.prototype.handleEvent = function(evt) {
    switch ( evt.type ) {
        case "click":
            if ( evt.target === this.registBtn ) {
                // pushed regist button
                evt.preventDefault();
                this.sendPinData();
            } else if ( evt.target === this.config ) {
                // toggle show config
                evt.preventDefault();
                this.toggleConfig();
            }
            break;

        case "keyup": // tag input observer
            this.controlTags();
            break;
    }
};
PinboardInput.prototype.toggleConfig = function() {
    ( TokenForm.isHidden() ) ? this.showConfiguration() : this.hideConfiguration();
};
PinboardInput.prototype.showConfiguration = function(lock) {
    var tf = TokenForm.getInstance(),
        v  = localStorage.getItem("pinboard-token");

    tf.setToken(( v === void 0 || v === null ) ? "" : v);
    tf.show(lock);
};
PinboardInput.prototype.hideConfiguration = function() {
    var tf = TokenForm.getInstance();

    if ( tf.isLocked() ) {
        return;
    }

    tf.hide();
};
PinboardInput.prototype.sendPinData = function() {
    var nodes    = this.form.querySelectorAll("input[type=text], textarea"),
        postData = [],
        xhr      = new XMLHttpRequest();

    [].forEach.call(nodes, function(node) {
        postData.push(enc(node.name) + "=" + enc(node.value));
    });

    xhr.onload = function() {
        this.handleSuccessResponse(xhr.responseText);
    }.bind(this);
    xhr.onerror = function() {
        this.handleErrorResponse(xhr.responseText);
    }.bind(this);

    xhr.open("POST", API_SERVER, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xhr.send(postData);
};
PinboardInput.prototype.parseMessage = function(message) {
    var json,
        parsed;

    try {
        json = JSON.parse(message);
    } catch ( e ) {
        console.log("JSON Parse error: %s", message);
        json = {
            message: message
        };
    } finally {
        parsed = json.message;
    }

    return parsed;
};
PinboardInput.prototype.controlTags = function() {
};
PinboardInput.prototype.handleSuccessResponse = function(message) {
    message = this.parseMessage(message);
};
PinboardInput.prototype.handleErrorResponse = function(message) {
    message = this.parseMessage(message);
};

function Overlay() {}
Overlay.prototype.showOverlay = function() {
    layer.classList.remove("hidden");
};
Overlay.prototype.hideOverlay = function() {
    layer.classList.add("hidden");
};

var TokenFormInstance;
function TokenForm() {
    this.form   = null;
    this.input  = null;
    this.save   = null;
    this.info   = null;
    this.error  = null;
    this.locked = false;
    this.hidden = true;

    Overlay.call(this);

    this.initialize();
}
TokenForm.getInstance = function() {
    if ( ! TokenFormInstance ) {
        TokenFormInstance = new TokenForm();
    }

    return TokenFormInstance;
};
TokenForm.isHidden = function() {
    return TokenForm.getInstance().hidden;
};
TokenForm.prototype = new Overlay();
TokenForm.prototype.initialize = function() {
    this.form  = document.querySelector(".pb-tokenform");
    this.input = this.form.querySelector(".pb-tokeninput");
    this.save  = this.form.querySelector(".pb-tokensave");
    this.info  = this.form.querySelector(".pb-tokeninfo");
    this.error = this.form.querySelector(".pb-tokenerror");

    this.save.addEventListener("click", this);
    this.info.querySelector("a").addEventListener("click", function(evt) {
        evt.preventDefault();

        chrome.tabs.create({url: HELP_PAGE_URL});
    });
};
TokenForm.prototype.setToken = function(token) {
    this.input.value = token;
};
TokenForm.prototype.show = function(lock) {
    this.showOverlay();
    this.form.classList.remove("hidden");
    this.error.classList.add("hidden");

    this.locked = !!lock;
    this.hidden = false;
};
TokenForm.prototype.hide = function() {
    this.hideOverlay();
    this.form.classList.add("hidden");

    this.hidden = true;
};
TokenForm.prototype.isLocked = function() {
    return this.locked;
};
TokenForm.prototype.handleEvent = function(evt) {
    evt.preventDefault();

    var token = this.input.value,
        message;

    if ( token === "" ) {
        this.error.textContent = "Token must not empty!";
        this.error.classList.remove("hidden");
        return;
    }

    this.error.classList.add("hidden");
    localStorage.setItem("pinboard-token", token);

    this.hide();
    message = new Message("Saved!");
    message.show(2000);
};

function Message(message) {
    this.message     = message;
    this.frame       = null;
    this.loading     = null;
    this.showLoading = false;
    this.timer       = null;

    Overlay.call(this);

    this.initialize();
}

Message.prototype = new Overlay();
Message.prototype.initialize = function() {
    this.frame   = document.querySelector(".pb-message");
    this.loading = this.frame.querySelector("p");

    this.loading.textContent = this.message;
};
Message.prototype.setLoading = function(flag) {
    this.showLoading = !!flag;
};
Message.prototype.show = function(duration) {
    this.showOverlay();
    if ( this.showLoading === true ) {
        this.loading.classList.add("loading");
    } else {
        this.loading.classList.remove("loading");
    }

    this.frame.classList.remove("hidden");
    if ( typeof duration === "number" ) {
        this.timer = setTimeout(this.hide.bind(this), duration);
    }
};
Message.prototype.hide = function() {
    this.hideOverlay();
    this.frame.classList.add("hidden");
};

main();
