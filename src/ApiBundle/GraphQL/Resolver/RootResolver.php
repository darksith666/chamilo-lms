<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\ApiBundle\GraphQL\ApiGraphQLTrait;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionCategory;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\UserBundle\Entity\User;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class RootResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class RootResolver implements ResolverInterface, AliasedInterface, ContainerAwareInterface
{
    use ApiGraphQLTrait;

    /**
     * Returns methods aliases.
     *
     * For instance:
     * array('myMethod' => 'myAlias')
     *
     * @return array
     */
    public static function getAliases()
    {
        return [
            'resolverViewer' => 'viewer',
            'resolveCourse' => 'course',
            'resolveSession' => 'session',
            'resolveSessionCategory' => 'sessioncategory',
        ];
    }

    /**
     * @return User
     */
    public function resolverViewer(): User
    {
        $this->checkAuthorization();

        $token = $this->container->get('security.token_storage')->getToken();

        return $token->getUser();
    }

    /**
     * @param int          $courseId
     * @param \ArrayObject $context
     *
     * @return Course|null
     */
    public function resolveCourse($courseId, \ArrayObject $context)
    {
        $this->checkAuthorization($context);

        $courseRepo = $this->em->getRepository('ChamiloCoreBundle:Course');
        $course = $courseRepo->find($courseId);

        $context->offsetSet('course', $course);

        if (!$course) {
            throw new UserError($this->translator->trans('NoCourse'));
        }

        $checker = $this->container->get('security.authorization_checker');

        if (false === $checker->isGranted(CourseVoter::VIEW, $course)) {
            throw new UserError($this->translator->trans('NotAllowed'));
        }

        return $course;
    }

    /**
     * @param int          $sessionId
     * @param \ArrayObject $context
     *
     * @return Session
     */
    public function resolveSession($sessionId, \ArrayObject $context)
    {
        $this->checkAuthorization($context);

        $sessionManager = $this->container->get('chamilo_core.entity.manager.session_manager');
        $translator = $this->translator;
        /** @var Session $session */
        $session = $sessionManager->find($sessionId);
        $context->offsetSet('session', $session);

        if (!$session) {
            throw new UserError($translator->trans('Session not found.'));
        }

        return $session;
    }

    /**
     * @param int          $categoryId
     * @param \ArrayObject $context
     *
     * @return SessionCategory|null
     */
    public function resolveSessionCategory($categoryId, \ArrayObject $context)
    {
        $this->checkAuthorization($context);

        $repo = $this->em->getRepository('ChamiloCoreBundle:SessionCategory');
        /** @var SessionCategory $category */
        $category = $repo->find($categoryId);

        if (!$category) {
            throw new UserError($this->translator->trans('Session category not found.'));
        }

        return $category;
    }
}
