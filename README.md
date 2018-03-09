unite cms
=========

[![Build Status](https://travis-ci.org/unite-cms/unite-cms.svg?branch=master)](https://travis-ci.org/unite-cms/unite-cms)
[![Test Coverage](https://api.codeclimate.com/v1/badges/59a0dce5677500c486a5/test_coverage)](https://codeclimate.com/github/unite-cms/unite-cms/test_coverage)

unite cms is a decoupled content management system that allows you to manage all kind of content in a datacentred way by using an intuitv and simple userinterface.

Developers can access the content via a graphQL API to build all kind of websites, apps or IoT applications.   


## Installation

With a single unite cms installation you can manage multiple separated units called *"Organizations"* which don't share any information with each other. 

At the moment unite cms is based on the Symfony 4.0 and only requires PHP >= 7.1 and a MySQL >= 5.7.9 database.

### Start a new project 

    composer create-project unite-cms/standard u --stability dev

    # Now set configuration using environment variables or .env file. See .env.dist for a list of all required environment parameters. 
    
    bin/console doctrine:schema:update --force

To get started create your first organization and a platform admin user:

    bin/console united:organization:create
    
    bin/console united:user:create 
    
If you want to use the PHP development server execute: 

    bin/console serve:run

To run unite cms content in production mode, execute:

    bin/console assets:install
    bin/console doctrine:schema:update --force --env=prod
    bin/console cache:clear --env=prod    

## Development

    git clone git@github.com:unite-cms/unite-cms.git
    composer install

## Testing

unite cms and all it's bundles uses unit and functional tests by using the PHPUnit framework. To run integration tests, 
execute: 

    phpunit
    
in the root dir. Slow tests, that simulate HTTP requests for example are grouped in the "*slow*" group. Therefore you 
can get a much faster response if you run this group at the end.

    phpunit --exclude-group slow
    phpunit --group slow 

Tests for each bundle can be executed from the root directory of each unite cms core bundle.

## Architecture

unite cms is divided into organizations. This organizations do not share any information and 
allow different groups (for example different agencies) to use the same installation for managing their content.

Each organization can have members and domains. Organization members can be invited into a domain, Different domains in 
the same organization do not share any information. One domain contains all data of a single unit (like a company). A 
domain defines all of its permissions and content structure with a single JSON file.

### Defining content structure

All data within a domain can be structured by defining *"ContentTypes"* and *"SettingTypes"*. ContentTypes allow you to 
create and store multiple content documents, while only a single Setting document is available for a SettingType.

Within one ContentType there is at least one *collection*. A collection contains content documents with optional 
collection settings (for example sorting or nesting information). Each ContentType have the **all**-collection which 
contains all content items. This collection is a simple list by default but can be changed to any other collection 
type.

ContentTypes and SettingTypes define the fields for a single document of this type. This fields can be of different 
types like single text field or more complex document reference fields.

### FieldTypes and CollectionTypes

unite cms includes a basic set of FieldTypes (text field, email field, etc) and CollectionTypes (list, sorted list, 
media grid, etc) for handling common use cases. This core set will be extend over time. Furthermore, developers can 
provide their own types by creating a Symfony bundle that includes their types: 

    # MyBundle\Field\Types\MyFieldType.php 
    namespace MyBundle\Field\Types;
    
    use UnitedCMS\CoreBundle\Field\FieldTypeInterface;
    
    MyFieldType implements FieldTypeInterface {}
    
    
    
    # MyBundle\Collection\Types\MyCollectionType.php
    namespace MyBundle\Collection\Types;
    
    use UnitedCMS\CoreBundle\Collection\CollectionTypeInterface;
    
    MyCollectionType implements CollectionTypeInterface {}

and register them as a service: 

    services.yml
    
    services: 
    
        MyBundle\Field\Types\MyFieldType:
            tags: [united_cms.field_type]    
            
        MyBundle\Collection\Types\MyCollectionType: 
            tags: [united_cms.collection_type]


### unite CMS graphQL API
To get content out of unite CMS developers can consume the graphQL API at: 

    https://yourdomain.com/{organization}/{domain}/api

Or if you are using the subdomain-approach: 

    https://{organization}.yourdomain.com/{domain}/api

You can find a full API reference in the file [API.md](API.md)   

### unite CMS Core API

Internally, unite CMS is based on the Symfony Framework and provides the following services that should be used when 
developing core or extension functionality: 

* **unite.cms.core_manager:** You can get the current organization and domain from this service, based on request 
information (like the path).
* **unite.cms.field_type_manager:** You can get all registered field types from this service and validate field 
settings and field data.
* **unite.cms.collection_type_manager:** You can get all registered collection types from this service.
* **unite.cms.domain_definition_parse:** Parses and serializes a domain object. This service is used to create a 
domain object from the JSON definition.
* **unite.cms.fieldable_form_builder:** The fieldable form builder allows you to build content and setting forms based 
on the defined contentType / settingType fields.  
  