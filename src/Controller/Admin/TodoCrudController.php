<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Todo;
use App\Repository\CompanyContactRepository;
use App\Repository\TodoRepository;
use App\Service\SecurityChecker;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\FormInterface;

class TodoCrudController extends BaseCrudController
{
    public function __construct(SecurityChecker $securityChecker, private CompanyContactRepository $companyContactRepository, private TodoRepository $todoRepository, private AdminUrlGenerator $adminUrlGenerator)
    {
        parent::__construct($securityChecker);
    }

    public static function getEntityFqcn(): string
    {
        return Todo::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud->setEntityLabelInPlural('To do')
            ->setEntityLabelInSingular('To do')
            ->showEntityActionsInlined(true)
        ->setDefaultSort(['date_reminder' => 'ASC'])
        ->setSearchFields(['user.firstName', 'user.lastName', 'todo', 'client.firstname', 'client.lastname', 'project.name', 'client.company.name']);

        return parent::configureCrud($crud);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        Action::new('getVatInfos', false)
            ->linkToCrudAction('getVatInfos');


        Action::new('done', false)
            ->linkToCrudAction('done');

        $actions
            ->setPermission('getVatInfos', 'ROLE_COMMERCIAL')
            ->setPermission(Action::NEW, 'ROLE_COMMERCIAL')
            ->setPermission(Action::EDIT, 'ROLE_COMMERCIAL')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_COMMERCIAL')
            ->setPermission(Action::INDEX, 'ROLE_COMMERCIAL')
            ->setPermission(Action::SAVE_AND_RETURN, 'ROLE_COMMERCIAL')
            ->setPermission(Action::SAVE_AND_ADD_ANOTHER, 'ROLE_COMMERCIAL')
            ->setPermission(Action::SAVE_AND_CONTINUE, 'ROLE_COMMERCIAL')
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel(false)->setHtmlAttributes(['title' => 'Consulter']);
            })
        ;

        return $actions;
    }

    public function createNewForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        $form = parent::createNewForm($entityDto, $formOptions, $context); // TODO: Change the autogenerated stub
        $client_id = $context->getRequest()->get('client_id');
        if (!empty($client_id)) {
            $client = $this->companyContactRepository->find($client_id);
            $form->get('client')->setData($client);
        }

        if ($form->has('user')) {
            $form->get('user')->setData($this->getUser());
        }

        return $form;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters); // TODO: Change the autogenerated stub

        if (!$this->isGranted('ROLE_ENCODE')) {
            $qb->andWhere('entity.user = :user')
                ->setParameter('user', $this->getUser());
        }

        return $qb
            ->andWhere('entity.done = :done')
            ->setParameter('done', false);
    }

    public function configureFields(string $pageName): iterable
    {
        $dateReminder = DateField::new('date_reminder')->setLabel('Date rappel')->setRequired(true)->setFormat('dd/MM/yy');
        $hourReminder = TimeField::new('hour_reminder')->setLabel('Heure de rappel')->setRequired(false)->setFormat('hh:mm');
        $contact = AssociationField::new('client')->setLabel('Client')->setRequired(false);
        $todo = TextEditorField::new('todo')->setLabel('Todo')->setRequired(true);
        $done = BooleanField::new('done')->setLabel('Fait ?');
        $user = AssociationField::new('user')->setLabel('Sales')->setRequired(false);
        $dateDone = DateTimeField::new('date_done')->setLabel('Date de réalisation');
        $projet = AssociationField::new('project')->setLabel('Projet')->setRequired(false);
        $type = AssociationField::new('type')->setLabel('Type de To do')->setRequired(true);
        $societe = TextField::new('client.company')->setLabel('Sociéte')->setRequired(false);

        $todoTxt = TextareaField::new('todo')->setLabel('Todo')->setRequired(true)->renderAsHtml();

        $response = match ($pageName) {
            Crud::PAGE_NEW => [$type, $dateReminder, $hourReminder, $user, $projet, $contact, $todo],
            Crud::PAGE_EDIT => [$type, $dateReminder, $hourReminder, $user, $projet, $contact, $todo, $done],
            Crud::PAGE_DETAIL => [$type, $dateReminder, $user, $projet, $contact, $todoTxt, $done, $dateDone],
            default => [$type, $dateReminder, $user, $projet, $contact, $societe, $todo, $done],
        };

        return $response;
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if (null === $entityInstance->getUser()) {
            $entityInstance->setUser($this->getUser());
        }
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function done(AdminContext $adminContext) {
        /** @var Todo $todo */
        $todo = $adminContext->getEntity()->getInstance();
        $todo->setDone(true);
        $todo->setDateDone(new \DateTime());
        $this->todoRepository->save($todo, true);
        return $this->redirect($this->adminUrlGenerator->setController(CompanyCrudController::class)->setAction(Crud::PAGE_DETAIL)->setEntityId($todo->getClient()->getCompany()->getId())->generateUrl());
    }
}
