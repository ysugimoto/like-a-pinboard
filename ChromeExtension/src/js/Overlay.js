/**
 * Like a pinboard Chrome Extension
 *
 * @author Yoshiaki Sugimoto <sugimoto@wnotes.net>
 * @License MIT
 * @copyright Yoshiaki Sugimoto all rights reserved.
 */

/**
 * Overlay class
 *
 * @class Overlay
 * @constructor
 */
function Overlay() {
    this.layer = document.querySelector(".overlay");
}

/**
 * Show overlay
 *
 * @method showOverlay
 * @return Void
 */
Overlay.prototype.showOverlay = function() {
    this.layer.classList.remove("hidden");
};

/**
 * Hide overlay
 *
 * @method hideOverlay
 * @return Void
 */
Overlay.prototype.hideOverlay = function() {
    this.layer.classList.add("hidden");
};
