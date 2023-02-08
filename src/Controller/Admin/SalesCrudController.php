<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Commission;
use App\Entity\Sales;
use App\Entity\User;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Orm\EntityRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

class SalesCrudController extends BaseCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        $crud->setEntityLabelInPlural('Ventes')
            ->setEntityLabelInSingular('Vente')
            ->showEntityActionsInlined(true);

        return parent::configureCrud($crud);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions
            ->setPermission(Action::NEW, 'ROLE_COMMERCIAL')
            ->setPermission(Action::EDIT, 'ROLE_COMMERCIAL')
            ->setPermission(Action::DELETE, 'ROLE_COMMERCIAL')
            ->setPermission(Action::DETAIL, 'ROLE_COMMERCIAL')
            ->setPermission(Action::INDEX, 'ROLE_COMMERCIAL')
            ->setPermission(Action::SAVE_AND_RETURN, 'ROLE_COMMERCIAL')
            ->setPermission(Action::SAVE_AND_ADD_ANOTHER, 'ROLE_COMMERCIAL')
            ->setPermission(Action::SAVE_AND_CONTINUE, 'ROLE_COMMERCIAL')
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye');
            })
            ->add(Crud::PAGE_EDIT, Action::DELETE)
        ;

        return $actions;
    }

    public static function getEntityFqcn(): string
    {
        return Sales::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $price = MoneyField::new('price')
            ->setStoredAsCents()
            ->setNumDecimals(2)
            ->setRequired(true)
            ->setCurrency('EUR');
        $product = AssociationField::new('product')->setRequired(true);
        $contacts = AssociationField::new('contact')->setRequired(true);
        $date = DateField::new('date');
        $percent_com = PercentField::new('percent_com')
            ->setNumDecimals(2)
            ->setStoredAsFractional(true)
            ->setPermission('ROLE_ADMIN');
        $percent_vr = PercentField::new('percent_vr')
            ->setNumDecimals(2)
            ->setStoredAsFractional(true)
            ->setPermission('ROLE_ADMIN');

        $quantity = IntegerField::new('quantity');

        switch ($pageName) {
            case Crud::PAGE_NEW:
            case Crud::PAGE_EDIT:
                $response = [$product, $contacts, $quantity, $price, $date, $percent_com, $percent_vr];
                break;
            default:
                $response = [$product, $contacts, $quantity, $price, $date, $percent_com, $percent_vr];
        }

        return $response;
    }

    public function createNewForm(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormInterface
    {
        /** @var Sales $instance */
        $instance = $entityDto->getInstance();
        $instance->setDate(new \DateTime());
        $entityDto->setInstance($instance);

        return parent::createNewForm($entityDto, $formOptions, $context); // TODO: Change the autogenerated stub
    }

    /**
     * @param Sales $entityInstance
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $user */
        $user = $this->getUser();
        $entityInstance->setUser($user);
        $entityInstance->setPercentVr($entityInstance->getProduct()->getPercentVr());

        /** @var Commission $com */
        $com = $entityManager->getRepository(Commission::class)->findOneBy(['product' => $entityInstance->getProduct(), 'user' => $this->getUser()]);

        $percent_com = 0;
        if (null !== $com) {
            $percent_com = $com->getPercentCom();
        }

        $entityInstance->setPercentCom($percent_com);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        /** @var QueryBuilder $qb */
        $qb = $this->container->get(EntityRepository::class)->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.user = :user')
            ->setParameter('user', $this->getUser());

        return $qb;
    }

    /**
     * @param AdminContext $context
     * @return KeyValueStore|Response
     */
    public function index(AdminContext $context)
    {
        $user = $this->getUser();

        return $this->render('admin/recap/myrecap.html.twig', [
            'user' => $user,
        ]);
    }
}
