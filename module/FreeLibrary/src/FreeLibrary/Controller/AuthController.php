<?php

namespace FreeLibrary\Controller;

class AuthController extends \VuFind\Controller\AbstractBase
{
    public function pintestAction()
    {
        // check if request is coming from approved IPs
        $type = $this->params()->fromRoute('type');
        $this->access($type);

        $id = $this->params()->fromRoute('id');
        $pin = $this->params()->fromRoute('pin');

        try {
            $patron = $this->getILS()->patronLogin($id, $pin);

            $valid = !empty($patron);

            // TODO: check if card has expired or not (for internet patrons)
        } catch (\Exception $e) {
            $valid = false;
        }

        $view = $this->createViewModel(
            [
                'valid' => $valid,
            ]
        );

        $view->setTemplate("auth/{$type}/pintest");

        // Disable the VuFind layout
        $view->setTerminal(true);

        return $view;
    }

    public function dumpAction()
    {
        // check if request is coming from approved IPs
        $type = $this->params()->fromRoute('type');
        $this->access($type);

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

    protected function access(String $type)
    {
        $config = $this->getAuthConfig();

        if (!isset($config[$type])) {
            return $this->notFoundAction();
        }

        $authConfig = $config[$type];

        $checkAccess = $authConfig['check_access'] ?? false;
        $accessIps = $authConfig['access_ips'] ?? [];

        if ($checkAccess) {

            if (!in_array($this->getRequest()->getServer('REMOTE_ADDR'), $accessIps)) {
                return $this->notFoundAction();
            }
        }
    }

    protected function getAuthConfig(): array
    {
        $configFile = dirname(__DIR__, 3) . '/config/auth.ini';

        if (!is_file($configFile)) {
            throw new \RuntimeException(
                'Authentication configuration file not found.'
            );
        }

        $config = parse_ini_file(
            $configFile,
            true,
            INI_SCANNER_TYPED
        );

        if ($config === false) {
            throw new \RuntimeException(
                'Unable to parse authentication configuration.'
            );
        }

        return $config;
    }
}
