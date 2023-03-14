<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\MailerInterface;

class ProjectArchiveCrudController extends ProjectCrudController
{
    public function __construct(private ProjectRepository $projectRepository, private AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud->setEntityLabelInPlural('Projets : Archives')
            ->setEntityLabelInSingular('Projet : Archive')
            ->showEntityActionsInlined(true);

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name')->setLabel('Nom du projet');

        if ($this->isGranted('ROLE_ENCODE')) {
            $projectEvent = CollectionField::new('product_event')->setLabel('Event à la carte')->allowAdd(true)->allowDelete(true)->setEntryIsComplex()->useEntryCrudForm(ProductEventCrudController::class)->setRequired(true);
            $projectPackage = CollectionField::new('product_package')->setLabel('Package VIP')->allowAdd(true)->allowDelete(true)->setEntryIsComplex()->useEntryCrudForm(ProductPackageVipCrudController::class)->setRequired(true);
            $projectSponsor = CollectionField::new('product_sponsoring')->setLabel('Sponsoring')->allowAdd(true)->allowDelete(true)->setEntryIsComplex()->useEntryCrudForm(ProductSponsoringCrudController::class)->setRequired(true);
            $projectDivers = CollectionField::new('product_divers')->setLabel('Divers')->allowAdd(true)->allowDelete(true)->setEntryIsComplex()->useEntryCrudForm(ProductDiversCrudController::class)->setRequired(true);
        } else {
            $projectEvent = CollectionField::new('product_event')->setLabel('Event à la carte')->allowAdd(true)->allowDelete(false)->setEntryIsComplex()->useEntryCrudForm(ProductEventCrudController::class)->setRequired(true);
            $projectPackage = CollectionField::new('product_package')->setLabel('Package VIP')->allowAdd(false)->allowDelete(false)->setEntryIsComplex()->useEntryCrudForm(ProductPackageVipCrudController::class)->setRequired(true);
            $projectSponsor = CollectionField::new('product_sponsoring')->setLabel('Sponsoring')->allowAdd(false)->allowDelete(false)->setEntryIsComplex()->useEntryCrudForm(ProductSponsoringCrudController::class)->setRequired(true);
            $projectDivers = CollectionField::new('product_divers')->setLabel('Divers')->allowAdd(true)->allowDelete(false)->setEntryIsComplex()->useEntryCrudForm(ProductDiversCrudController::class)->setRequired(true);
        }

        $response = [$name, $projectEvent, $projectSponsor, $projectPackage, $projectDivers];

        return $response;
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        $actions
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, 'archiveProject')
            ->setPermission(Action::NEW, 'ROLE_ENCODE')
            ->setPermission(Action::DETAIL, 'ROLE_COMMERCIAL')
            ->setPermission(Action::INDEX, 'ROLE_COMMERCIAL')
            ->setPermission(Action::SAVE_AND_RETURN, 'ROLE_COMMERCIAL')
            ->setPermission(Action::SAVE_AND_ADD_ANOTHER, 'ROLE_COMMERCIAL')
            ->setPermission(Action::SAVE_AND_CONTINUE, 'ROLE_COMMERCIAL')

        ;

        return $actions;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        /** @var QueryBuilder $qb */
        $qb = $this->container->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.archive = :archive')
            ->setParameter('archive', true);

        return $qb;
    }
}
