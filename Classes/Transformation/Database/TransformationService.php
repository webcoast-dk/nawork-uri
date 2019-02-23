<?php

namespace Nawork\NaworkUri\Transformation\Database;

use Nawork\NaworkUri\Transformation\AbstractTransformationService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Description of DatabaseTransformationService
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class TransformationService extends AbstractTransformationService
{

    /**
     *
     * @param TransformationConfiguration                     $configuration
     * @param mixed                                           $value
     * @param \Nawork\NaworkUri\Utility\TransformationUtility $transformationUtility
     *
     * @return string
     */
    public function transform($configuration, $value, $transformationUtility)
    {
        $record = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($configuration->getTable())
            ->select(['*'], $configuration->getTable(), [$configuration->getCompareField() => $value], [], [], 1)->fetch();
        if (is_array($record)) {
            if (preg_match_all('/{(.*?)}/',
                $configuration->getPattern($transformationUtility->getLanguage()),
                $fields)
            ) {
                if ($transformationUtility->getLanguage() > 0) {
                    $translatedRecord = $this->getTypoScriptFrontendController()->sys_page->getRecordOverlay($configuration->getTable(), $record, $transformationUtility->getLanguage());
                    if (is_array($translatedRecord)) {
                        $record = $translatedRecord;
                    }
                }
                $output = $configuration->getPattern($transformationUtility->getLanguage());
                foreach ($fields[1] as $field) {
                    // replace the field placeholder if the field exist in the record
                    // if not leave the placeholder to show something is wrong
                    if (array_key_exists($field, $record)) {
                        $output = str_replace('{' . $field . '}', $record[$field], $output);
                    }
                }

                return $output;
            }
        }

        return $value;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

}
