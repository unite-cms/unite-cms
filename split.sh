#!/bin/bash

# First draft of auto code split based on https://github.com/splitsh/lite

SHA1=`splitsh-lite --prefix=src/Bundle/AdminBundle/`
git push git@github.com:unite-cms/AdminBundle.git ${SHA1}:master --tags

SHA1=`splitsh-lite --prefix=src/Bundle/CoreBundle/`
git push git@github.com:unite-cms/CoreBundle.git ${SHA1}:master --tags

SHA1=`splitsh-lite --prefix=src/Bundle/DoctrineORMBundle/`
git push git@github.com:unite-cms/DoctrineORMBundle.git ${SHA1}:master --tags

SHA1=`splitsh-lite --prefix=src/Bundle/MediaBundle/`
git push git@github.com:unite-cms/MediaBundle.git ${SHA1}:master --tags
