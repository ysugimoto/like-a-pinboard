/**
 * Like a pinboard Chrome Extension
 *
 * @author Yoshiaki Sugimoto <sugimoto@wnotes.net>
 * @License MIT
 * @copyright Yoshiaki Sugimoto all rights reserved.
 */

//= require Overlay.js

// Singleton instance
var TokenFormInstance;

/**
 * Token input manager class
 *
 * @class TokenForm
 * @constructor
 */
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

/**
 * Get singleton instance
 *
 * @method getInstance
 * @public static
 * @return TokenForm TokenFormInstance
 */
TokenForm.getInstance = function() {
    if ( ! TokenFormInstance ) {
        TokenFormInstance = new TokenForm();
    }

    return TokenFormInstance;
};

/**
 * Check form is hidden
 *
 * @method isHidden
 * @public static
 * @return Bool
 */
TokenForm.isHidden = function() {
    return TokenForm.getInstance().hidden;
};

// extend
TokenForm.prototype = new Overlay();

/**
 * Initialize
 *
 * @method initialize
 * @private
 * @return Void
 */
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

/**
 * Set setting values
 *
 * @method setToken
 * @public
 * @param Object setting
 * @return Void
 */
TokenForm.prototype.setToken = function(setting) {
    this.host.value  = ( ! ("requestHost" in setting) || setting.requestHost === null ) ? "" : setting.requestHost;
    this.token.value = ( ! ("token"       in setting) || setting.token       === null ) ? "" : setting.token;
};

/**
 * Show form
 *
 * @method show
 * @public
 * @return Void
 */
TokenForm.prototype.show = function(lock) {
    this.showOverlay();
    this.form.classList.remove("hidden");
    this.hostError.classList.add("hidden");
    this.tokenError.classList.add("hidden");

    this.locked = !!lock;
    this.hidden = false;
};

/**
 * Hide form
 *
 * @method hide
 * @public
 * @return Void
 */
TokenForm.prototype.hide = function() {
    this.hideOverlay();
    this.form.classList.add("hidden");

    this.hidden = true;
};

/**
 * Check form is locked
 *
 * @method isLocked
 * @public
 * @return Book
 */
TokenForm.prototype.isLocked = function() {
    return this.locked;
};

/**
 * Event handler
 *
 * @method handleEvent
 * @public
 * @param Event evt
 * @return Void
 */
TokenForm.prototype.handleEvent = function(evt) {
    evt.preventDefault();

    var token  = this.token.value,
        host   = this.host.value,
        errors = [],
        promise;

    // validate
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
    } else if ( ! /^https?:\/\/[\w\-\._]+(?:\:[0-9]+)$/.test(host) ) {
        errors.push(function() {
            this.hostError.textContent = "Host must be URL format!";
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

/**
 * Send to your server for accept
 *
 * @method acceptRequest
 * @private
 * @param String host
 * @param String token
 * @return Function(Promise.resolve, Promise.reject)
 */
TokenForm.prototype.acceptRequest = function(host, token) {
    return function(resolve, reject) {
        var xhr = new XMLHttpRequest();

        xhr.onload = function() {
            if ( xhr.status === 200 ) {
                resolve(JSON.parse(xhr.responseText));
            } else {
                reject({
                    host: "",
                    token: "Token not authorized"
                });
            }
        };
        xhr.onerror = function() {
            var reason = {
                host: "",
                token: ""
            };

            if ( xhr.status === 404 ) {
                reason.host = "Host not found";
            } else {
                reason.token = "Token not authorized";
            }

            reject(reason);
        };

        xhr.open("GET", host + "/accept", true);
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xhr.setRequestHeader("X-LAP-Token", "token");
        xhr.send(null);
    };
};
