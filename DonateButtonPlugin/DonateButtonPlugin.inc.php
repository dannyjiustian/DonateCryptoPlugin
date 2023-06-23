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
                HookRegistry::register('TemplateManager::display', array($this, 'checkURL'));
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
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'wallet_address_author',
                    'type'=> 'string'
                ),
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'wallet_address_reviewer',
                    'type'=> 'string'
                ),
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'wallet_address_publisher',
                    'type'=> 'string'
                ),
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'author_agreement',
                    'type'=> 'boolean'
                ),
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'reviewer_agreement',
                    'type'=> 'boolean'
                ),
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'publisher_agreement',
                    'type'=> 'boolean'
                ),
                array(
                    'tableName' => 'publications',
                    'fieldName' => 'contract_address',
                    'type'=> 'string'
                ),
            );

            foreach ($newFields as $field) {
                $tableName = $field['tableName'];
                $fieldName = $field['fieldName'];
                $type = $field['type'];

                if ($this->checkColumnInDB($tableName, $fieldName)) {
                    $schema->table($tableName, function ($table) use ($fieldName, $type) {
                        if($type === 'string'){
                            $table->string($fieldName)->nullable();
                        }else if($type === 'boolean'){
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

    public function checkURL($hookName, $args)
    {
        $request = Application::get()->getRequest();
        $currentUrl = $request->url();
        $templateMgr = TemplateManager::getManager($request);

        if (strpos($currentUrl, '/submission/wizard') !== false) {

            // Javascript
            $templateMgr->addJavaScript(
                'showAgreementScript',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/showAgreement.js?v=' . time(),
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

            //CSS
            $templateMgr->addStyleSheet(
                'showAgreementCSS',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/submission.css?v=' . time(),
                array(
                    'priority' => STYLE_SEQUENCE_LAST,
                    'contexts' => 'backend'
                )
            );
        } else {
            error_log('Current URL does not contain "/submission/wizard".');
        }
    }
}
