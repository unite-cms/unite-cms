<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 03.11.18
 * Time: 12:49
 */

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\View\ViewTypeManager;

class ReferenceOfType extends AbstractType
{
    private $viewTypeManager;

    public function __construct(ViewTypeManager $viewTypeManager)
    {
        $this->viewTypeManager = $viewTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('view')
            ->setRequired('reference_field');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['template'] = null;
        $view->vars['templateParameters'] = null;

        if(empty($options['view']) || !$options['view'] instanceof View) {
            throw new InvalidArgumentException('Required "view" form option must be of type UniteCMS\CoreBundle\Entity\View.');
        }

        if(empty($options['reference_field']) || !$options['reference_field'] instanceof ContentTypeField) {
            throw new InvalidArgumentException('Required "reference_field" form option must be of type UniteCMS\CoreBundle\Entity\ContentTypeField.');
        }

        if(!$form->getRoot() || !$form->getRoot()->getConfig()->hasOption('content') || !$form->getRoot()->getConfig()->getOption('content') instanceof Content) {
            throw new InvalidArgumentException('ReferenceOf form type needs a content option on the root form element.');
        }

        $content = $form->getRoot()->getConfig()->getOption('content');

        if(empty($content->getId())) {
            return;
        }

        $view->vars['template'] = $this->viewTypeManager->getViewType($options['view']->getType())::getTemplate();
        $view->vars['templateParameters'] = $this->viewTypeManager->getTemplateRenderParameters($options['view']);

        $settings = $view->vars['templateParameters']->getSettings();
        $referenceFilter = ['field' => $options['reference_field']->getIdentifier().'.content', 'operator' => '=', 'value' => $content->getId()];
        $settings['filter'] = empty($settings['filter']) ? $referenceFilter : ['AND' => [$referenceFilter, $settings['filter']]];
        $settings['embedded'] = true;

        $view->vars['templateParameters']->setSettings($settings);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_core_reference_of';
    }
}