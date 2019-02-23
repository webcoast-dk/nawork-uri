<?php

namespace Nawork\NaworkUri\Transformation\PagePath;

use Nawork\NaworkUri\Transformation\AbstractTransformationService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Description of PagePathTransformationService
 *
 * @author Thorben Kapp <thorben@work.de>
 */
class TransformationService extends AbstractTransformationService
{

    /**
     * @param mixed                                                                 $value
     * @param \Nawork\NaworkUri\Transformation\PagePath\TransformationConfiguration $configuration
     * @param \Nawork\NaworkUri\Utility\TransformationUtility                       $transformationUtility
     *
     * @return string
     *
     * @throws \Exception
     */
    public function transform($configuration, $value, $transformationUtility)
    {
        $rootLine = $this->getRootline($value, $configuration->getTable());
        if (count($rootLine) === 0) {
            throw new \RuntimeException('Page path could not be transformed, due to empty rootline.', 1469629524);
        } elseif (count($rootLine) === 1) {
            return '';
        } elseif (count($rootLine) > 1) {
            // reverse root line
            $rootLine = array_reverse($rootLine);
            // remove the first item, the root page
            array_shift($rootLine);
            $transformedValue = [];
            $excludedDokTypes = GeneralUtility::trimExplode(',', $configuration->getExcludeDokTypes(), true);
            foreach ($rootLine as $pageRecord) {
                if ($transformationUtility->getLanguage() > 0) {
                    $translatedFields = $this->getPageOverlay($pageRecord['uid'],
                        $configuration,
                        $transformationUtility->getLanguage());
                    if (count($translatedFields) > 0) {
                        ArrayUtility::mergeRecursiveWithOverrule($pageRecord, $translatedFields);
                    }
                }
                if (!$pageRecord['tx_naworkuri_exclude'] && !in_array($pageRecord['doktype'], $excludedDokTypes)) {
                    $fields = GeneralUtility::trimExplode('//', $configuration->getFields(), true);
                    array_unshift($fields, 'tx_naworkuri_pathsegment');
                    foreach ($fields as $field) {
                        if (array_key_exists($field, $pageRecord) && !empty($pageRecord[$field])) {
                            $transformedValue[] = $pageRecord[$field];
                            break;
                        }
                    }
                }
            }

            return implode($configuration->getPathSeparator(), $transformedValue);
        }
        throw new \Exception('No rootline could be determined for page with uid "' . $value . '"', 1394656424);
    }

    /**
     * @param int    $pageUid
     * @param string $table
     * @param array  $rootLine
     *
     * @return array
     */
    private function getRootline($pageUid, $table, $rootLine = [])
    {
        $pageRecord = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)
            ->select(['*'], $table, ['uid' => (int)$pageUid], [], [], 1)->fetch();
        if (is_array($pageRecord)) {
            $rootLine[] = $pageRecord;
            if ($pageRecord['pid'] > 0) {
                return $this->getRootline($pageRecord['pid'], $table, $rootLine);
            }
        }

        return $rootLine;
    }

    /**
     * @param int                                                                   $pageUid
     * @param \Nawork\NaworkUri\Transformation\PagePath\TransformationConfiguration $configuration
     * @param int                                                                   $language
     *
     * @return array
     */
    private function getPageOverlay($pageUid, $configuration, $language)
    {
        if (VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getCurrentTypo3Version()) < VersionNumberUtility::convertVersionNumberToInteger('9.5.0')) {
            // get overlay fields from conf vars
            $overlayFields = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields']);
            $fields = GeneralUtility::trimExplode('//', $configuration->getFields(), true);
            array_unshift($fields, 'tx_naworkuri_pathsegment');
            // merge overlay fields with select fields from transformation configuration
            $overlayFields = array_unique(array_merge($overlayFields, $fields));
            // remove fields that are not present in the current database to avoid sql errors
            $overlayFields = array_intersect($overlayFields, array_keys($GLOBALS['TYPO3_DB']->admin_get_fields($configuration->getTranslationTable())));
            $translatedRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(implode(',', $overlayFields),
                $configuration->getTranslationTable(),
                'pid=' . (int)$pageUid . ' AND sys_language_uid=' . (int)$language . ' AND deleted=0 AND hidden=0');
        } else {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($configuration->getTable());
            $queryBuilder->select('*')->from($configuration->getTable())->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT))
            )->setMaxResults(1);
            $statement = $queryBuilder->execute();
            if ($statement->rowCount() === 1) {
                $translatedRecord = $statement->fetch();
            } else {
                $translatedRecord = null;
            }
        }
        if (!is_array($translatedRecord)) {
            $translatedRecord = [];
        }

        return $translatedRecord;
    }
}
