<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\ProductSponsoring;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductSponsoringCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductSponsoring::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud->setEntityLabelInPlural('Sponsorings')
            ->setEntityLabelInSingular('Sponsoring')
            ->showEntityActionsInlined(true);

        return parent::configureCrud($crud);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        return $actions
            ->setPermission(Action::NEW, 'ROLE_ENCODE')
            ->setPermission(Action::EDIT, 'ROLE_ENCODE')
            ->setPermission(Action::DETAIL, 'ROLE_COMMERCIAL')
            ->setPermission(Action::INDEX, 'ROLE_COMMERCIAL')
            ->setPermission(Action::DELETE, 'ROLE_ENCODE')
            ->setPermission(Action::SAVE_AND_RETURN, 'ROLE_ENCODE')
            ->setPermission(Action::SAVE_AND_ADD_ANOTHER, 'ROLE_ENCODE')
            ->setPermission(Action::SAVE_AND_CONTINUE, 'ROLE_ENCODE')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $projectName = TextField::new('project.name')->setLabel('Nom du projet');
        $name = TextField::new('name')->setLabel('Nom du produit')->setRequired(true);
        $percentVr = PercentField::new('percent_vr')->setLabel('Commission Verhulst')->setPermission('ROLE_ENCODE')->setStoredAsFractional(false)->setNumDecimals(2)->setRequired(true);
        $percentDefaultFreelance = PercentField::new('percent_freelance')->setLabel('Commission Freelance')->setPermission('ROLE_ENCODE')->setStoredAsFractional(false)->setNumDecimals(2)->setRequired(true);
        $percentDefaultSalarie = PercentField::new('percent_salarie')->setLabel('Commission Salarié')->setPermission('ROLE_ENCODE')->setStoredAsFractional(false)->setNumDecimals(2)->setRequired(true);
        $ca = MoneyField::new('ca')->setCurrency('EUR')->setStoredAsCents(false)->setNumDecimals(2)->setLabel('Prix de vente');
        $description = TextEditorField::new('description');
        $quantityMax = IntegerField::new('quantity_max')->setLabel('Quantité max');
        $quantitySales = IntegerField::new('quantity_sales')->setLabel('Quantité vendue');
        $quantityAvailable = IntegerField::new('quantity_available')->setLabel('Quantité disponible');
        $image = ImageField::new('doc')->setBasePath('files/products')->setUploadDir('../../shared/public/files/products')->setUploadedFileNamePattern('[slug]-[timestamp]-[randomhash].[extension]')->setLabel('Document (PDF)');
        $imageDwonload = TextField::new('download_url')->renderAsHtml()->setLabel('Document (PDF)');

        $percentFreelanceHidden = PercentField::new('percent_freelance')
            ->setLabel('Commission Freelance')
            ->setPermission('ROLE_ADMIN')
            ->setNumDecimals(2)
            ->setStoredAsFractional(false)
            ->setRequired(false)
            ->setCssClass('d-none');

        $percentSalarieHidden = PercentField::new('percent_salarie')
            ->setLabel('Commission Salarié')
            ->setPermission('ROLE_ADMIN')
            ->setNumDecimals(2)
            ->setStoredAsFractional(false)
            ->setRequired(false)
            ->setCssClass('d-none');

        switch ($pageName) {
            case Crud::PAGE_DETAIL:
            case Crud::PAGE_INDEX:
                $response = [$projectName, $name, $percentVr, $ca, $description, $quantityMax, $quantitySales, $quantityAvailable, $imageDwonload];
                break;
            case Crud::PAGE_NEW:
                $response = [$name, $percentVr, $percentDefaultFreelance, $percentDefaultSalarie, $ca, $description, $quantityMax, $image];
                break;
            case Crud::PAGE_EDIT:
                $response = [$name, $percentVr, $ca, $description, $quantityMax, $image, $percentFreelanceHidden, $percentSalarieHidden];
                break;
            default:
                $response = [$name, $percentVr, $ca, $description, $quantityMax];
        }

        return $response;
    }
}
