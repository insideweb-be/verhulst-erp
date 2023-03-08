<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\SalesRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CodeEditorType;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardPagesController extends DashboardController
{
    public function __construct(private ChartBuilderInterface $chartBuilder, private UserRepository $userRepository, private SalesRepository $salesRepository, private RequestStack $requestStack, private AdminUrlGenerator $adminUrlGenerator)
    {
    }

    #[Route('/admin/{_locale}', name: 'dashboard_admin')]
    public function index(): Response
    {
        if (!$this->isGranted('ROLE_COMMERCIAL')) {
            return $this->redirect($this->adminUrlGenerator->setController(ComptaCrudController::class)->setAction(Action::INDEX)->generateUrl());
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('dashboard_com');
        }

        $months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        $default_year = (new \DateTime())->format('Y');
        $year = $this->requestStack->getCurrentRequest()->get('year', $default_year);
        $default_month = (new \DateTime())->format('m');
        $month = $this->requestStack->getCurrentRequest()->get('month', $default_month);

        $sales = $this->salesRepository->get10LastSales();

        return $this->render('admin/dashboard.html.twig', [
            'year' => $year,
            'month' => $months[$month - 1],
            'month_num' => $month,
            'locale' => $this->requestStack->getCurrentRequest()->getLocale(),
            'sales' => $sales,
        ]);
    }

    #[Route('/admin/{_locale}/dashboard/com', name: 'dashboard_com')]
    public function dashboard(AdminContext $adminContext): Response
    {
        if (!$this->isGranted('ROLE_COMMERCIAL')) {
            throw new ForbiddenActionException($adminContext);
        }
        $months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        $default_year = (new \DateTime())->format('Y');
        $year = $this->requestStack->getCurrentRequest()->get('year', $default_year);
        $default_month = (new \DateTime())->format('m');
        $month = $this->requestStack->getCurrentRequest()->get('month', $default_month);
        /** @var User $me */
        $me = $this->getUser();
        $sales = $this->salesRepository->get10LastSalesByUser($me);

        return $this->render('admin/dashboard_com.html.twig', [
            'year' => $year,
            'month' => $months[$month - 1],
            'month_num' => $month,
            'locale' => $this->requestStack->getCurrentRequest()->getLocale(),
            'sales' => $sales,
        ]);
    }

    #[Route('/admin/{_locale}/dashboard/my-sales', name: 'my_sales')]
    public function my_sales(AdminContext $adminContext): Response
    {
        if (!$this->isGranted('ROLE_COMMERCIAL')) {
            throw new ForbiddenActionException($adminContext);
        }

        $months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

        $default_year = (new \DateTime())->format('Y');
        $year = $this->requestStack->getCurrentRequest()->get('year', $default_year);
        /** @var User $me */
        $me = $this->getUser();
        $sales = $this->salesRepository->getStatsByMonthByUser((int) $year, $me);
        $datas = [];
        foreach ($sales as $sale) {
            $month_index = (int) explode('-', $sale['date'])[0];
            $year = explode('-', $sale['date'])[1];
            $datas[$month_index - 1] = $sale['price'];
        }

        for ($i = 0; $i < 12; ++$i) {
            if (!isset($datas[$i])) {
                $datas[$i] = '0.00';
            }
        }
        ksort($datas);

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'CA',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $datas,
                ],
            ],
        ]);
        $chart->setOptions([
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 100,
                ],
            ],
        ]);

        return $this->render('admin/dashboard/my_sales.html.twig', [
            'chart' => $chart,
            'year' => $year,
            'locale' => $this->requestStack->getCurrentRequest()->getLocale(),
        ]);
    }

    #[Route('/admin/{_locale}/dashboard/my-com', name: 'my_com')]
    public function my_com(AdminContext $adminContext): Response
    {
        if (!$this->isGranted('ROLE_COMMERCIAL')) {
            throw new ForbiddenActionException($adminContext);
        }

        $months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

        $default_year = (new \DateTime())->format('Y');
        $year = $this->requestStack->getCurrentRequest()->get('year', $default_year);
        /** @var User $me */
        $me = $this->getUser();
        $sales = $this->salesRepository->getCommissionsStatsByMonthByUser((int) $year, $me);
        $datas = [];
        foreach ($sales as $sale) {
            $month_index = (int) explode('-', $sale['date'])[0];
            $year = explode('-', $sale['date'])[1];
            $datas[$month_index - 1] = $sale['price'];
        }

        for ($i = 0; $i < 12; ++$i) {
            if (!isset($datas[$i])) {
                $datas[$i] = '0.00';
            }
        }
        ksort($datas);

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'Commissions',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $datas,
                ],
            ],
        ]);
        $chart->setOptions([
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 100,
                ],
            ],
        ]);

        return $this->render('admin/dashboard/my_com.html.twig', [
            'chart' => $chart,
            'year' => $year,
            'locale' => $this->requestStack->getCurrentRequest()->getLocale(),
        ]);
    }

    #[Route('/admin/{_locale}/dashboard/sales_tot', name: 'sales_tot')]
    public function sales_tot(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('dashboard_com');
        }

        $months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

        $default_year = (new \DateTime())->format('Y');
        $year = $this->requestStack->getCurrentRequest()->get('year', $default_year);
        $sales = $this->salesRepository->getStatsByMonth((int) $year);
        $datas = [];
        foreach ($sales as $sale) {
            $month_index = (int) explode('-', $sale['date'])[0];
            $year = explode('-', $sale['date'])[1];
            $datas[$month_index - 1] = $sale['price'];
        }

        for ($i = 0; $i < 12; ++$i) {
            if (!isset($datas[$i])) {
                $datas[$i] = '0.00';
            }
        }
        ksort($datas);

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'CA',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $datas,
                ],
            ],
        ]);
        $chart->setOptions([
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 100,
                ],
            ],
        ]);

        return $this->render('admin/dashboard/sales_tot.html.twig', [
            'chart' => $chart,
            'year' => $year,
            'locale' => $this->requestStack->getCurrentRequest()->getLocale(),
        ]);
    }

    #[Route('/admin/{_locale}/dashboard/users_tot', name: 'users_tot')]
    public function users_tot(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('dashboard_com');
        }

        $months = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

        $default_year = (new \DateTime())->format('Y');
        $year = $this->requestStack->getCurrentRequest()->get('year', $default_year);
        $default_month = (new \DateTime())->format('m');
        $month = $this->requestStack->getCurrentRequest()->get('month', $default_month);
        $day = (new \DateTime($year . '-' . $month . '-01'))->format('t');
        $chart2 = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $sales = $this->userRepository->getStatsByUser(new \DateTime($year . '-' . $month . '-01'), new \DateTime($year . '-' . $month . '-' . $day));
        $users_data = [];
        $sales_data = [];
        foreach ($sales as $sale) {
            $users_data[] = $sale['first_name'] . ' ' . $sale['last_name'];
            $sales_data[] = empty($sale['total']) ? 0 : $sale['total'];
        }

        $chart2->setData([
            'labels' => $users_data,
            'datasets' => [
                [
                    'label' => 'Dataset 1',
                    'data' => $sales_data,
                    'borderColor' => 'white',
                    'backgroundColor' => ['#e32727', '#e29f21', '#ffdd31', '#d0d626', '#4f7423', '#6adb28', '#2cd9d1', '#2a395b', '#3539e0', '#a736de'],
                ],
            ],
        ]);

        return $this->render('admin/dashboard/users_tot.html.twig', [
            'chart2' => $chart2,
            'year' => $year,
            'month' => $months[$month - 1],
            'month_num' => $month,
            'locale' => $this->requestStack->getCurrentRequest()->getLocale(),
        ]);
    }

    #[Route('/admin/{_locale}/recap', name: 'app_admin_recap')]
    public function recap(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        $users = $this->userRepository->getCommercials();

        return $this->render('admin/recap/recap.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/{_locale}/css', name: 'app_admin_css')]
    public function css(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new UnauthorizedHttpException('Unauthorized');
        }
        $fileContent = file_get_contents(realpath(__DIR__ . '/../../../../../shared/public/css/app.css'));

        $form = $this->createFormBuilder()
            ->add('task', CodeEditorType::class, ['attr' => ['data-ea-code-editor-field' => 'true', 'data-ea-align' => 'left']])
            ->getForm();

        $form->get('task')->setData($fileContent);

        return $this->render('admin/css/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
