<?php

namespace UniteCMS\AdminBundle\AdminView;

use UniteCMS\CoreBundle\ContentType\ContentType;

interface AdminFieldConfiguratorInterface
{

    /**
     * Modify the field configuration based on context information.
     *
     * All field directives are already added to the field and can be used. You
     * should only modify field config and fieldType. In case of invalid field
     * configuration, you should throw an InvalidAdminViewFieldConfig exception.
     *
     * @param AdminViewField $field
     * @param AdminView $adminView
     * @param ContentType $contentType
     */
    public function configureField(AdminViewField $field, AdminView $adminView, ContentType $contentType);
}
