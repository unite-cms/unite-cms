# Changelog

## 0.8.2 (2019-05-14)
- Added #313: A recaptcha field to secure graphql mutations
- Added #407, #419: A field_group settings to allow to group or hide fields from the form
- Added #383: A country field type
- Added #556: A new geolocation field type
- Added #554: A new language field type
- Added #555: Allow to pass env variable to domain config json
- Added #526: Added a rows_per_page option to view settings
- Added #565: Added a new service tag unite_cms.graphql.schema_type_alteration to alter existing schemas
- Added #498: Introduce a new ILIKE operator for case-insensitive comparing + use it for table view search
- Improved #365: Collection and variant form widgets
- Improved #506: You can now use any custom kind of view field to render a field, not only field types
- Improved #501: Improve sidebar view
- Improved #484: Settings translation now include all languages
- Improved #572: Refactored settings translation admin view
- Improved #563: Make it more easy to override core templates
- Fixed #573: Only add graphql types for types if the user is allowed to ContentVoter::LIST and SettingVoter::View 
- Fixed #577: Allow inline styles in wysiwyg editor (needed for alignment feature)
- Fixed #575: Fixed a css bug for big screens
- Fixed #504: Fix a rendering bug with multiple reference fields
- Fixed #567: Fixed a bug, where deleting choices values via api won't work
- Fixed #561: Default data for fields in a collection was not working
- Fixed #552: Fixed a bug in domain config import script
- Fixed #550: Fixed a bug with incorrect identifiers of fieldable content inside variants

## 0.8.1 (2019-03-30)
- Fixed #505: A problem where images could not be removed
- Fixed #475: A bug with multiple reference and reference_of fields on the same form
- Fixed #287: Missing validation of fields in variant settings
- Fixed #497: Variables in domain configuration now work as designed
- Fixed #492: Now org and domains can be created from existing config files
- Fixed #496: Fixed a problem with collection fields inside variant fields
- Fixed #522: Auto text field could not generate values, if the form was not valid 
- Fixed: Fixed a bug where sorting table rows to lower levels did not work
- Improved #502: content_unique and content_uniquify now accept an array with ids to exclude (for example the current content itself) 
- Improved #341: domain update visualization is now a config diff
- Improved #520: GraphQL filter import now has an cast parameter to compare integer, float, boolean, date and datetime values
- Improved: GraphQL filter now have a ILIKE operator to compare case insensitive values. Default table search now uses ILIKE
- Improved #511: Show a better message to the user, if he_she does not have access to an (reference field)
- Improved #540: Added a experimental feature to allow auto_text fields as variant and collection fields
- Improved #539: Added more toolbar options (table, alginment, highlight) to wysiwyg field and fixed a bug with custom headings

- Added #292: Added more CLI commands (domain config import, list domains)
- Added #542: Added a new money field (that stores a float value and a currency)


## 0.8.0 (2019-01-24)
- Added #429: Provide export options for content type data
- Fixed #479: API Key Authenticator should check detailed token structure in supports method
- Fixed #478: API Key User Provider should throw UsernameNotFoundException instead of TokenNotFoundException
- Fixed #477: 500 error if empty AUTHORIZATION for API calls
- Fixed #476: core needs GuzzleHttp but does not require guzzle
- Fixed #379: Error Access the Graphql Api Definition
- Improved #327: Allow to display fields of a variants field on a view (table)
- Improved #245: Variables Same variable content
- Improved #471: Update npm dependencies
- Improved #468: Allow other bundles to provide api users
- Improved #458: Allow fields to alter data after form submit but before validation
- Improved #454: Replace validation and access expression language classes
- Improved #433: UI improvement: Hide Content Actions if user has no permission
- Improved #404: Symfony 4.2 compatible

## 0.7.7 (2019-01-15)
- Fixed #455: Searching views should reset paging to page 1
- Improved #121: Users only see view row actions they have permissions for

## 0.7.6 (2019-01-09)
- Fixed #463: expression language doctrine content functions are not available when validating validatons

## 0.7.5 (2019-01-09)
- Fixed #459: API: not_empty error messages don't have correct path
- Fixed #440: Missing required label marker (*) when setting not_empty inside a collection
- Fixed #448: Followup for 440: Variants field variant required not_empty get checked when not selected
- Fixed #444: Collection sorting and deleting Variation Fields which contains another Collection cause strange sorting and duplicate Fields
- Fixed #439: Content Type / Setting Type additional settings not validated
- Fixed #422: Numbertype returns Type String in Graphql
- Fixed #420: Checkboxtype returns Type String in Graphql
- Fixed #415: Can't paginate the users list
- Added #428: Fix Test Splitting Code Climate in travis.yml
- Added #436: Configure GraphQL Query Limit
- Added #441: Travis Split core bundle Tests for faster coverage runs
- Added #457: unique expression language function
- Added #445: Unify different expression language classes + provide more functions
- Added #340: Trigger events on domain config file actions
- Added #218: field data processors or tokens

