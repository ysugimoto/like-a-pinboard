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
 */
function Message(message) {
    this.message     = message;
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
 * @return
 */
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

/**
 * Hide message
 *
 * @method hide
 * @public
 * @return
 */
Message.prototype.hide = function() {
    this.hideOverlay();
    this.frame.classList.add("hidden");
};