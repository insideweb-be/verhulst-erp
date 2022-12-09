<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

abstract class BaseCrudController extends AbstractCrudController
{
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-pencil-alt')->setLabel('Edit')->setHtmlAttributes(['title' => 'Edit']);
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')->setLabel('Delete')->setHtmlAttributes(['title' => 'Delete']);
            })
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action->setIcon('fa fa-save');
            })
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-plus-square');
            })
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::DETAIL, 'ROLE_ADMIN')
            ->setPermission(Action::INDEX, 'ROLE_ADMIN')
            ->setPermission(Action::SAVE_AND_RETURN, 'ROLE_ADMIN')
            ->setPermission(Action::SAVE_AND_ADD_ANOTHER, 'ROLE_ADMIN')
            ->setPermission(Action::SAVE_AND_CONTINUE, 'ROLE_ADMIN')
            ;
    }
}
