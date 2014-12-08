var gui    = require('nw.gui');
var fs     = require('fs');
var path   = require('path');
var clip   = gui.Clipboard.get();
var win    = gui.Window.get();
var hidden = true;
var tray   = new gui.Tray({
    alticon: 'images/alticon.white.png',
    icon:    'images/alticon.png'
});

tray.on('click', function() {
    win.setPosition('center');
    win.show();
    win.focus();
    input.focus();
    hidden = false;
});

var input    = document.getElementById('q');
var list     = document.querySelector('.launcher-list');
var terminal = document.querySelector('.terminal');
var hitList  = document.querySelectorAll('.list-item');

input.addEventListener('keydown', function(evt) {
    console.log(evt);
    if ( evt.keyCode === 9 ) {
        evt.preventDefault();
        document.querySelector('.list-item:first-child').setAttribute('data-selected', 1);;
        input.blur();
    } else if ( evt.keyCode == 86 && evt.metaKey === true ) {
        input.value = clip.get();
        // todo handle keyup immidiataly
    }

});

input.addEventListener('keyup', function(evt) {
    if ( input.value === "" ) {
        list.classList.remove('show');
        terminal.classList.remove('show');
        win.resizeTo(742, 168);
        return;
    }

    if ( input.value.charAt(0) === ':' ) {
        list.classList.remove('show');
        terminal.classList.add('show');
        win.resizeTo(742, 522);
    } else {
        list.classList.add('show');
        terminal.classList.remove('show');
        var hits = [];
        var regex = new RegExp(input.value, 'i');
        var max = 522;
        Object.keys(fileList).forEach(function(name) {
            if ( regex.test(name) ) {
                hits[hits.length] = fileList[name];
            }
        });
        for ( var i = 0; i < 6; ++i ) {
            if ( ! hits[i] ) {
                break;
            }
            hitList[i].innerHTML = '<strong>' + hits[i].name + '</strong>' + hits[i].absPath;
            hitList[i].classList.remove('notfound');
        }
        for ( ; i < 6; ++i ) {
            hitList[i].classList.add('notfound');
            max -= 59;
        }
        if ( max < 200 ) {
            max = 168;
        }
        console.log('resize to ' + max);
        win.resizeTo(742, max);
    }
});

var shortcut = new gui.Shortcut({
    key: "Ctrl+Period"
});

gui.App.registerGlobalHotKey(shortcut);
shortcut.on('active', function() {
    console.log('shortcut activated');
    if ( hidden === true ) {
        win.show();
        win.setPosition('center');
        win.focus();
        input.focus();
    } else {
        win.hide();
    }
    hidden = !hidden;
});

//win.on('blur', function() {
//    console.log('blured');
//    win.hide();
//    hidden = true;
//});

new Notification('Application Launched background');
win.resizeTo(742, 168);

document.addEventListener('keydown', function(evt) {
    if ( evt.keyCode === 27 ) {
        win.hide();
        hidden = true;
    } else if ( evt.keyCode === 13 ) {
        console.log('enter');
    }
});

var settings = ( fs.existsSync(process.env.HOME + '/.alfredrc') )
                 ? fs.readFileSync(process.env.HOME + '/.alfrelrc')
                 : {};

if ( ! settings.path ) {
    settings.path = [];
}
settings.path.unshift('/Applications');
if ( ! settings.shell ) {
    settings.shell = '/bin/bash';
}


var indexes = localStorage.getItem('indexes');
var fileList = ( indexes ) ? JSON.parse(indexes) : {};
settings.path.forEach(function(detectPath) {
    find(detectPath, true);
});
function find(detectPath, isApp) {
    if ( ! fs.existsSync(detectPath) ) {
        console.log(detectPath);
        return;
    }
    var stat = fs.statSync(detectPath);
    var base = path.basename(detectPath);

    if ( stat.isFile() && ! isApp && !(base in fileList) ) {
        fileList[base] = {
            name: base,
            absPath: detectPath,
            type: 'file'
        };
    } else if ( stat.isDirectory() ) {
        if ( /\.app$/.test(detectPath) && !(detectPath in fileList) ) {
            var icns = fs.readdirSync(detectPath).filter(function(file) {
                return /\.icns$/.test(file);
            });
            if ( icns.length > 0 ) {
                icns = icns[0];
            }
            var base = path.basename(detectPath);
            fileList[base] = {
                name: base,
                absPath: detectPath,
                type: 'app',
                icon: icns
            };
        } else {
            fs.readdirSync(detectPath).forEach(function(subPath) {
                find(detectPath + '/' + subPath, isApp);
            });
        }
    }
}

localStorage.setItem('setting', JSON.stringify(settings));
localStorage.setItem('indexes', JSON.stringify(fileList));

console.log(JSON.stringify(fileList).length);
console.log(fileList);