## 0.7.4 (2018-12-07)
- Added #412: Update phpunit dist due to latest changes
- Fixed #409: Cant select cross domain content type for reference_of field
- Fixed #323: Mutation with Type 'StorageFileInput'
- Partly Fixed #379: added validation to prevent identifiers starting with numbers

## 0.7.3 (2018-11-30)
- Added #378: GraphQL API now allows to delete content
- Added #380: GraphQL API Maximum nesting level is now configurable
- Fixed #399: Fixed a bug where selection of nested tree items in reference overlay was not working
- Fixed #387, #385: Don't show an 500 error if reference or content_label are not set correctly and try to validate them during domain create / update 

## 0.7.2 (2018-11-23)
- Added #355: Reference_of field to access all content that references this content via GraphQL API
- Added #231: A tree view to display and sort tree-nested data
- Improved #371: The layout of the view header was refactored and views now have a embedded option for be embedded in a form
- [Security] Fixed #386, #388: Filesystem config is now always in sync with org and domains on create, change and delete
- Fixed #367: Vue is now deployed in production mode, this should improve performance
- Fixed #373: Fixed a GraphQL mutation error if a content type only contains fields without an input object
- Fixed #375: Show a validation warning if view type is missing instead of raising an exception
- Fixed #376: Fixed cookie and session config to make both routing approaches more robust
- Fixed #394: Fixed a bug, where reference content could not be saved if the reference content_type settings changed
- Fixed #381: Fixed a bug, where reference content in variants (and collections) could not be saved

## 0.7.1 (2018-11-09)
- Improved #362: Enable description, not_empty and default option for more field types
- Fixed #360: Fix a bug, where field assets where not added for fields in collections 

## 0.7.0 (2018-11-08)
- Added #246: Domain config is now saved to the filesystem. Changes in the filesystem can be imported into unite cms
- Added #350: Most of the fields now have a "description" setting which will be shown in the form
- Added #239: Most of the fields now have a "default" option for default values and a "not_empty" option for setting the field as required
- Added #311: Refactored view component to allow to display complex fields (like collections or references)
- Added #307, #5: Add a (media) grid view type 
- Added #324: Allow to search table and grid views
- Improved #347: Improve getting translations of content/settings via GraphQL API
- Improved #246: Improve variable definition by saving the whole domain config including the variables to the filesystem
- Improved #310: Allow to define a content-type for webhooks
- Fixed #334: Fixed drag and drop sorting for element at position 0
- Fixed #309: Fixed a bug where you cannot access collection or variant field data inside the webhook query

## 0.6.4 (2018-09-28)
- Fixed #305: New checkbox styling was not applied to all checkboxes
- Fixed #302: Link target icon in nested collection was not shown

## 0.6.3 (2018-09-27)
- Added #220: Provide Generic State field
- Added #222: Implement a Link Field
- Added #249: API: Access translations from content and setting
- Added #288/#295: Extend Checkbox for multiple Checkboxes
- Improved #197: Abstract route generation for common entities
- Improved #297: Improve form rendering for checkbox and choices fields
- Fixed #192: GraphQL: Map validation path of custom validations
- Fixed #257: GraphQL File Data can not access
- Fixed #264: add File Field Type Bucket JSON validation
- Fixed #267: There cannot be more than 1 rows. wrong propertyPath and error, when having multiple collection fields
- Fixed #268: Selecting existing content as translation: API response results in js error
- Fixed #269: GraphQL API: Missing language, when getting more translations of the same content
- Fixed #273: Search in Editor does not work and throws an error in console
- Fixed #274: Variation Type returns always null in Graphql
- Fixed #277: Remove js page exit message on sign up / sign in
- Fixed #278: Writing Id instead of ID in query results in 500 error (should be catched)
- Fixed #285: Field identifier inside collections allows hyphens
- Fixed #286: Update Domain Config causes Crash of Editor
- Fixed #287: Fields inside Variant and Collection don't get validated at all

