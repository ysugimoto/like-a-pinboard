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

function captureOpeningTabInfo(pi) {
    chrome.tabs.getSelected(null, function(tab) {
        pi.setUrl(tab.url);
        pi.setTitle(tab.title);
        // A few minute delay
        setTimeout(function() {
            pi.focus("tags");
        }, 50);
    });
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
PinboardInput.prototype.setUrl = function(url) {
    this.form.querySelector("[name=url]").value = url;
};
PinboardInput.prototype.setTitle = function(title) {
    this.form.querySelector("[name=title]").value = title;
};
PinboardInput.prototype.focus = function(name) {
    this.form.querySelector("[name=" + name + "]").focus();
};
PinboardInput.prototype.showConfiguration = function(lock) {
    var tf = TokenForm.getInstance(),
        v  = localStorage.getItem("pinboard-token"),
        json;

    try {
        json = JSON.parse(v);
        tf.setToken(json);
        tf.show(lock);
    } catch ( e ) {
        tf.setToken({host: "", token: ""});
        tf.show(lock);
    }

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
    this.form  = document.querySelector(".pb-settingform");
    this.token = this.form.querySelector(".pb-tokeninput");
    this.host  = this.form.querySelector(".pb-hostinput");
    this.save  = this.form.querySelector(".pb-tokensave");
    this.info  = this.form.querySelector(".pb-tokeninfo");

    this.tokenError = this.form.querySelector(".pb-tokenerror");
    this.hostError  = this.form.querySelector(".pb-hosterror");

    this.save.addEventListener("click", this);
    this.info.querySelector("a").addEventListener("click", function(evt) {
        evt.preventDefault();

        chrome.tabs.create({url: HELP_PAGE_URL});
    });
};
TokenForm.prototype.setToken = function(setting) {
    this.host.value  = ( ! ("requestHost" in setting) || setting.requestHost === null ) ? "" : setting.requestHost;
    this.token.value = ( ! ("token"       in setting) || setting.token       === null ) ? "" : setting.token;
};
TokenForm.prototype.show = function(lock) {
    this.showOverlay();
    this.form.classList.remove("hidden");
    this.hostError.classList.add("hidden");
    this.tokenError.classList.add("hidden");

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

    var token  = this.token.value,
        host   = this.host.value,
        errors = [],
        promise;

    if ( token === "" ) {
        errors.push(function() {
            this.tokenError.textContent = "Token must not empty!";
            this.tokenError.classList.remove("hidden");
        }.bind(this));
    }
    if ( host === "" ) {
        errors.push(function() {
            this.hostError.textContent = "Host must not empty!";
            this.hostError.classList.remove("hidden");
        }.bind(this));
    }

    if ( errors.length > 0 ) {
        errors.forEach(function(error) {
            error();
        })
        return;
    }

    // Check accept request ( async )
    promise = new Promise(this.acceptRequest(host, token));
    promise.then(function(json) {
        var message;

        this.tokenError.classList.add("hidden");
        this.hostError.classList.add("hidden");
        localStorage.setItem("pinboard-token", JSON.stringify({
            requestHost: host,
            token: token
        }));

        this.hide();
        message = new Message("Welcome, " + json.message + "!");
        message.show(2000);
    }.bind(this), function(reason) {
        this.hostError.textContent = reason.host;
        this.hostError.classList.remove("hidden");
        this.tokenError.textContent = reason.token;
        this.tokenError.classList.remove("hidden");
    }.bind(this));
};
TokenForm.prototype.acceptRequest = function(host, token) {
    return function(resolve, reject) {
        var xhr = new XMLHttpRequest();

        xhr.onload = function() {
            switch ( xhr.status ) {
                case 200:
                    resolve(JSON.parse(xhr.responseText));
                    break;
                default:
                    reject({
                        host: "",
                        token: "Token not authorized"
                    });
                    break;
            }
        };
        xhr.onerror = function() {
            switch ( xhr.status ) {
                case 404:
                    reject({
                        host: "Host not found",
                        token: ""
                    });
                    break;
                default:
                    reject({
                        host: "",
                        token: "Token not authorized"
                    });
                    break;
            }
        };

        xhr.open("GET", host + "/accept?token=" + enc(token), true);
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xhr.send(null);
    };
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
