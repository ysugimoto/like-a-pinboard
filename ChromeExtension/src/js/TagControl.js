/**
 * Like a pinboard Chrome Extension
 *
 * @author Yoshiaki Sugimoto <sugimoto@wnotes.net>
 * @License MIT
 * @copyright Yoshiaki Sugimoto all rights reserved.
 */

function TagControl(input) {
    this.tagWrapper    = document.querySelector(".tag-wrapper");
    this.tagInput      = input;
    this.tagElement    = document.createElement("span");
    this.tagList       = {};
    this.isComposition = false;

    this.initialize();
}

TagControl.prototype.initialize = function() {
    var removeElement = document.createElement("a");

    this.tagInput.addEventListener("compositionstart", this);
    this.tagInput.addEventListener("compositionend",   this);
    this.tagInput.addEventListener("keyup",            this);

    this.tagWrapper.addEventListener("click", this);

    this.tagElement.className = "tag";
    removeElement.className   = "remove";

    this.tagElement.appendChild(removeElement);
};

TagControl.prototype.handleEvent = function(evt) {
    switch ( evt.type ) {
        case "keyup":
            this.handleKeyUp(evt);
            break;
        case "click":
            this.handleTagClick(evt);
            break;
        case "compositionstart":
            this.isComposition = true;
            break;
        case "compositionend":
            this.isComposition = false;
            break;
    }
};

TagControl.prototype.handleKeyUp = function(evt) {
    if ( evt.keyCode !== 32 || this.isComposition === true ) { // space key
        return;
    }
    var value = this.tagInput.value;

    // skip if empty input
    if ( value !== "" && value !== " " && ! this.checkExists(value) ) {
        this.createTag(value.trim());
    }

    this.tagInput.value = "";
    this.tagInput.focus();
};

TagControl.prototype.handleTagClick = function(evt) {
    var tag,
        tagName;

    if ( ! evt.target.matches(".remove") ) {
        return;
    }

    tag = evt.target.parentNode;
    tagName = tag.firstChild.nodeValue;
    tag.parentNode.removeChild(tag);

    if ( tagName in this.tagList ) {
        delete this.tagList[tagName];
    }
};

TagControl.prototype.createTag = function(text) {
    var span = this.tagElement.cloneNode(true);

    span.insertBefore(document.createTextNode(text), span.firstChild);

    this.tagWrapper.appendChild(span);
    this.tagList[text] = 1;
};

TagControl.prototype.checkExists = function(text) {
    return ( text in this.tagList );
}

TagControl.prototype.getTagList = function() {
    return Object.keys(this.tagList);
};


