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
                HookRegistry::register('LoadHandler', array($this, 'websiteSettings'));
                HookRegistry::register('LoadHandler', array($this, 'checkPublishUrl'));
                $this->modifyDatabase();
                $this->createSmartContractTable();
                $this->createSubmissionTable();
                // $this->createAddressAuthorsTable();
                // $this->createAddressPublishersTable();
                // $this->createAddressReviewersTable();
                $this->createPercentageSettingsTable();
                // $this->createReviewersTable();
                // $this->dropCustomTable();
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

    // Function to make the plugin import some of the javascript
    private function importJavascript($templateMgr, $request, $type)
    {
        $templateMgr->addJavaScript(
            'etherjs',
            "https://cdn.ethers.io/lib/ethers-5.4.umd.min.js",
            array(
                'priority' => STYLE_SEQUENCE_LAST,
                'contexts' => [$type == 'backend' ? 'backend' : 'frontend']
            )
        );
        $templateMgr->addJavaScript(
            'iziToastjs',
            "https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js",
            array(
                'priority' => STYLE_SEQUENCE_LAST,
                'contexts' => [$type == 'backend' ? 'backend' : 'frontend']
            )
        );
        $templateMgr->addJavaScript(
            'Sweel_alert_2',
            "https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js",
            array(
                'priority' => STYLE_SEQUENCE_LAST,
                'contexts' => [$type == 'backend' ? 'backend' : 'frontend']
            )
        );
    }

    // Function to make the plugin import some of the stylesheets
    private function importStylesheet($templateMgr, $request, $type)
    {
        $templateMgr->addStyleSheet(
            'ethercss',
            "https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css",
            array(
                'priority' => STYLE_SEQUENCE_LAST,
                'contexts' => [$type == 'backend' ? 'backend' : 'frontend']
            )
        );
        $templateMgr->addStyleSheet(
            'SweetAlertCss',
            "https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css",
            array(
                'priority' => STYLE_SEQUENCE_LAST,
                'contexts' => [$type == 'backend' ? 'backend' : 'frontend']
            )
        );
    }

    // Function to create a donate button in article page
    public function createButton($hookName, $args)
    {
        $smarty = &$args[1];
        $output = &$args[2];
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);

        $article = $smarty->getTemplateVars('article');

        // Make sure the article is exist and published
        if ($article && $article->getStatus() === STATUS_PUBLISHED) {

            $templateMgr->addJavaScript(
                'donate_button',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/donate_button.js?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => ['frontend'],
                )
            );
            $templateMgr->addJavaScript(
                'react_minified',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/index-5fa8768b.js?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => ['frontend'],
                    'attributes' => array(
                        'type' => 'module',
                        'crossorigin' => true,
                    ),
                )
            );
            $this->importJavascript($templateMgr, $request, 'frontend');

            $output .= $smarty->fetch($this->getTemplateResource('donate_button.tpl'));
        }
    }

    // Function to add a field to existing database table from OJS
    private function modifyDatabase()
    {
        try {
            $schema = Capsule::schema();

            $newFields = array(
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
                    'fieldName' => 'publisher_id',
                    'type' => 'integer'
                ),
                array(
                    'tableName' => 'authors',
                    'fieldName' => 'wallet_address',
                    'type' => 'string'
                ),
                array(
                    'tableName' => 'authors',
                    'fieldName' => 'percentage',
                    'type' => 'integer'
                ),
                array(
                    'tableName' => 'users',
                    'fieldName' => 'wallet_address',
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
                        } else if ($type === 'boolean' && $fieldName !== 'publisher_agreement') {
                            $table->boolean($fieldName)->default(false)->nullable();
                        } else if ($type === 'boolean' && $fieldName === 'publisher_agreement') {
                            $table->boolean($fieldName)->default(true)->nullable();
                        } else if ($type === 'integer') {
                            $table->integer($fieldName)->default(false)->nullable();
                        }
                    });
                }
            }
        } catch (Exception $e) {
            throw new Exception('Database connection error: ' . $e->getMessage());
        }
    }

    // Function to create a new table called "smart_contract"
    private function createSmartContractTable()
    {
        try {
            $schema = Capsule::schema();

            // Add a new table
            if (!$this->checkTableInDB('smart_contract')) {
                $schema->create('smart_contract', function ($table) {
                    $table->integer('id_submission')->primary();
                    $table->string('smart_contract_address');
                    $table->integer('percentages_publisher');
                    $table->integer('percentages_reviewers');
                    $table->integer('percentages_authors');
                    // $table->integer('percentage_editors');
                    $table->timestamp('expired')->nullable();
                });
            }
        } catch (Exception $e) {
            throw new Exception('Database connection error: ' . $e->getMessage());
        }
    }

    // Function to create a new table called "submission"
    private function createSubmissionTable()
    {
        try {
            $schema = Capsule::schema();

            // Add a new table
            if (!$this->checkTableInDB('submission')) {
                $schema->create('submission', function ($table) {
                    $table->integer('id_submission')->primary();
                    $table->string('network');
                    $table->string('url_api_key');
                    $table->string('private_key_account');
                });
            }
        } catch (Exception $e) {
            throw new Exception('Database connection error: ' . $e->getMessage());
        }
    }

    // // Function to create a new table called "address_publishers"
    // private function createAddressPublishersTable()
    // {
    //     try {
    //         $schema = Capsule::schema();

    //         // Add a new table
    //         if (!$this->checkTableInDB('address_publishers')) {
    //             $schema->create('address_publishers', function ($table) {
    //                 $table->increments('id');
    //                 $table->string('smart_contract_address');
    //                 $table->string('wallet_address');
    //             });
    //         }
    //     } catch (Exception $e) {
    //         throw new Exception('Database connection error: ' . $e->getMessage());
    //     }
    // }

    // // Function to create a new table called "address_authors"
    // private function createAddressAuthorsTable()
    // {
    //     try {
    //         $schema = Capsule::schema();

    //         // Add a new table
    //         if (!$this->checkTableInDB('address_authors')) {
    //             $schema->create('address_authors', function ($table) {
    //                 $table->increments('id');
    //                 $table->string('smart_contract_address');
    //                 $table->string('wallet_address');
    //             });
    //         }
    //     } catch (Exception $e) {
    //         throw new Exception('Database connection error: ' . $e->getMessage());
    //     }
    // }

    // // Function to create a new table called "address_reviewers"
    // private function createAddressReviewersTable()
    // {
    //     try {
    //         $schema = Capsule::schema();

    //         // Add a new table
    //         if (!$this->checkTableInDB('address_reviewers')) {
    //             $schema->create('address_reviewers', function ($table) {
    //                 $table->increments('id');
    //                 $table->string('smart_contract_address');
    //                 $table->string('wallet_address');
    //             });
    //         }
    //     } catch (Exception $e) {
    //         throw new Exception('Database connection error: ' . $e->getMessage());
    //     }
    // }

    // Function to create a new table called "percentage_settings"
    private function createPercentageSettingsTable()
    {
        try {
            $schema = Capsule::schema();

            // Add a new table
            if (!$this->checkTableInDB('percentage_settings')) {
                $schema->create('percentage_settings', function ($table) {
                    $table->integer('publisher_id')->primary();
                    $table->integer('percentage_publisher');
                    $table->integer('percentage_reviewers');
                    $table->integer('percentage_authors');
                    // $table->integer('percentage_editors');
                });
            }
        } catch (Exception $e) {
            throw new Exception('Database connection error: ' . $e->getMessage());
        }
    }

    // Function to remove all tables that we created
    private function dropCustomTable()
    {
        try {
            $schema = Capsule::schema();

            if ($this->checkTableInDB('smart_contract')) {
                $schema->dropIfExists('smart_contract');
            }
            if ($this->checkTableInDB('submission')) {
                $schema->dropIfExists('submission');
            }
            if ($this->checkTableInDB('address_publishers')) {
                $schema->dropIfExists('address_publishers');
            }
            if ($this->checkTableInDB('address_authors')) {
                $schema->dropIfExists('address_authors');
            }
            if ($this->checkTableInDB('address_reviewers')) {
                $schema->dropIfExists('address_reviewers');
            }
            if ($this->checkTableInDB('percentage_settings')) {
                $schema->dropIfExists('percentage_settings');
            }
        } catch (Exception $e) {
            throw new Exception('Database connection error: ' . $e->getMessage());
        }
    }

    // Function to check if table is exist in database or not
    private function checkTableInDB($tableName)
    {
        $schema = Capsule::schema();
        return $schema->hasTable($tableName);
    }

    // Function to check if a field is exist in table or not
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

    // Function to push a javascript and css file based on the url
    public function checkAuthorURL($hookName, $args)
    {
        $request = Application::get()->getRequest();
        $currentUrl = $request->url();
        $templateMgr = TemplateManager::getManager($request);

        if (strpos($currentUrl, '/submission/wizard') !== false) {
            //Javascript
            $templateMgr->addJavaScript(
                'addWalletScript',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/author_agreement.js?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => ['backend'],
                    'attributes' => array(
                        'type' => 'module',
                    ),
                )
            );
            //CSS
            $templateMgr->addStyleSheet(
                'showAgreementCSS',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/submission.css?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => ['backend']
                )
            );
            $this->importJavascript($templateMgr, $request, 'backend');
            $this->importStylesheet($templateMgr, $request, 'backend');
        } else {
            error_log('Current URL does not contain "/submission/wizard".');
        }
    }

    // Function to push a javascript and css file based on the url
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
                    'contexts' => ['backend'],
                )
            );
            $templateMgr->addStyleSheet(
                'reviewSubmissionStylesheet',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/submission.css?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => ['backend']
                )
            );
            $this->importJavascript($templateMgr, $request, 'backend');
            $this->importStylesheet($templateMgr, $request, 'backend');
        } else {
            error_log("URL does not match the pattern.");
        }
    }

    // Function to push a javascript and css file based on the url
    public function checkPublishUrl($hookName, $args)
    {
        $request = Application::get()->getRequest();
        $url = $request->getCompleteUrl();
        $templateMgr = TemplateManager::getManager($request);

        $pattern = '/\/workflow\/index\/(\d+)\/(\d+)/';

        if (preg_match_all($pattern, $url, $matches)) {
            // Javascript
            $templateMgr->addJavaScript(
                'publishArticle',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/publish_article.js?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => ['backend'],
                )
            );
            $this->importJavascript($templateMgr, $request, 'backend');
            $this->importStylesheet($templateMgr, $request, 'backend');
        } else {
            error_log("URL does not match the pattern.");
        }
    }

    // Function to push a javascript and css file based on the url
    public function websiteSettings($hookName, $args)
    {
        $request = Application::get()->getRequest();
        $url = $request->getCompleteUrl();
        $templateMgr = TemplateManager::getManager($request);

        if (strpos($url, '/management/settings/website') !== false) {
            // Javascript
            $templateMgr->addJavaScript(
                'websiteSettingsScript',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/website_setting.js?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => ['backend'],
                )
            );

            $templateMgr->addStyleSheet(
                'websiteSettingsStylesheet',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/website_setting.css?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => ['backend']
                )
            );
            $this->importJavascript($templateMgr, $request, 'backend');
            $this->importStylesheet($templateMgr, $request, 'backend');
        }
    }
}
