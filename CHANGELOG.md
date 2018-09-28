# Changelog

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

