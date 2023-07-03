/*
* (c) Michael Zelensky 2017
* */
(function(){
    var book = {
        init : function () {
            this.index = document.querySelector('p.index');
            this.navi = document.getElementById('navi-container');
            this.navi = this.navi.childNodes[0];
            while(this.navi.childNodes[0]) this.navi.removeChild(this.navi.childNodes[0]);
            this.makeIndex();
            this.bindEvents();
        },
        makeIndex : function () {
            var me = this,
                li, tagName, name, a, naviA, anc, item, h = document.querySelectorAll('h2, h3, h4');
            for (var i = 0, n = h.length; i < n; i++) {
                item = document.createElement('div');
                tagName = h[i].tagName.toLowerCase();
                item.className = "index-item " + tagName;
                a =  document.createElement('a');
                a.innerHTML = h[i].innerHTML;
                name = h[i].getAttribute('name') || this.genString();
                a.href = "#" + name;
                item.appendChild(a);
                naviA = document.createElement('a');
                naviA.href = "#" + name;
                naviA.innerHTML = h[i].innerHTML;
                anc = document.createElement('a');
                anc.name = name;
                h[i].insertBefore(anc, h[i].childNodes[0]);
                this.index.appendChild(item);
                if (tagName === 'h2') {
                    li = document.createElement('li');
                    li.appendChild(naviA);
                    this.navi.appendChild(li);
                }
            }
        },
        genString : function () {
            return Math.random().toString(36).substring(7);
        },
        bindEvents : function () {
            var h2 = document.querySelectorAll('h2'),
                navi = document.getElementById('navi-container'),
                naviIsShown = false,
                selectedNaviItem,
                h2foreword = document.querySelector('h2[name=foreword]');
            document.addEventListener('scroll', function(e){
                var h2n, liItem;
                if (window.scrollY > h2foreword.offsetTop) {
                    if (!naviIsShown) {
                        navi.style.display = "block";
                        naviIsShown = true;
                    }
                } else {
                    if (naviIsShown) {
                        navi.style.display = "none";
                        naviIsShown = false;
                    }
                }
                for (var i = 0, n = h2.length; i < n; i++) {
                    if (window.scrollY > h2[i].offsetTop - 100) {
                        h2n = h2[i].getAttribute('name');
                        //h2[i].style.fontWeight = "bold";
                    }
                }
                if (h2n !== undefined && selectedNaviItem !== h2n) {
                    if (selectedNaviItem !== undefined) {
                        liItem = document.querySelector('a[href="#' + selectedNaviItem + '"');
                        liItem.style.fontWeight = "normal";
                    }
                    selectedNaviItem = h2n;
                    liItem = document.querySelector('a[href="#' + h2n + '"');
                    liItem.style.fontWeight = "bold";
                }
            });
        }
    };
    book.init();
})();