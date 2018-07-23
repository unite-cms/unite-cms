
unite cms Registration Bundle
=============================

This extends the unite cms core bundle and allows users to self-register and self-cancel an unite cms user account. 

This bundle is not part of the unite cms standard distribution and must therefore be installed manually:

    composer create-project unite-cms/standard unitecms --stability dev
    cd unitecms
    
    composer install unite-cms/registration-bundle
    
    # Now create the databse schema.
    bin/console doctrine:schema:update --force

**Please see [github.com/unite-cms/unite-cms](https://github.com/unite-cms/unite-cms) for more information.**

