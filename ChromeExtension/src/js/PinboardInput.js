/**
 * Like a pinboard Chrome Extension
 *
 * @author Yoshiaki Sugimoto <sugimoto@wnotes.net>
 * @License MIT
 * @copyright Yoshiaki Sugimoto all rights reserved.
 */

//= require TokenForm.js

/**
 * Pin data input manager class
 *
 * @class PinboardInput
 * @constructor
 */
function PinboardInput() {
    this.tagInpuut    = null;
    this.form         = null;
    this.registBtn    = null;
    this.config       = null;

    this.initialize();
}

/**
 * Initialize
 *
 * @method initialize
 * @private
 * @return Void
 */
PinboardInput.prototype.initialize = function() {
    this.form      = document.querySelector(".pb-table");
    this.registBtn = document.querySelector(".pb-submit");
    this.tagInput  = this.form.querySelector("[name=tags]");
    this.config    = document.querySelector(".pb-configuration");

    this.registBtn.addEventListener("click", this);
    this.config.addEventListener("click", this);
    this.tagInput.addEventListener("keyup", this);
};

/**
 * Event handler
 *
 * @method handleEvent
 * @public
 * @param Event evt
 * @return Void
 */
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

/**
 * Toggle config
 *
 * @method toggleConfig
 * @public
 * @return Void
 */
PinboardInput.prototype.toggleConfig = function() {
    ( TokenForm.isHidden() ) ? this.showConfiguration() : this.hideConfiguration();
};

/**
 * URL value setter
 *
 * @method setUrl
 * @public
 * @param String url
 * @return Void
 */
PinboardInput.prototype.setUrl = function(url) {
    this.form.querySelector("[name=url]").value = url;
};

/**
 * Title value setter
 *
 * @method setTitle
 * @public
 * @param String title
 * @return Void
 */
PinboardInput.prototype.setTitle = function(title) {
    this.form.querySelector("[name=title]").value = title;
};

/**
 * Focus element
 *
 * @method focus
 * @public
 * @param String name
 * @return Void
 */
PinboardInput.prototype.focus = function(name) {
    this.form.querySelector("[name=" + name + "]").focus();
};

/**
 * Show config window
 *
 * @method showConfiguration
 * @public
 * @param Boolean lock
 * @return Void
 */
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

/**
 * Hide config window
 *
 * @method hideConfiguration
 * @public
 * @return Void
 */
PinboardInput.prototype.hideConfiguration = function() {
    var tf = TokenForm.getInstance();

    if ( tf.isLocked() ) {
        return;
    }

    tf.hide();
};

/**
 * Send pin data to server
 *
 * @method sendPinData
 * @public
 * @return Void
 */
PinboardInput.prototype.sendPinData = function() {
    var nodes    = this.form.querySelectorAll("input[type=text], textarea"),
        postData = [],
        config   = JSON.parse(localStorage.getItem("pinboard-token")),
        xhr      = new XMLHttpRequest(),
        loading  = new Message("Sending pin data...");

    [].forEach.call(nodes, function(node) {
        postData.push(enc(node.name) + "=" + enc(node.value));
    });

    xhr.onload = function() {
        this.handleResponse(xhr.responseText, loading);
    }.bind(this);

    xhr.onerror = function() {
        this.handleResponse(xhr.responseText, loading, true);
    }.bind(this);

    // loading
    loading.setLoading(true);
    loading.show();

    xhr.open("POST", config.requestHost + API_SERVER_PATH, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xhr.send(postData);
};

/**
 * Try to parse message
 *
 * @method parseMessage
 * @private
 * @param String message
 * @return Object
 */
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

/**
 * Cotrol tags input
 *
 * @method controlTags
 * @private
 * @return Void
 */
PinboardInput.prototype.controlTags = function() {
    // TODO: implement
};

/**
 * Handle response
 *
 * @method handleSuccessResponse
 * @public
 * @param String response
 * @param Message loading
 * @param Boolean isError
 * @return Void
 */
PinboardInput.prototype.handleResponse = function(response, loading, isError) {
    var json    = this.parseMessage(response),
        message = new Message(json.message, isError);

    loading.hide();
    message.show();
};
