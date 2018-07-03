# Changelog

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

