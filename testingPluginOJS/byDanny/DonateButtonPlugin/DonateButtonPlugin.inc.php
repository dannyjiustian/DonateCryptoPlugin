<?php

use Illuminate\Database\Capsule\Manager as Capsule;

import('lib.pkp.classes.plugins.GenericPlugin');
class DonateButtonPlugin extends GenericPlugin
{
    public function __construct()
    {
        import('lib.pkp.classes.db.DBResultRange');
        import('classes.core.Services');
    }

    public function register($category, $path, $mainContextId = NULL)
    {
        if (parent::register($category, $path, $mainContextId)) {
            if ($this->getEnabled($mainContextId)) {
                HookRegistry::register('Templates::Article::Main', array($this, 'addButton'));
                HookRegistry::register('TemplateManager::display', array($this, 'checkAuthorURL'));
                HookRegistry::register('TemplateManager::display', array($this, 'checkReviewURL'));
                $this->modifyDatabase();
            }
            return true;
        }
        return false;
    }



    public function getDisplayName()
    {
        return 'Donate Button Plugin';
    }

    public function getDescription()
    {
        return 'Enable a donate button';
    }

    public function isPluginEnabled()
    {
        return $this->getEnabled();
    }


    public function addButton($hookName, $args)
    {
        $smarty = &$args[1];
        $output = &$args[2];

        $article = $smarty->getTemplateVars('article');

        if ($article && $article->getStatus() === STATUS_PUBLISHED) {
            $output .= $smarty->fetch($this->getTemplateResource('donate_button.tpl'));
        }
    }

    private function modifyDatabase()
    {
        try {
            $schema = Capsule::schema();

            $newFields = array(
                // array(
                //     'tableName' => 'publications',
                //     'fieldName' => 'wallet_address_author',
                //     'type' => 'string'
                // ),
                // array(
                //     'tableName' => 'publications',
                //     'fieldName' => 'wallet_address_reviewer',
                //     'type' => 'string'
                // ),
                // array(
                //     'tableName' => 'publications',
                //     'fieldName' => 'wallet_address_publisher',
                //     'type' => 'string'
                // ),
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'author_agreement',
                    'type' => 'boolean'
                ),
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'reviewer_agreement',
                    'type' => 'boolean'
                ),
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'publisher_agreement',
                    'type' => 'boolean'
                ),
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'contract_address',
                    'type' => 'string'
                ),
                array(
                    'tableName' => 'authors',
                    'fieldName' => 'crypto_wallet_address',
                    'type' => 'string'
                ),
            );

            foreach ($newFields as $field) {
                $tableName = $field['tableName'];
                $fieldName = $field['fieldName'];
                $type = $field['type'];

                if ($this->checkColumnInDB($tableName, $fieldName)) {
                    $schema->table($tableName, function ($table) use ($fieldName, $type) {
                        if ($type === 'string') {
                            $table->string($fieldName)->nullable();
                        } else if ($type === 'boolean') {
                            $table->boolean($fieldName)->default(false)->nullable();
                        }
                    });
                }
            }
        } catch (Exception $e) {
            throw new Exception('Database connection error: ' . $e->getMessage());
        }
    }

    private function checkColumnInDB($tableName, $fieldName)
    {
        try {
            $conn = Capsule::connection();

            $tableColumns = $conn->getDoctrineSchemaManager()->listTableColumns($tableName);

            // Check if the field already exists in the table
            if (!array_key_exists($fieldName, $tableColumns)) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            throw new Exception('Database connection error: ' . $e->getMessage());
        }
    }

    public function checkAuthorURL($hookName, $args)
    {
        $request = Application::get()->getRequest();
        $currentUrl = $request->url();
        $templateMgr = TemplateManager::getManager($request);

        if (strpos($currentUrl, '/submission/wizard') !== false) {

            // Javascript
            $templateMgr->addJavaScript(
                'showAgreementScript',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/submission_agreement.js?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => 'backend'
                )
            );
            $templateMgr->addJavaScript(
                'addWalletScript',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/add_wallet.js?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => 'backend'
                )
            );
            $templateMgr->addJavaScript(
                'etherjs',
                "https://cdn.ethers.io/lib/ethers-5.4.umd.min.js",
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => 'backend'
                )
            );
            $templateMgr->addJavaScript(
                'iziToastjs',
                "https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js",
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => 'backend'
                )
            );
            $templateMgr->addJavaScript(
                'Sweel_alert_2',
                "https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js",
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => 'backend'
                )
            );
            //CSS
            $templateMgr->addStyleSheet(
                'showAgreementCSS',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/submission.css?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => 'backend'
                )
            );
            $templateMgr->addStyleSheet(
                'etherjs',
                "https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css",
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => 'backend'
                )
            );
            $templateMgr->addStyleSheet(
                'SweetAlertCss',
                "https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css",
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => 'backend'
                )
            );
        } else {
            error_log('Current URL does not contain "/submission/wizard".');
        }
    }

    public function checkReviewURL($hookName, $args)
    {
        $request = Application::get()->getRequest();
        $url = $request->getCompleteUrl();
        $templateMgr = TemplateManager::getManager($request);

        $pattern = '/\/workflow\/index\/(\d+)\/(\d+)/';

        // if (preg_match_all($pattern, $url, $matches)) {
        if (strpos($url, '/reviewer/submission') !== false) {
            // Javascript
            $templateMgr->addJavaScript(
                'reviewAgreementScript',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/review_agreement.js?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => 'backend',
                )
            );
        } else {
            error_log("URL does not match the pattern.");
        }
    }
}