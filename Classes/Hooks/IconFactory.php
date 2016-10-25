<?php

namespace Nawork\NaworkUri\Hooks;


class IconFactory
{
    public function getRecordIconIdentifier($incomingParameters)
    {
        $iconIdentifier = 'tcarecords-tx_naworkuri_uri-';
        $type = is_array($incomingParameters['row']['type']) ? reset($incomingParameters['row']['type']) : $incomingParameters['row']['type'];
        switch ((int)$type) {
            case 0:
                $iconIdentifier .= ((int)$incomingParameters['row']['locked'] === 1) ? 'locked' : 'default';
                break;
            case 1:
                $iconIdentifier .= 'old';
                break;
            case 2:
            case 3:
                $iconIdentifier .= 'redirect';
                break;
            default:
                $iconIdentifier .= 'default';
        }
        return $iconIdentifier;
    }
}