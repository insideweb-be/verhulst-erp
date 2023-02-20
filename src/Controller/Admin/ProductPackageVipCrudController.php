<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ProductPackageVip;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductPackageVipCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductPackageVip::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud->setEntityLabelInPlural('Packages Vip')
            ->setEntityLabelInSingular('Package Vip')
            ->showEntityActionsInlined(true);

        return parent::configureCrud($crud);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_COMMERCIAL')
            ->setPermission(Action::INDEX, 'ROLE_COMMERCIAL')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::SAVE_AND_RETURN, 'ROLE_ADMIN')
            ->setPermission(Action::SAVE_AND_ADD_ANOTHER, 'ROLE_ADMIN')
            ->setPermission(Action::SAVE_AND_CONTINUE, 'ROLE_ADMIN')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $projectName = TextField::new('project.name')->setLabel('Nom du projet');
        $name = TextField::new('name');
        $percentVr = PercentField::new('percent_vr')->setLabel('Com Verhulst')->setPermission('ROLE_ADMIN')->setStoredAsFractional(false);
        $ca = MoneyField::new('ca')->setCurrency('EUR')->setStoredAsCents(false)->setLabel('Prix de vente');
        $description = TextEditorField::new('description');
        $quantityMax = IntegerField::new('quantity_max');
        $quantitySales = IntegerField::new('quantity_sales');
        $quantityAvailable = IntegerField::new('quantity_available');
        $image = ImageField::new('doc')->setBasePath('files/products')->setUploadDir('../../shared/public/files/products');

        switch ($pageName) {
            case Crud::PAGE_DETAIL:
            case Crud::PAGE_INDEX:
                $response = [$projectName, $name, $percentVr, $ca, $description, $quantityMax, $quantitySales, $quantityAvailable];
                break;
            case Crud::PAGE_EDIT:
            case Crud::PAGE_NEW:
                $response = [$name, $percentVr, $ca, $description, $quantityMax, $image];
                break;
            default:
                $response = [$name, $percentVr, $ca, $description, $quantityMax];
        }

        return $response;
    }
}