## 0.6.2 (2018-09-07)
- Added #227: Added an experimental variable feature for domain JSON
- Added #177: Send an internal event and an email to org admins after a user accepts / rejects an invitation.
- Improved #215: Collection field can now be sort and have an updated interface
- Fixed #237: Make locale optional for update mutation
- Fixed #233: Fixed nested collection and reference fields always throws MaximumNestingLevel exception
- Fixed #234: Fixed a bug, where collection field data could not be saved because of invalid form field name  

## 0.6.1 (2018-08-31)
- Fixed #221: Reference Field not working 404 
- Fixed #205: Reference & storage field cannot resolve domains with underscore
- Improved #206: Missing validation: Reference domain and content_type 
- Improved #199: Add window#beforeunload js to all pages with forms

## 0.6.0 (2018-08-17)
- Added #38: Implement CRUD Webhooks
- Added #37: Implement variants field type
- Added #39: Implement custom, configurable field validations for content and setting types  
- Added #41: Implement iFrame live preview for content and setting edit form
- Added #193: Implement required persist input option for mutation api to test mutation without persisting changes to the database
- Added #82: Add docker setup to project dir for local development
- Fixed #191: Authorization issues when user has multiple memberships on the same domain
- Fixed #210: Fixed an issue where user sees content form another organization he_she has access to
- Improved #207, #209, #208: Trigger nested setting field events for create & delete, not only update

## 0.5.8 (2018-07-20)
- Fixed #185: API: Rename header header (Authentication -> Authorization)
- Added #184: API: Handle CORS-preflight requests and allow API Keys to set allowed origin header
- Fixed #188: Add missing path in image api response + fixed a image field setting bug 

## 0.5.7 (2018-07-19)
- Fixed #175: 500 error in invitation form for existing user
- Fixed #170: Add some missing translations
- Fixed #168: CSS Height problem
- Fixed #167: CSS Responsive problem
- Fixed #164: CSS cut-off problem
- Improved #173: Update all dependencies and refactor code so there are no deprecation warnings anymore
- Improved #157: Registration template structure for easy override
- Improved #140: Refactor tests to improve performance. With coverage is now ~25' on travis
- Improved: Update npm dependencies for all core bundles 

## 0.5.6 (2018-07-09)
- Fixed #171: When using an underscore in the organization name, the system is not working

## 0.5.5 (2018-07-03)
- Fixed #156: Registration via invitation form rendering bug fix
- Fixed #153: Show form violation errors on registration form
- Fixed #158: Ensure that field assets will get rendered, even when they are only part of a nested collection form element
- Improved #158: Show a message, if the user was redirected to the login page during invitation process
- Improved #163: Allow to define complex WYSIWYG editor headings 

## 0.5.4 (2018-06-29)

- Fixed: #141 When user is logged in, he_she should not be able to access registration page
- Fixed: #139 API debug flag should be set according to kernel.debug
- Fixed: #138 All composer requirements must be included in bundles as well
- Improved: #151: WYSIWYG: Allow to insert br
- Improved: #150: WYSIWYG: Allow h1-h6
- Improved: #147: Replace Editor and allow code block
- Improved: #142: Change Template Paths to logical paths in CoreBundle

## 0.5.3 (2018-06-21)

- Fixed: #135 API field with underscore resolve error
- Fixed: #134 API Error if no content types but setting types are defined
- Improved: #118: Improve test performance

## 0.5.2 (2018-06-15)

- Fixed: Use hyphens for identifiers only in url and underscores for all internal identifier
- Improved: Allow to delete content/setting/member fields when there is content present. Content for this field will be deleted
- Improved: Show also field details no domain update/delete
- Fixed: Fixed a bug, where the domain identifier could not be updated because unite manager uses the updated identifier instead of the original  
- Fixed: Fixed the invitation form and add missing translations
- Improved: Add css styling to all emails
- Improved: Added nicer http error pages for 403, 404 and 500 errors.  

## 0.5.1 (2018-06-14)

## 0.5.0

- Added: Allow to create/update/delete organizations
- Added: Allow to invite users to an organization without a domain
- Changed: Expression language replaces role based authorization checking 
- Added: Translate all violation messages
- Added: Allow users to cancel their account
- Added: Optional subdomain routing approach 
- Added: Domain update / delete confirmation step
- Fixed: Now you can delete setting types
- Fixed: There must be at least one organization admin
- Fixed: Safari / Firefox js bugs 
- Fixed: UI improvements for small devices

## 0.4.0

- Replace domain member roles with fieldable domain member types and an expression language for access checking 

