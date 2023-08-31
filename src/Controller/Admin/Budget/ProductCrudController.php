<?php

namespace App\Controller\Admin\Budget;

use App\Entity\Budget\Product;
use App\Repository\Budget\EventRepository;
use App\Repository\Budget\ProductRepository;
use App\Repository\Budget\Ref\CategoryRepository as CategoryRepositoryRef;
use App\Repository\Budget\SubCategoryRepository;
use App\Service\SecurityChecker;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductCrudController extends BaseCrudController
{
    protected Request $request;

    public function __construct(
        SecurityChecker                        $securityChecker,
        RequestStack                           $requestStack,
        private readonly SubCategoryRepository $subCategoryRepository,
        private readonly AdminUrlGenerator     $adminUrlGenerator
    )
    {
        parent::__construct($securityChecker);
        $this->request = $requestStack->getCurrentRequest();
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->setEntityLabelInPlural('Produits')
            ->setEntityLabelInSingular('Produit')
            ->showEntityActionsInlined(true);

        return $crud;
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::INDEX)
            ->disable(Action::DETAIL)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('title')->setLabel('Nom du produit')
            ->setRequired(true);
        $quantity = NumberField::new('quantity')->setLabel('Quantité')
            ->setRequired(true);
        $price = MoneyField::new('price')->setStoredAsCents(false)
            ->setNumDecimals(2)
            ->setRequired(true)
            ->setCurrency('EUR')
            ->setLabel('Prix');

        return [$name, $quantity, $price];
    }

    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        $url = $this->adminUrlGenerator
            ->setAction(Action::DETAIL)
            ->setEntityId($this->request->get('budget_id'))
            ->setController(BudgetCrudController::class)
            ->setDashboard(DashboardController::class)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $subCategory = $this->subCategoryRepository->find($this->request->get('subcategory_id'));
        $entityInstance->setSubCategory($subCategory);
        parent::persistEntity($entityManager, $entityInstance); // TODO: Change the autogenerated stub
    }

    public function delete(AdminContext $context)
    {
        parent::delete($context); // TODO: Change the autogenerated stub
        return $this->redirect($this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(BudgetCrudController::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($this->request->get('budget_id'))
            ->generateUrl());
    }
}
