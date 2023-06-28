# hello-docker-sitexml

Trivial SiteXML application running in a container.

## How to run

### Prerequisites
- Docker installed and running

### Build and run the application

1. Build the images

```$ docker build -t sitexmlapp .```

2. Start the container

```$ docker-compose up```

3. Browse the application at http://localhost

## Getting started

### 1. Add content
The main file SiteXML file is ```.site.xml```. It consists of three pages, but all use the same content. So, good point to start would be to add more content to this site. To do this, just add more HTML files with your content into the ```.content``` directory (e.g. ```pageA.html```, ```pageB.html```) and link them to the pages in ```.site.xml```, like this:

```xml
...
  <page id="2" name="Page A">
    <content id="2" name="main">pageA.html</content>
  </page>
  <page id="3" name="Page B">
    <content id="3" name="main">pageB.html</content>
  </page>
...
```

### 2. Update page aliases
You might have noticed that as you browse the site by clicking the pages in the navigation, the pages' URL's have page id, e.g. http://localhost/?id=2. To make the URL look better, update page aliases:

```xml
...
  <page id="2" name="Page A">
    <content id="2" alias="page-a" name="main">pageA.html</content>
  </page>
  <page id="3" name="Page B">
    <content id="3" alias="page-b" name="main">pageB.html</content>
  </page>
...
```

### 3. Add theme
The site is not using any themes. Actually it does, but only a system one, which is very trivial. You can add a theme to the folder ```.themes```, e.g. ```mytheme.thm```. It can by any text file, for example, HTML, and can use [SiteXML theme macrocommands](https://sitexml.info/api). Link it to the site:

```xml
<site>
  <theme file="mytheme.thm" id="1"></theme>
  <page id="1" name="home">
    <content id="1" name="main">hello.html</content>
  </page>
  <page id="2" name="Page A">
    <content id="2" name="main" alias="page-a">pageA.html</content>
  </page>
  <page id="3" name="Page B">
    <content id="3" name="main">hello.html</content>
  </page>
</site> 
```
