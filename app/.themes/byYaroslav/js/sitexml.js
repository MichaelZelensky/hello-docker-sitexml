/**
 *
 * SiteXML JavaScript class
 *
 * https://github.com/MichaelZelensky/sitexml.js
 * https://sitexml.info/sitexml.js
 *
 * @author Michael Zelensky http://miha.in (c) 2017
 * @license MIT
 *
 * Usage:
 *
 * var SiteXML = new sitexml();
 *
 * "Public" methods (methods that are supposed to be used as public):
 *
 * loadSitexml ()
 * loadContent (id)
 * getContentIdByPIDandName (id, [parent])
 * getPageById (id)
 * getContentIdByPidPname(pid, name)
 * getDefaultTheme()
 * getPageTheme(pid)
 * getThemeById(theme_id)
 *
 */


function sitexml (path) {

    this.path = path || '';

    /*
    * Executes ?sitexml STP command
    * Triggers 'sitexml.is.loaded' event
    * Creates the following properties:
    *   this.sitexml - raw server response body
    *   this.sitexmlObj - .site.xml parsed, resulting in document type javascript object
    *   this.siteObj - javascript object representing the site
    */
    this.loadSitexml = function () {
        var me = this;
        this.httpGetAsync(this.path + '?sitexml', function (r) {
            me.sitexml = r;
            me.sitexmlObj = me.parseXML(r);
            me.siteObj = me.getSiteObj();
            me.triggerEvent(window, 'sitexml.is.loaded');
        });
    };

    /*
    Loads content by id, filename, or page id + content name
    Caches the loaded content in this.content
    @Param {Integer} id - get content by id
     */
    this.loadContent = function (id) {
        var me = this,
            loadedContent = {},
            str = this.path;
        if ((typeof id).toLowerCase() === 'number' || id * 1) {
            id = id * 1;
            if (id) {
                str += '/?cid=' + id;
                loadedContent.id = id;
            }
        /*//filename
        } else if ((typeof id).toLowerCase() === 'string') {
            if (str !== '' && str[str.length - 1] !== '/') {
                str += '/';
            }
            str += '.content/' + encodeURI(id);
            loadedContent.filename = id;
        //cid & name
        } else if ((typeof id).toLowerCase() === 'object') {
            if (id.id) {
                id.id = id.id * 1;
            }
            if (id.id && id.name) {
                str += '?id=' + id.id + '&name=' + encodeURI(id.name);
                loadedContent.pid = id.id;
                loadedContent.name = id.name;
            }*/
        }
        this.httpGetAsync(str, function (r) {
            loadedContent.content = r;
            me.content = me.content || {};
            me.content[id + ''] = r;
            me.triggerEvent(window, 'content.is.loaded', {cid: id});
        });
    };

    /**/
    this.getContentIdByPidPname = function (pid, name) {
        var page = this.getPageById(pid);
        if (page && page.content) {
            for (var i = 0, n = page.content.length; i < n; i++) {
                if (page.content[i].attributes.name === name) {
                    return page.content[i].attributes.id;
                }
            }
        }
        return undefined;
    };

    /*
    * Recursive
    *
    * Returns content object by content id
    * */
    this.getContentById = function (cid, parent) {
        var parent = parent || this.siteObj,
            content = undefined,
            p = parent.pages;
        for (var i = 0, n = p.length; i < n; i++) {
            loop1:
                if (p[i].content && p[i].content.length > 0) {
                    for (var j = 0, m = p[i].content.length; j < m; j++) {
                        if (p[i].content[j].attributes.id * 1 === cid * 1) {
                            content = p[i].content[j];
                            break loop1;
                        }
                    }
                    if (!content && p[i].pages && p[i].pages.length > 0) {
                        content = this.getContentById(cid, p[i]);
                    }
                }
        }
        return content;
    };

    /*
    * Recursive
    * @param {Integer} id - page id
    * @param {Object} parent
    * @requires this.siteObj
    * */
    this.getPageById = function (id, parent) {
        var page;
        parent = parent || (this.siteObj);
        for (var i = 0, n = parent.pages.length; i < n; i++) {
            if (parent.pages[i].attributes.id * 1 === id * 1) {
                return parent.pages[i];
            } else if (parent.pages[i].pages) {
                page = this.getPageById(id, parent.pages[i]);
                if (page) {
                    return page;
                }
            }
        }
        return undefined;
    };

    /*
    * Returns default theme if PAGE@theme is not defined (see algorithm: http://sitexml.info/algorithms)
    * */
    this.getDefaultTheme = function () {
        var theme = undefined;
        if (this.siteObj.themes && this.siteObj.themes.length > 0) {
            for (var i = 0, n = this.siteObj.themes.length; i < n; i++) {
                if (this.siteObj.themes[i].attributes.default === 'yes') {
                    theme = this.siteObj.themes[i];
                }
            }
            if (!theme) {
                theme = this.siteObj.themes[0];
            }
        }
        return theme;
    };

    /*
    * Returns theme object for a page, see algorithm here: http://sitexml.info/algorithms
    * @param {Integer} id - page id
    * @requires this.siteObj
    * */
    this.getPageTheme = function (id, parent) {
        var tid, theme, page;
        if (id) {
            page = this.getPageById(id);
            if (page && page.attributes && page.attributes.theme) { //1. getting page's theme
                tid = page.attributes.theme;
                theme = this.getThemeById(tid);
            }
            if (this.siteObj.themes && this.siteObj.themes.length > 0) {
                if (!theme) { //2. getting default theme
                    for (var i = 0, n = this.siteObj.themes.length; i < n; i++) {
                        if (this.siteObj.themes[i].attributes.default && this.siteObj.themes[i].attributes.default.toLowerCase() === 'yes') {
                            theme = this.siteObj.themes[i];
                            break;
                        }
                    }
                }
                if (!theme) { // 3. getting the first theme
                    theme = this.siteObj.themes[0];
                }
            }
        }
        return theme || undefined;
    };

    //
    this.getThemeById = function(id) {
        var theme;
        if (this.siteObj && this.siteObj.themes && this.siteObj.themes.length > 0) {
            for (var i = 0, n = this.siteObj.themes.length; i < n; i++) {
                if (id * 1 === this.siteObj.themes[i].attributes.id * 1) {
                    theme = this.siteObj.themes[i];
                    break;
                }
            }
        }
        return theme || undefined;
    };

    //http://stackoverflow.com/questions/247483/http-get-request-in-javascript
    this.httpGetAsync = function (theUrl, callback) {
        var xmlHttp = new XMLHttpRequest();
        xmlHttp.onreadystatechange = function() {
            if (xmlHttp.readyState == 4 && xmlHttp.status == 200)
                callback(xmlHttp.responseText);
        };
        xmlHttp.open("GET", theUrl, true); // true for asynchronous
        xmlHttp.send(null);
    };

    //
    this.triggerEvent = function (element, name, data) {
        var event; // The custom event that will be created

        if (document.createEvent) {
            event = document.createEvent("HTMLEvents");
            event.initEvent(name, true, true);
        } else {
            event = document.createEventObject();
            event.eventType = name;
        }

        event.eventName = name;
        if (data) {
            event.data = data;
        }

        if (document.createEvent) {
            element.dispatchEvent(event);
        } else {
            element.fireEvent("on" + event.eventType, event);
        }
    };

    //
    this.parseXML = function (xmlstring) {
        var oParser = new DOMParser(),
            oDOM = oParser.parseFromString(xmlstring, "text/xml");
        if (oDOM.documentElement.nodeName == "parsererror") {
            return "error while parsing";
        } else {
            return oDOM;
        }
    };

    //
    this.getSiteObj = function () {
        var site, page, meta, themes, theme,
            siteObj = {
                name : undefined,
                metas : [],
                themes : [],
                pages : []
            },
            xml = this.sitexmlObj;
        if (xml) {
            site = xml.getElementsByTagName('site');
            if (site.length > 0) {
                siteObj.name = site[0].getAttribute('name');
                siteObj.metas = getMeta(site[0]);
                siteObj.pages = getPages(site[0]);
                //themes
                themes = site[0].getElementsByTagName('theme');
                for (var i = 0, n = themes.length; i < n;  i++) { if (themes.hasOwnProperty(i)) {
                    theme = {
                        attributes : {
                            id : themes[i].getAttribute('id'),
                            dir : themes[i].getAttribute('dir'),
                            file : themes[i].getAttribute('file'),
                            default : themes[i].getAttribute('default'),
                            name : themes[i].getAttribute('name')
                        }
                    };
                    theme.content = getContent(themes[i]);
                    siteObj.themes.push(theme);
                }}
            }
        }

        /*
        Returns page objects of the given parent element of the site tree
        @returns {Array} - of objects
        @param {DOM Object} - parent element
        */
        function getPages(parent) {
            var ps, page, pages, subpages;
            if (parent && parent.getElementsByTagName) {
                ps = parent.getElementsByTagName('page');
                ps = Array.prototype.slice.call(ps);
                ps = ps.filter(function(v, i){
                    return v.parentElement === parent;
                });
                if (ps.length) {
                    pages = [];
                    for (var i = 0, n = ps.length; i < n; i++) { if (ps.hasOwnProperty(i)) {
                        page = {
                                attributes : {
                                    id : ps[i].getAttribute('id'),
                                        name : ps[i].getAttribute('name'),
                                        alias : ps[i].getAttribute('alias'),
                                        theme : ps[i].getAttribute('theme'),
                                        nonavi : ps[i].getAttribute('nonavi'),
                                        startpage : ps[i].getAttribute('type')
                                },
                                content : getContent(ps[i]),
                                metas : getMeta(ps[i])
                            };
                        subpages = getPages(ps[i]);
                        if (subpages) {
                            page.pages = subpages;
                        }
                        pages.push(page);
                    }}
                }
            }
            return pages;
        }

        /*
        Returns meta objects of the given parent element of the site tree
        @returns {Array} - of objects
        @param {DOM Object} - parent element
         */
        function getMeta (parent) {
            var metas, meta;
            if (parent && parent.getElementsByTagName) {
                metas = parent.getElementsByTagName('meta');
                metas = Array.prototype.slice.call(metas);
                metas = metas.filter(function(v, i){
                    return v.parentElement === parent;
                });
                if (metas.length) {
                    meta = [];
                    for (var i = 0, n = metas.length; i < n; i++) { if (metas.hasOwnProperty(i)) {
                        meta.push({
                            attributes : {
                                name : metas[i].getAttribute('name'),
                                charset : metas[i].getAttribute('charset'),
                                httpEquiv : metas[i].getAttribute('http-equiv'),
                                scheme : metas[i].getAttribute('scheme'),
                                content : metas[i].getAttribute('content')
                            },
                            content : metas[i].innerHTML
                        });
                    }}
                }
            }
            return meta;
        }

        /*
        Returns content objects of the given parent element of the site tree
        @returns {Array} - of objects
        @param {DOM Object} - parent element
         */
        function getContent(parent) {
            var cs, content;
            if (parent && parent.getElementsByTagName) {
                cs = parent.getElementsByTagName('content');
                cs = Array.prototype.slice.call(cs);
                cs = cs.filter(function(v, i){
                    return v.parentElement === parent;
                });
                if (cs.length) {
                    content = [];
                    for (var i = 0, n = cs.length; i < n; i++) { if (cs.hasOwnProperty(i)) {
                        content.push({
                            attributes : {
                                id : cs[i].getAttribute('id'),
                                name : cs[i].getAttribute('name'),
                                type : cs[i].getAttribute('type')
                            },
                            content : cs[i].innerHTML
                        });
                    }}
                }
            }
            return content;
        }

        return siteObj;
    };
}
