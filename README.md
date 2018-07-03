unite cms
=========

[![Build Status](https://travis-ci.org/unite-cms/unite-cms.svg?branch=master)](https://travis-ci.org/unite-cms/unite-cms)
[![Test Coverage](https://api.codeclimate.com/v1/badges/59a0dce5677500c486a5/test_coverage)](https://codeclimate.com/github/unite-cms/unite-cms/test_coverage)

unite cms is a decoupled content management system that allows you to manage all kind of content in a single application using a clean and simple user interface. Developers can access the content via a graphQL API to build all kind of websites, apps or IoT applications.   

[See what makes unite cms different from other CMS](https://www.unitecms.io)

### Getting Started


[See the docs](https://www.unitecms.io/docs)

### Project structure

This is the mono-repository of unite cms core. It includes all core bundles. And pushes changes automatically to each 
the repository of each bundle.

So when you are using unite cms, you will not install this repository directly, but use composer to install all of the 
unite cms core bundles you need in your project. We also provide an standard installation of unite cms which includes 
all core bundles and all core configuration. You can find all details in the [docs](https://www.unitecms.io/docs).


### Contributing

Feel free to report bugs, ask questions, give feedback or help us working on the code base of unite cms. The issue queue 
of this repository is the place for all of this contributions.  

#### Releasing versions

Because the code splitter we are using at the moment is only listening to code push and not to tag push, you need to 
modify files in every bundle that needs to get updated. After that you can add a new git tag version and push the new 
tag to the repository:

    git commit -m "Release version 0.X.X"
    git tag -a v0.X.X -m ""
    git push origin --tags
    git push origin master
