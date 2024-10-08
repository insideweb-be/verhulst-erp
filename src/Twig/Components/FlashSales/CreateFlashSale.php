<?php

declare(strict_types=1);

namespace App\Twig\Components\FlashSales;

use App\Entity\CompanyContact;
use App\Entity\FastSales;
use App\Entity\User;
use App\Form\Type\NewFlashSaleType;
use App\Form\Type\UpdateFlashSaleType;
use App\Repository\FastSalesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('create_flash_sale', template: 'app/sales/flash/create_flash_sale.html.twig')]
class CreateFlashSale extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public ?CompanyContact $contact = null;

    #[LiveProp]
    public ?FastSales $sale = null;

    public function __construct(private readonly FastSalesRepository $fastSalesRepository)
    {
    }

    #[LiveAction]
    public function save(): RedirectResponse
    {
        $this->submitForm();
        $formData = $this->getForm()->getData();

        $formData->setPercentVr($formData->getPercentVr() * 100);
        $formData->setPercentCom($formData->getPercentCom() * 100);

        if ('fixed' === $formData->getPercentVrType()) {
            $formData->setPercentVrEur((string) ($formData->getPrice() - $formData->getPa()));
        }

        $this->fastSalesRepository->save($formData, true);

        return $this->redirectToRoute('sales_flash_index');
    }

    protected function instantiateForm(): FormInterface
    {
        if (null !== $this->sale) {
            $form = $this->createForm(UpdateFlashSaleType::class, $this->sale);
            if ($form->has('percent_vr')) {
                $form->get('percent_vr')->setData($this->sale->getPercentVr() / 100);
            }
            if ($form->has('percent_com')) {
                $form->get('percent_com')->setData($this->sale->getPercentCom() / 100);
            }

            return $form;
        }
        $flashSale = new FastSales();
        $flashSale->setContact($this->contact);
        if ($this->getUser() instanceof User) {
            /** @var User $user */
            $user = $this->getUser();
            $flashSale->setUser($user);
        }

        return $this->createForm(NewFlashSaleType::class, $flashSale);
    }
}
