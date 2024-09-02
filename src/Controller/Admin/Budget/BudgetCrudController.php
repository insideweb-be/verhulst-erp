<?php

declare(strict_types=1);

namespace App\Controller\Admin\Budget;

use App\Entity\Budget\Budget;
use App\Entity\Budget\Category;
use App\Entity\Budget\Product;
use App\Entity\Budget\SubCategory;
use App\Repository\Budget\EventRepository;
use App\Repository\Budget\Ref\CategoryRepository as CategoryRepositoryRef;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BudgetCrudController extends BaseCrudController
{
    protected Request $request;

    public function __construct(
        RequestStack $requestStack,
        private readonly EventRepository $eventRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly CategoryRepositoryRef $categoryRepositoryRef,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->setEntityLabelInPlural('Budgets')
            ->setEntityLabelInSingular('Budget')
            ->showEntityActionsInlined(true)
            ->overrideTemplate('crud/detail', 'admin/budget/budgets/details.html.twig');

        return $crud;
    }

    public static function getEntityFqcn(): string
    {
        return Budget::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $name = TextField::new('name')->setLabel('Nom');

        return [$name];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_DETAIL, Action::INDEX, function ($action) {
                return $action->linkToUrl($this->adminUrlGenerator
                    ->setController(EventCrudController::class)
                    ->setDashboard(DashboardController::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($this->request->get('event_id'))
                    ->generateUrl()
                );
            })
            ->update(Crud::PAGE_DETAIL, Action::INDEX, function ($action) {
                return $action->linkToUrl($this->adminUrlGenerator
                    ->setController(EventCrudController::class)
                    ->setDashboard(DashboardController::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($this->request->get('event_id'))
                    ->generateUrl()
                );
            })
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->setPermission(Action::DELETE, 'budget-edit')
            ->setPermission(Action::EDIT, 'budget-edit')
        ;
    }

    /**
     * @param Budget $entityInstance
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $event = $this->eventRepository->find($this->request->get('event_id'));
        $entityInstance->setEvent($event);

        $categoriesRef = $this->categoryRepositoryRef->findAll();

        foreach ($categoriesRef as $categoryRef) {
            $category = new Category();
            $category->setName($categoryRef->getName());
            foreach ($categoryRef->getSubCategories() as $subCategoryRef) {
                $subCategory = new SubCategory();
                $subCategory->setName($subCategoryRef->getName());
                $category->addSubCategory($subCategory);
                foreach ($subCategoryRef->getProducts() as $productRef) {
                    $product = new Product();
                    $product->setTitle($productRef->getTitle());
                    $product->setDescription($productRef->getDescription());
                    $product->setVat($productRef->getVat());
                    $subCategory->addProduct($product);
                }
            }
            $entityInstance->addCategory($category);
        }

        parent::persistEntity($entityManager, $entityInstance); // TODO: Change the autogenerated stub
    }

    public function delete(AdminContext $context)
    {
        parent::delete($context); // TODO: Change the autogenerated stub

        return $this->redirect($this->adminUrlGenerator
            ->setDashboard(DashboardController::class)
            ->setController(EventCrudController::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($this->request->get('event_id'))
            ->generateUrl());
    }

    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        $url = $this->adminUrlGenerator
            ->setAction(Action::DETAIL)
            ->setEntityId($this->request->get('event_id'))
            ->setController(EventCrudController::class)
            ->setDashboard(DashboardController::class)
            ->generateUrl();

        return $this->redirect($url);
    }
}
