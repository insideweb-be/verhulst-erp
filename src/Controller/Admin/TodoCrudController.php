<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Todo;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TodoCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return Todo::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud->setEntityLabelInPlural('To do')
            ->setEntityLabelInSingular('To do')
            ->showEntityActionsInlined(true)
        ->setDefaultSort(['date_reminder' => 'ASC']);

        return parent::configureCrud($crud);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        Action::new('getVatInfos', false)
            ->linkToCrudAction('getVatInfos');

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
        $dateReminder = DateTimeField::new('date_reminder')->setLabel('Date rappel')->setRequired(true)->setFormat('dd/MM/yy hh:mm');
        $contact = AssociationField::new('client')->setLabel('Client')->setRequired(false);
        $todo = TextEditorField::new('todo')->setLabel('Todo')->setRequired(true);
        $done = BooleanField::new('done')->setLabel('Fait ?');
        $user = AssociationField::new('user')->setLabel('Sales')->setRequired(false);
        $dateDone = DateTimeField::new('date_done')->setLabel('Date de réalisation');
        $projet = AssociationField::new('project')->setLabel('Projet')->setRequired(false);
        $type = AssociationField::new('type')->setLabel('Type de To do')->setRequired(true);
        $societe = TextField::new('client.company')->setLabel('Sociéte')->setRequired(false);

        if ($this->isGranted('ROLE_ADMIN')) {
            switch ($pageName) {
                case Crud::PAGE_NEW:
                    $response = [$type, $dateReminder, $user, $projet, $contact, $todo];
                    break;
                case Crud::PAGE_EDIT:
                    $response = [$type, $dateReminder, $user, $projet, $contact, $todo, $done];
                    break;
                case Crud::PAGE_DETAIL:
                    $response = [$type, $dateReminder, $user, $projet, $contact, $todo, $done, $dateDone];
                    break;
                default:
                    $response = [$type, $dateReminder, $user, $projet, $contact, $societe, $todo, $done];
            }
        } else {
            switch ($pageName) {
                case Crud::PAGE_NEW:
                    $response = [$type, $dateReminder, $projet, $contact, $todo];
                    break;
                case Crud::PAGE_INDEX:
                    $response = [$type, $dateReminder, $projet, $contact, $societe, $todo, $done];
                    break;
                case Crud::PAGE_EDIT:
                    $response = [$type, $dateReminder, $projet, $contact, $todo, $done];
                    break;
                case Crud::PAGE_DETAIL:
                    $response = [$type, $dateReminder, $projet, $contact, $todo, $done, $dateDone];
                    break;
                default:
                    $response = [$type, $dateReminder, $projet, $contact, $todo, $done];
            }
        }

        return $response;
    }

    public function persistEntity(EntityManagerInterface $entityManager, mixed $entityInstance): void
    {
        if (null === $entityInstance->getUser()) {
            $entityInstance->setUser($this->getUser());
        }
        parent::persistEntity($entityManager, $entityInstance);
    }
}
