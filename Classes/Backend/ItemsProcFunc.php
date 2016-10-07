<?php

namespace Nawork\NaworkUri\Backend;


use Nawork\NaworkUri\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ItemsProcFunc implements SingletonInterface
{
    /**
     * @param array          $incomingParameters an array containing "items", "config", "TSConfig", "table", "row" and
     *                                           "field"
     */
    public function sysDomainAlterConfigurationItems(&$incomingParameters)
    {
        if ($incomingParameters['table'] === 'sys_domain' && $incomingParameters['field'] === 'tx_naworkuri_use_configuration') {
            foreach (ConfigurationUtility::getAvailableConfigurations() as $configuration) {
                $incomingParameters['items'][] = [$this->buildLabelFromValue($configuration), $configuration];
            }
        }
    }

    /**
     * Convert the given value from underscore or camel cased to
     * space separated human readable format
     *
     * @param string $value The given value to convert
     *
     * @return string
     */
    protected function buildLabelFromValue($value)
    {
        if (stripos($value, '_')) {
            $label = str_replace('_', ' ', $value);
        } else {
            $label = str_replace('_', ' ', GeneralUtility::camelCaseToLowerCaseUnderscored($value));
        }
        // if the beginning is not a hostname, e.g. www.domain.tld or domain.tld, but a normal word, capitalize it
        if (!preg_match('/^([\d\w\-]+)\.([\d\w\-]+)/', $label)) {
            $label = ucfirst($label);
        }

        return $label;
    }
}