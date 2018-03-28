<?php

namespace UnitedCMS\CoreBundle\View;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnitedCMS\CoreBundle\Entity\View;

class ViewParameterBag implements \JsonSerializable
{
    /**
     * @var array
     */
    private $settings = array();

    /**
     * @var string
     */
    private $csrfToken = '';

    /**
     * @var string
     */
    private $selectMode = '';

    /**
     * @var string
     */
    private $apiEndpointPattern = '';

    /**
     * @var string
     */
    private $createUrlPattern = '';

    /**
     * @var string
     */
    private $updateUrlPattern = '';

    /**
     * @var string
     */
    private $deleteUrlPattern = '';

    /**
     * @var string
     */
    private $recoverUrlPattern = '';

    /**
     * @var string
     */
    private $translationsUrlPattern = '';

    /**
     * @var string
     */
    private $revisionsUrlPattern = '';

    /**
     * @var string
     */
    private $deleteDefinitelyUrlPattern = '';

    public function __construct($settings = [])
    {
        $this->settings = $settings;
    }

    public static function createFromView(View $view, UrlGeneratorInterface $generator, string $select_mode = ViewTypeInterface::SELECT_MODE_NONE, $settings = [])
    {
        $bag = new ViewParameterBag($settings);

        $urlParameter = [
            'domain' => $view->getContentType()->getDomain()->getIdentifier(),
            'organization' => $view->getContentType()->getDomain()->getOrganization()->getIdentifier(),
        ];
        $bag->setApiEndpointPattern($generator->generate('unitedcms_core_api', $urlParameter));

        $urlParameter['view'] = $view->getIdentifier();
        $urlParameter['content_type'] = $view->getContentType()->getIdentifier();
        $bag->setCreateUrlPattern($generator->generate('unitedcms_core_content_create', $urlParameter));

        $urlParameter['content'] = '__id__';
        $bag->setUpdateUrlPattern($generator->generate('unitedcms_core_content_update', $urlParameter));
        $bag->setDeleteUrlPattern($generator->generate('unitedcms_core_content_delete', $urlParameter));
        $bag->setRecoverUrlPattern($generator->generate('unitedcms_core_content_recover', $urlParameter));
        $bag->setDeleteDefinitelyUrlPattern($generator->generate('unitedcms_core_content_deletedefinitely', $urlParameter));

        if(count($view->getContentType()->getLocales()) > 1) {
            $bag->setTranslationsUrlPattern($generator->generate('unitedcms_core_content_translations', $urlParameter));
        }

        $bag->setRevisionsUrlPattern($generator->generate('unitedcms_core_content_revisions', $urlParameter));

        $bag->setSelectMode($select_mode);
        return $bag;
    }

    /**
     * @return string
     */
    public function getApiEndpointPattern(): string
    {
        return $this->apiEndpointPattern;
    }

    /**
     * @return string
     */
    public function getCreateUrlPattern(): string
    {
        return $this->createUrlPattern;
    }

    /**
     * @return string
     */
    public function getUpdateUrlPattern(): string
    {
        return $this->updateUrlPattern;
    }

    /**
     * @return string
     */
    public function getDeleteUrlPattern(): string
    {
        return $this->deleteUrlPattern;
    }

    /**
     * @param string $pattern
     * @return ViewParameterBag
     */
    public function setApiEndpointPattern(string $pattern) {
        $this->apiEndpointPattern = $pattern;
        return $this;
    }

    /**
     * @param string $pattern
     * @return ViewParameterBag
     */
    public function setCreateUrlPattern(string $pattern) {
        $this->createUrlPattern = $pattern;
        return $this;
    }

    /**
     * @param string $pattern
     * @return ViewParameterBag
     */
    public function setUpdateUrlPattern(string $pattern) {
        $this->updateUrlPattern = $pattern;
        return $this;
    }

    /**
     * @param string $pattern
     * @return ViewParameterBag
     */
    public function setDeleteUrlPattern(string $pattern) {
        $this->deleteUrlPattern = $pattern;
        return $this;
    }

    /**
     * @return string
     */
    public function getRecoverUrlPattern(): string
    {
        return $this->recoverUrlPattern;
    }

