<?php

namespace MauticPlugin\BouncerBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class BouncerIntegration extends AbstractIntegration
{
    const EMAIL_STATUSES = ["ev_awaiting", "ev_unverified", "ev_undeliverable", "ev_unknown", "ev_risky", "ev_deliverable"];

    public function getName()
    {
        return 'Bouncer';
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    public function getClientSecretKey()
    {
        return 'secret_key';
    }

    /**
     * Return array of key => label elements that will be converted to inputs to
     * obtain from the user.
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'secret_key' => 'mautic.integration.bouncer.secret_key',
        ];
    }

    /**
     * @param        $url
     * @param array  $parameters
     * @param string $method
     * @param array  $settings
     *
     * @return mixed|string
     */
    public function makeRequest($url, $parameters = [], $method = 'GET', $settings = [])
    {
        $keys = $this->getDecryptedApiKeys();
        $clientSecretValue = $keys[$this->getClientSecretKey()];

        $settings['headers'] = [
            "Accept: application/json",
            "x-api-key: $clientSecretValue",
        ];

        return parent::makeRequest($url, $parameters, $method, $settings);
    }

    /**
     * @return bool
     */
    public function isAuthorized()
    {
        return true;
    }

    /**
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @param array                          $config
     *
     * @return bool
     */
    public function pushLead($lead, $config = [])
    {
        $emailAddress = $lead->getEmail();
        $url = "https://api.usebouncer.com/v1.1/email/verify?email=$emailAddress";
        $emailVerification = $this->makeRequest($url);

        $addTag = "ev_" . $emailVerification['status'];
        $removeTags = array_diff(self::EMAIL_STATUSES, [$addTag]);

        $this->leadModel->modifyTags($lead, $addTag, $removeTags);

        return true;
    }

}
