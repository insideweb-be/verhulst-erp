<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SecurityChecker;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserCrudController extends BaseCrudController
{
    public function __construct(private UserService $userService, private UserRepository $userRepository, protected SecurityChecker $securityChecker)
    {
        parent::__construct($securityChecker);
    }

    #[Route(path: '/admin/{_locale}/modifier-mon-mot-de-passe', name: 'admin_password_update')]
    public function updatePassword(): RedirectResponse|Response
    {
        return $this->render('admin/update_password.html.twig');
    }

    #[Route(path: '/admin/{_locale}/authentification-2-facteurs', name: 'admin_2fa_enable')]
    public function enable2fa(): Response
    {
        return $this->render('admin/enable2fa.html.twig');
    }

    #[Route(path: '/admin/{_locale}/update_profile', name: 'admin_update_profile')]
    public function updateProfile(): Response
    {
        return $this->render('admin/update_profile.html.twig');
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud->setEntityLabelInPlural('Utilisateurs')
            ->setEntityLabelInSingular('Utilisateur')
            ->showEntityActionsInlined(true);

        return parent::configureCrud($crud);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions); // TODO: Change the autogenerated stub

        $resetPassword = Action::new('resetPassword', 'Reset mot de passe')
            ->linkToCrudAction('resetPassword');

        $switchUser = Action::new('switchUser', 'Switch user')
            ->linkToCrudAction('switchUser');

        $actions->add(Crud::PAGE_INDEX, $resetPassword);

        $actions->update(Crud::PAGE_INDEX, 'resetPassword', function (Action $action) {
            return $action->setIcon('fa fa-unlock-keyhole')->setLabel(false)->setHtmlAttributes(['title' => 'Reset mot de passe']);
        });

        $actions->add(Crud::PAGE_INDEX, $switchUser);
        $actions->update(Crud::PAGE_INDEX, 'switchUser', function (Action $action) {
            return $action->setIcon('fa fa-user-secret')->setLabel(false)->setHtmlAttributes(['title' => 'Switch user']);
        });
        $actions->setPermission('switchUser', 'ROLE_ALLOWED_TO_SWITCH');
        $actions->setPermission('resetPassword', 'ROLE_ADMIN');

        return $actions;
    }

    public function resetPassword(AdminContext $context): RedirectResponse
    {
        $user = $context->getEntity()->getInstance();
        $this->userService->processSendingPasswordResetEmail($user);
        $this->addFlash('success', 'Mot de passe réinitialisé');

        return $this->redirect($context->getReferrer());
    }

    public function switchUser(AdminContext $context): RedirectResponse
    {
        $userId = $context->getRequest()->get('entityId');

        $user = $this->userRepository->find($userId);

        return $this->redirect($this->generateUrl('admin') . '?_switch_user=' . $user->getEmail());
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $email = EmailField::new('email');
        $firstname = TextField::new('firstName')->setLabel('Prénom');
        $lastname = TextField::new('lastName')->setLabel('Nom');
        $locale = ChoiceField::new('locale')->allowMultipleChoices(false)->renderExpanded(true)->setChoices(['Français' => 'fr', 'English' => 'en'])->setLabel('Langue');
        $twoFa = BooleanField::new('isTotpEnabled')->setLabel('Double authentification');
        $role = ChoiceField::new('roles')->allowMultipleChoices(true)->renderExpanded(true)->setChoices(['Admin' => 'ROLE_ADMIN', 'Commercial' => 'ROLE_COMMERCIAL', 'Encodeur' => 'ROLE_ENCODE', 'Compta' => 'ROLE_COMPTA'])->setLabel('Rôle');
        $enabled = BooleanField::new('enabled')->setLabel('Validé');
        $freelance = ChoiceField::new('com')->setLabel('Type de Commisssion')->setChoices([
            'Salarié' => 'salarie',
            'Freelance' => 'freelance',
            'TV' => 'tv',
        ]);

        switch ($pageName) {
            case Crud::PAGE_DETAIL:
            case Crud::PAGE_INDEX:
                $response = [$firstname, $lastname, $email, $locale, $twoFa, $role, $freelance, $enabled];
                break;
            case Crud::PAGE_NEW:
            case Crud::PAGE_EDIT:
                $response = [$firstname, $lastname, $email, $locale, $role, $freelance, $enabled];
                break;
            default:
                $response = [$firstname, $lastname, $email, $locale, $twoFa, $role, $freelance, $enabled];
        }

        return $response;
    }

    /**
     * @param ?object $entityInstance
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    /**
     * @param ?object $entityInstance
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /* @var User $entityInstance */
        $entityInstance->setPassword('Password123!');
        $entityManager->persist($entityInstance);
        $entityManager->flush();
        $this->userService->processSendingPasswordResetEmail($entityInstance);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        /** @var QueryBuilder $qb */
        $qb = $this->container->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.roles NOT LIKE :searchTerm')
            ->setParameter('searchTerm', '%ROLE_BOSS%');

        return $qb;
    }
}
