/**
 * Like a pinboard Chrome Extension
 *
 * @author Yoshiaki Sugimoto <sugimoto@wnotes.net>
 * @License MIT
 * @copyright Yoshiaki Sugimoto all rights reserved.
 */

//= require Overlay.js

/**
 * Message manager class
 *
 * @class Message
 * @constructor
 * @param String message
 * @param Boolean isError
 */
function Message(message, isError) {
    this.message     = message;
    this.isError     = !!isError;
    this.frame       = null;
    this.loading     = null;
    this.showLoading = false;
    this.timer       = null;

    Overlay.call(this);

    this.initialize();
}

// extend
Message.prototype = new Overlay();

/**
 * Initialize
 *
 * @method initialize
 * @private
 * @return Void
 */
Message.prototype.initialize = function() {
    this.frame   = document.querySelector(".pb-message");
    this.loading = this.frame.querySelector("p");

    this.loading.textContent = this.message;
};

/**
 * Set loading flag
 *
 * @method setLoading
 * @public
 * @return Void
 */
Message.prototype.setLoading = function(flag) {
    this.showLoading = !!flag;
};

/**
 * Show message
 *
 * @method show
 * @public
 * @param Number duration
 * @param Boolean afterWindowClose
 * @return
 */
Message.prototype.show = function(duration, afterWindowClose) {
    var that = this;

    this.showOverlay();
    if ( this.showLoading === true ) {
        this.loading.classList.add("loading");
    } else {
        this.loading.classList.remove("loading");
    }

    if ( this.isError === true ) {
        this.loading.classList.add("errormessage");
    }

    this.frame.classList.remove("hidden");
    if ( typeof duration === "number" ) {
        this.timer = setTimeout(function() {
            that.hide(!!afterWindowClose);
        }, duration);
    }
};

/**
 * Hide message
 *
 * @method hide
 * @public
 * @param Boolean closeWindow
 * @return
 */
Message.prototype.hide = function(closeWindow) {
    this.hideOverlay();
    this.frame.classList.add("hidden");

    if ( closeWindow === true ) {
        window.close();
    }
};
