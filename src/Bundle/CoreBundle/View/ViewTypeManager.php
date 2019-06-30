<?php

namespace UniteCMS\CoreBundle\View;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\View;

class ViewTypeManager
{
    /**
     * @var ViewTypeInterface[]
     */
    private $viewTypes = [];

    /**
     * @var UrlGeneratorInterface $urlGenerator
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return ViewTypeInterface[]
     */
    public function getViewTypes(): array
    {
        return $this->viewTypes;
    }

    public function hasViewType($key): bool
    {
        return array_key_exists($key, $this->viewTypes);
    }

    public function getViewType($key): ViewTypeInterface
    {
        if (!$this->hasViewType($key)) {
            throw new \InvalidArgumentException("The view type: '$key' was not found.");
        }

        return $this->viewTypes[$key];
    }

    /**
     * Get template render parameters for the given view.
     * @param View $view
     * @param string $select_mode
     *
     * @return ViewParameterBag
     */
    public function getTemplateRenderParameters(View $view, $select_mode = ViewTypeInterface::SELECT_MODE_NONE): ViewParameterBag {
        $viewType = $this->getViewType($view->getType());
        $settings = $viewType->getTemplateRenderParameters($view, $select_mode);
        return ViewParameterBag::createFromView($view, $this->urlGenerator, $select_mode, $settings ?? []);
    }

    /**
     * Get template render parameters for the given domain member type.
     *
     * @param DomainMemberType $domainMemberType
     * @param string $select_mode
     *
     * @param array $settings
     * @return ViewParameterBag
     */
    public function getTemplateRenderParametersForDomainMemberType(DomainMemberType $domainMemberType, $select_mode = ViewTypeInterface::SELECT_MODE_NONE, array $settings = []): ViewParameterBag {
        return ViewParameterBag::createFromDomainMemberType($domainMemberType, $this->urlGenerator, $select_mode, $settings);
    }

    /**
     * Validates view settings for given view by using the validation method of the view type.
     * @param ViewSettings $settings
     * @param ExecutionContextInterface $context
     */
    public function validateViewSettings(ViewSettings $settings, ExecutionContextInterface $context)
    {
        $viewType = $this->getViewType($context->getObject()->getType());
        $viewType->validateSettings($settings, $context);
    }

    /**
     * @param ViewTypeInterface $viewType
     *
     * @return ViewTypeManager
     */
    public function registerViewType(ViewTypeInterface $viewType)
    {
        if (!isset($this->viewTypes[$viewType::getType()])) {
            $this->viewTypes[$viewType::getType()] = $viewType;
        }

        return $this;
    }
}
