/**
 * Like a pinboard Chrome Extension
 *
 * @author Yoshiaki Sugimoto <sugimoto@wnotes.net>
 * @License MIT
 * @copyright Yoshiaki Sugimoto all rights reserved.
 */

(function() {

// Request path constants
var API_SERVER_PATH    = "/add";
var GETTING_TOKEN_PATH = "/generate";

// include depend classes
//= require PinboardInput.js
//= require Overlay.js
//= require TokenForm.js
//= require Message.js

// Main
window.addEventListener("load", function() {
    var pi = new PinboardInput();

    // Did you save settings at localStorage?
    if ( ! localStorage.getItem("pinboard-token") ) {
        pi.showConfiguration(true);
    }

    // Get active tab's url and title
    chrome.tabs.getSelected(null, function(tab) {
        pi.setUrl(tab.url);
        pi.setTitle(tab.title);
        // A few delay
        setTimeout(function() {
            pi.focus("tags");
        }, 50);
    });
});

})();
