<?php


namespace UniteCMS\AdminBundle\AdminView;


use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\Parser;
use Symfony\Component\Security\Core\Security;
use UniteCMS\AdminBundle\Exception\InvalidAdminViewType;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\GraphQL\Util;
use UniteCMS\CoreBundle\Log\LoggerInterface;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

class AdminViewManager
{
    const TYPE_CONTENT = 'content';
    const TYPE_USER = 'user';
    const TYPE_SINGLE_CONTENT = 'single_content';

    /**
     * @var \UniteCMS\CoreBundle\Domain\DomainManager
     */
    protected $domainManager;

    /**
     * @var Security $security
     */
    protected $security;

    /**
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    public function __construct(DomainManager $domainManager, Security $security, SaveExpressionLanguage $expressionLanguage)
    {
        $this->domainManager = $domainManager;
        $this->security = $security;
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * @param Domain $domain
     *
     * @return AdminView[]
     */
    public function getAdminViews(Domain $domain = null) : array {

        if(!$domain) {
            $domain = $this->domainManager->current();
        }

        $adminViews = [];

        try {
            $schema = Parser::parse(join("\n", $domain->getCompleteSchema()));
        } catch(SyntaxError $e) {
            $domain->log(LoggerInterface::ERROR, sprintf('Could not parse schema for @adminView fragments, because of SyntaxError: %s', $e->getMessage()));
            return [];
        }

        foreach($schema->definitions as $definition) {
            if($definition instanceof FragmentDefinitionNode) {
                $directives = Util::getDirectives($definition);

                $adminDirective = null;

                foreach($directives as $directive) {
                    if($directive['name'] === 'adminView') {
                        $adminDirective = $directive;
                        break;
                    }
                }

                if(!$adminDirective) {
                    continue;
                }

                $id = $definition->typeCondition->name->value;
                $type = null;

                if($domain->getContentTypeManager()->getContentType($id)) {
                    $type = self::TYPE_CONTENT;
                }

                else if($domain->getContentTypeManager()->getUserType($id)) {
                    $type = self::TYPE_USER;
                }

                else if($domain->getContentTypeManager()->getSingleContentType($id)) {
                    $type = self::TYPE_SINGLE_CONTENT;
                }

                else {
                    throw new InvalidAdminViewType();
                }

                $contentType = $domain->getContentTypeManager()->getAnyType($id);

                // If the user is not allowed to query this content type.
                if(!$this->security->isGranted(ContentVoter::QUERY, $contentType)) {
                    continue;
                }

                // If the user is not allowed to see this adminView.
                if(!empty($adminDirective['args']['if']) && !(bool)$this->expressionLanguage->evaluate($adminDirective['args']['if'])) {
                    continue;
                }

                $adminViews[] = new AdminView(
                    $definition,
                    $adminDirective,
                    $contentType,
                    $type
                );
            }
        }

        return $adminViews;
    }
}
