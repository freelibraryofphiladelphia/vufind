<?php

namespace FreeLibrary\Controller;

class AuthController extends \VuFind\Controller\AbstractBase
{
    public function pintestAction()
    {
        $type = $this->params()->fromRoute('type');
        $id = $this->params()->fromRoute('id');
        $pin = $this->params()->fromRoute('pin');

        $view = $this->createViewModel(
            [
                'type'  => $type,
                'id'    => $id,
                'pin'   => $pin,
            ]
        );

        $view->setTemplate("auth/{$type}/pintest");

        return $view;
    }

    public function dumpAction()
    {
        $type = $this->params()->fromRoute('type');
        $id = $this->params()->fromRoute('id');
        $pin = $this->params()->fromRoute('pin', null);

        $view = $this->createViewModel(
            [
                'type'  => $type,
                'id'    => $id,
                'pin'   => $pin,
            ]
        );

        $view->setTemplate("auth/{$type}/dump");

        return $view;

    }
}
