<?php

namespace FreeLibrary\Controller;

class AuthController extends \VuFind\Controller\AbstractBase
{
    /**
     * Test a patron PIN.
     *
     * @return \Laminas\View\Model\ViewModel
     */
    public function pintestAction()
    {
        // check if request is coming from approved IPs
        $type = $this->params()->fromRoute('type');
        $this->access($type);

        $barcode = $this->params()->fromRoute('id');
        $pin = $this->params()->fromRoute('pin');

        try {
            $patron = $this->getILS()->patronLogin($barcode, $pin);
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

    /**
     * Dump patron or PIN authentication information.
     *
     * @return \Laminas\View\Model\ViewModel
     */
    public function dumpAction()
    {
        // check if request is coming from approved IPs
        $type = $this->params()->fromRoute('type');
        $this->access($type);

        $barcode = $this->params()->fromRoute('id');
        $pin = $this->params()->fromRoute('pin');

        if ($pin) {

            $profileInteger = 0;
            $valid = false;
            $patron = $this->getILS()->patronLogin($barcode, $pin);

            if ($patron !== null) {

                $profile = $this->getILS()->getMyProfile($patron);
                $valid = $profile ?? false;
                $profileInteger = $this->getProfileInteger($profile['group'] ?? null);
            }

            $view = $this->createViewModel(
                [
                    'valid' => $valid,
                    'id' => $barcode,
                    'profile' => $profileInteger,
                ]
            );

        } else {

            $patron = $this->getILS()->lookupPatronByBarcode($barcode);

            $view = $this->createViewModel(
                [
                    'patron' => $patron,
                ]
            );
        }

        $view->setTemplate("auth/{$type}/dump");

        // Disable the VuFind layout
        $view->setTerminal(true);

        return $view;
    }

    /**
     * Verify that the request comes from an approved IP address.
     *
     * @param string $type Authentication configuration type
     *
     * @return mixed
     */
    protected function access(string $type)
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

    /**
     * Get authentication configuration.
     *
     * @return array
     *
     * @throws \RuntimeException If the configuration file is missing or invalid
     */
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

    /**
     * Get the policy number for a patron profile group.
     *
     * @param ?string $profileGroup Patron profile group
     *
     * @return int Policy number, or 0 for an unknown profile group
     */
    protected function getProfileInteger(?string $profileGroup): int
    {
        switch ($profileGroup) {
            // adults (age 18 and above)
            case 'A':
            case 'EDUCATOR':
            case 'G':
            case 'H':
            case 'P':
            case 'TEM':
            case 'TEMW':
            case 'W':
            case 'VIRTUAL':
                return 1;

            // teens (ages 12-17)
            case 'TA':
            case 'TAVR':
            case 'TN':
            case 'TVR':
                return 2;

            // children (ages 0-11)
            case 'CAOA':
            case 'CAOAVR':
            case 'COA':
            case 'COAVR':
                return 3;

            // children (ages 0-11) with restriction
            case 'C':
            case 'CA':
            case 'CAVR':
            case 'CVR':
                return 4;

            default:
                return 0;
        }
    }
}
