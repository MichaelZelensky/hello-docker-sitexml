/**
 * Created by mzelensky on 06/07/15.
 *
 * Adds second level menu basing on H2 elements on page
 */

var m = function (s) {
    var r;
    switch (s.substr(0, 1)) {
        case '#':
            r = document.getElementById(s.substr(1));
            break;
        case '.':
            r = document.getElementsByClassName(s.substr(1));
            break;
        default :
            r = document.getElementsByTagName(s);
            break;
    }
    return r;
};

(function(){
    var ul2, li2, a, href, text, abase, cl, selected,
        h2/* = m('h2')*/,
        navi = m('.siteXML-navi'),
        naviB = m('#navi-320-button');

    var insertL2Navi = function () {
        //adding menu
        window.navi_li = m('.siteXML-current');
        ul2 = m('.level-2');
        if (ul2.length && ul2[0].remove) {
            ul2[0].remove();
        }
        h2 = m('h2');
        if (h2.length && navi_li.length) {
            ul2 = createUl();
            abase = randStr();
            for (var i = 0, n = h2.length; i < n; i++) {
                text = h2[i].textContent;
                href = abase + i;
                a = createA(href);
                href = '#' + href;
                h2[i].insertBefore(a, h2[i].childNodes[0]);
                li2 = createLi(text, href);
                ul2.appendChild(li2);
            }
            navi_li[0].appendChild(ul2);
        }
    };

    insertL2Navi();

    window.addEventListener("sitexml.content.displayed", function(e){
        insertL2Navi();
    });

    //click listener
    if (navi.length) {
        navi[0].addEventListener('click', function (e) {
            if(e.target && e.target.nodeName == "A") {
                selected = navi[0].getElementsByClassName('selected');
                if (selected && selected.length) {
                    for (var i = 0, n = selected.length; i < n; i++) {
                        cl = selected[i].className.replace(/selected/, '');
                        selected[i].className = cl;
                    }
                }
                e.target.parentNode.className += ' selected';
            }
        });
    }

    //toggle 320px menu
    naviB.addEventListener('click', function () {
        toggle_menu();
    });

    function toggle_menu () {
        var menu = m('#navi-container'),
            c = menu.className,
            c1 = c.replace('open', '');
        if (c === c1) {
            menu.className = c + ' open';
        } else {
            menu.className = c1;
        }
    }

    function createUl () {
        var ul2 = document.createElement('ul');
        ul2.className = 'siteXML-navi level-2';
        return ul2;
    }

    function createLi (text, href) {
        var li = document.createElement('li'),
            a = document.createElement('a'),
            t = document.createTextNode(text);
        a.href = href;
        a.appendChild(t);
        li.appendChild(a);
        return li;
    }

    function createA (name) {
        var a = document.createElement('a');
        a.setAttribute('name', name);
        return a;
    }

    function randStr () {
        var text = "",
            possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        for (var i = 0; i < 5; i++) {
            text += possible.charAt(Math.floor(Math.random() * possible.length));
        }
        return text;
    }
})();