<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Sales;
use App\Repository\SalesRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ComptaCrudController extends BaseCrudController
{
    public function __construct(private SalesRepository $salesRepository, private AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Sales::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud->setEntityLabelInPlural('Ventes')
            ->setEntityLabelInSingular('Vente')
            ->showEntityActionsInlined(true)
            ->setEntityPermission('ROLE_COMPTA')
        ->setDefaultSort(['invoiced' => 'ASC']);

        return parent::configureCrud($crud);
    }

    public function configureFields(string $pageName): iterable
    {
        $panelProduct = FormField::addPanel('Produit')->addCssClass('col-sm-6');

        $panelClient = FormField::addPanel('Client')->addCssClass('col-sm-6');

        $panelVente = FormField::addPanel('Vente')->addCssClass('col-sm-6');

        $panelContact = FormField::addPanel('Contact')->addCssClass('col-sm-6');

        $price = MoneyField::new('price')
            ->setStoredAsCents(false)
            ->setNumDecimals(2)
            ->setRequired(true)
            ->setCurrency('EUR')->setLabel('Prix Unitaire');

        $priceTotal = MoneyField::new('total_price')
            ->setStoredAsCents(false)
            ->setNumDecimals(2)
            ->setRequired(true)
            ->setCurrency('EUR')->setLabel('Prix Total');

        $priceMarge = MoneyField::new('marge')
            ->setStoredAsCents(false)
            ->setNumDecimals(2)
            ->setRequired(true)
            ->setCurrency('EUR')->setLabel('Prix final');

        $project = TextField::new('product.project')->setLabel('Projet');
        $product = TextField::new('product')->setLabel('Product');
        $description = TextField::new('product.description')->setLabel('description')->renderAsHtml();
        $company = TextField::new('contact[0].company')->setLabel('Société');
        $companyVat = TextField::new('contact[0].company.vat_number')->setLabel('Tva');
        $companyStreet = TextField::new('contact[0].company.street')->setLabel('Rue');
        $companyPc = TextField::new('contact[0].company.pc')->setLabel('Code postal');
        $companyCity = TextField::new('contact[0].company.city')->setLabel('Ville');
        $companyCountry = CountryField::new('contact[0].company.country')->setLabel('Pays');
        $date = DateField::new('date')->setLabel('Date de vente')->setFormat('dd/MM/yy');
        $contact = TextField::new('contact[0].fullname')->setLabel('Nom');
        $contactTel = TelephoneField::new('contact[0].tel')->setLabel('Tel');
        $contactGsm = TelephoneField::new('contact[0].gsm')->setLabel('Gsm');
        $contactEmail = EmailField::new('contact[0].email')->setLabel('Mail');
        $quantity = IntegerField::new('quantity')->setLabel('Quantité');
        $discount = MoneyField::new('discount')
            ->setStoredAsCents(false)
            ->setNumDecimals(2)
            ->setRequired(false)
            ->setCurrency('EUR')
            ->setLabel('Réduction (EUR)');
        $total = MoneyField::new('marge')
            ->setStoredAsCents(false)
            ->setNumDecimals(2)
            ->setRequired(false)
            ->setCurrency('EUR')
            ->setLabel('Prix total (EUR)');
        $invoiced = BooleanField::new('invoiced')
            ->setLabel('Facturé')->setDisabled(true);

        if ($this->isGranted('ROLE_ADMIN')) {
            $invoiced->setDisabled(false);
        }
        $dateValidation = DateField::new('invoicedDt')->setLabel('Date de validation')->setFormat('dd/MM/yy HH:MM');

        switch ($pageName) {
            case Crud::PAGE_INDEX:
                $response = [$company, $project, $product, $price, $quantity, $discount, $total, $date, $invoiced, $dateValidation];
                break;

            case Crud::PAGE_DETAIL:
                $response = [$panelProduct,
                    $project, $product, $description,
                    $panelClient,
                    $company, $companyVat, $companyStreet, $companyPc, $companyCity, $companyCountry,
                    $panelVente,
                    $price, $quantity, $priceTotal, $discount, $priceMarge, $date, $invoiced, $dateValidation,
                    $panelContact,
                    $contact, $contactTel, $contactGsm, $contactEmail];
                break;
            case Crud::PAGE_EDIT:
                $response = [$invoiced];
                break;
            default:
                $response = [$project, $product, $quantity, $price, $date, $discount];
        }

        return $response;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $granted = $this->isGranted('ROLE_ADMIN');

        $setInvoiced = Action::new('setInvoiced', false)
            ->displayIf(static function ($entity) use ($granted) {
                return !$entity->isInvoiced() || $granted;
            })
            ->linkToCrudAction('setInvoiced');

        $actions
            ->disable(Action::NEW)
            ->setPermission(Action::EDIT, 'ROLE_COMPTA')
            ->disable(Action::DELETE)
            ->setPermission(Action::DETAIL, 'ROLE_COMPTA')
            ->setPermission(Action::INDEX, 'ROLE_COMPTA')
            ->setPermission('setInvoiced', 'ROLE_COMPTA')
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $setInvoiced)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setIcon('fa fa-eye')->setLabel(false);
            })
            ->update(Crud::PAGE_DETAIL, 'setInvoiced', function (Action $action) {
                return $action->setIcon('fa fa-check')->setLabel('Vente encodée')->addCssClass('btn btn-success');
            })
        ;

        return $actions;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('date')
            ->add('invoiced')
            ->add('invoiced_dt')
        ;
    }

    public function setInvoiced(AdminContext $adminContext): RedirectResponse
    {
        /** @var Sales $sale */
        $sale = $adminContext->getEntity()->getInstance();
        $sale->setInvoiced(true);
        if (null === $sale->getInvoicedDt()) {
            $sale->setInvoicedDt(new \DateTime());
        }
        $this->salesRepository->save($sale, true);
        $next = $this->salesRepository->findOneBy(['invoiced' => false]);
        if (null === $next) {
            return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->setEntityId(null)->generateUrl());
        }

        return $this->redirect($this->adminUrlGenerator->setAction(Action::DETAIL)->setEntityId($next->getId())->generateUrl());
    }
}