    /**
     * @param string $recoverUrlPattern
     * @return ViewParameterBag
     */
    public function setRecoverUrlPattern(string $recoverUrlPattern)
    {
        $this->recoverUrlPattern = $recoverUrlPattern;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeleteDefinitelyUrlPattern(): string
    {
        return $this->deleteDefinitelyUrlPattern;
    }

    /**
     * @param string $deleteDefinitelyUrlPattern
     * @return ViewParameterBag
     */
    public function setDeleteDefinitelyUrlPattern(string $deleteDefinitelyUrlPattern)
    {
        $this->deleteDefinitelyUrlPattern = $deleteDefinitelyUrlPattern;
        return $this;
    }

    /**
     * @return string
     */
    public function getTranslationsUrlPattern(): string
    {
        return $this->translationsUrlPattern;
    }

    /**
     * @param string $translationsUrlPattern
     * @return ViewParameterBag
     */
    public function setTranslationsUrlPattern(string $translationsUrlPattern)
    {
        $this->translationsUrlPattern = $translationsUrlPattern;
        return $this;
    }

    /**
     * @return string
     */
    public function getRevisionsUrlPattern(): string
    {
        return $this->revisionsUrlPattern;
    }

    /**
     * @param string $revisionsUrlPattern
     * @return ViewParameterBag
     */
    public function setRevisionsUrlPattern(string $revisionsUrlPattern)
    {
        $this->revisionsUrlPattern = $revisionsUrlPattern;
        return $this;
    }

    /**
     * @return array
     */
    public function getSettings() {
        return $this->settings;
    }

    /**
     * @param array $settings
     * @return ViewParameterBag
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;
        return $this;
    }

    /**
     * @return string
     */
    public function getCsrfToken() : string {
        return $this->csrfToken;
    }

    /**
     * @param string $csrfToken
     * @return ViewParameterBag
     */
    public function setCsrfToken($csrfToken) {
        $this->csrfToken = $csrfToken;
        return $this;
    }

    /**
     * @return string
     */
    public function getSelectMode(): string
    {
        return $this->selectMode;
    }

    /**
     * @param string $selectMode
     * @return ViewParameterBag
     */
    public function setSelectMode(string $selectMode)
    {
        $this->selectMode = $selectMode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSelectModeSingle() : bool {
        return $this->selectMode == ViewTypeInterface::SELECT_MODE_SINGLE;
    }

    /**
     * @return bool
     */
    public function isSelectModeNone() : bool {
        return $this->selectMode == ViewTypeInterface::SELECT_MODE_NONE;
    }

    /**
     * @param string $settingKey
     * @return mixed|null
     */
    public function get(string $settingKey) {
        if(!empty($this->settings[$settingKey])) {
            return $this->settings[$settingKey];
        }

        return null;
    }

    /**
     * @param string $url
     * @return null|string
     */
    public function getUrl(string $url) {

        if($url == 'api') {
            return $this->getApiEndpointPattern();
        }

        if($url == 'create') {
            return $this->getCreateUrlPattern();
        }

        if($url == 'update') {
            return $this->getUpdateUrlPattern();
        }

        if($url == 'delete') {
            return $this->getDeleteUrlPattern();
        }

        if($url == 'recover') {
            return $this->getRecoverUrlPattern();
        }

        if($url == 'delete_definitely') {
            return $this->getDeleteDefinitelyUrlPattern();
        }

        if($url == 'translations') {
            return $this->getTranslationsUrlPattern();
        }

        if($url == 'revisions') {
            return $this->getRevisionsUrlPattern();
        }

        return null;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        return [
            'urls' => [
                'api' => $this->getApiEndpointPattern(),
                'create' => $this->getCreateUrlPattern(),
                'update' => $this->getUpdateUrlPattern(),
                'delete' => $this->getDeleteUrlPattern(),
                'recover' => $this->getRecoverUrlPattern(),
                'delete_definitely' => $this->getDeleteDefinitelyUrlPattern(),
                'translations' => $this->getTranslationsUrlPattern(),
                'revisions' => $this->getRevisionsUrlPattern(),
            ],
            'select' => [
                'mode' => $this->getSelectMode(),
                'is_mode_none' => $this->isSelectModeNone(),
                'is_mode_single' => $this->isSelectModeSingle(),
            ],
            'csrf_token' => $this->getCsrfToken(),
            'settings' => $this->getSettings(),
        ];
    }
}